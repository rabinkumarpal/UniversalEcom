<?php

namespace App\Domain\Pricing;

use App\Core\Contracts\TaxCalculatorContract;
use App\Core\Registry\PricingAdjustmentRegistry;
use App\Core\Registry\ShippingProviderRegistry;
use App\Models\Address;
use App\Models\Cart;

class PricingPipeline
{
    public function __construct(
        protected PricingAdjustmentRegistry $adjusters,
        protected ShippingProviderRegistry $shippingRegistry,
        protected TaxCalculatorContract $taxCalculator
    ) {}

    /**
     * Compute authoritative cart or checkout totals.
     * Never trusts any client-side prices or discounts.
     */
    public function calculate(Cart $cart, ?Address $destination = null, array $options = []): array
    {
        $cart->loadMissing(['items.variant.product', 'items.variant.quantityTiers', 'items.variant.taxClass']);

        $rawSubtotal = 0;
        $lines = [];

        // 1. Authoritative base prices & quantity tiers
        foreach ($cart->items as $item) {
            $variant = $item->variant;
            $unitPrice = $variant->getPriceForQuantity($item->quantity);
            $lineSubtotal = $unitPrice * $item->quantity;
            $rawSubtotal += $lineSubtotal;

            $taxInfo = $this->taxCalculator->calculateTax($variant, $unitPrice, $item->quantity, $destination);

            $lines[$variant->id] = [
                'cart_item_id' => $item->id,
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'unit_price' => $unitPrice,
                'quantity' => $item->quantity,
                'base_total' => $lineSubtotal,
                'discount' => 0,
                'tax' => $taxInfo['tax_amount'],
                'tax_rate' => $taxInfo['rate_percentage'],
                'line_total' => $lineSubtotal,
                'metadata' => [],
            ];
        }

        // 2. Extensible adjustments (Promotion Engine, Coupons, Loyalty, etc.)
        $discountTotal = 0;
        $appliedPromotions = [];
        $freeShipping = false;

        foreach ($this->adjusters->sorted() as $adjuster) {
            $adjustment = $adjuster->calculateCartAdjustments($cart, [
                'destination' => $destination,
                'current_lines' => $lines,
                'options' => $options,
            ]);

            if (! empty($adjustment['discount_total'])) {
                $discountTotal += $adjustment['discount_total'];
            }

            if (! empty($adjustment['free_shipping'])) {
                $freeShipping = true;
            }

            if (! empty($adjustment['applied_promotions'])) {
                $appliedPromotions = array_merge($appliedPromotions, $adjustment['applied_promotions']);
            }

            if (! empty($adjustment['line_adjustments'])) {
                foreach ($adjustment['line_adjustments'] as $variantId => $lineDiscount) {
                    if (isset($lines[$variantId])) {
                        $lines[$variantId]['discount'] += $lineDiscount;
                        $lines[$variantId]['line_total'] = max(0, $lines[$variantId]['base_total'] - $lines[$variantId]['discount']);
                    }
                }
            }
        }

        // 3. Tax calculation sum
        $taxTotal = array_sum(array_column($lines, 'tax'));

        // 4. Shipping rate
        $deliveryFee = 0;
        if (! $freeShipping && $destination) {
            $shippingProvider = $this->shippingRegistry->get();
            $deliveryFee = $shippingProvider->calculateRate($cart, $destination);
        }

        // 5. Grand total
        $grandTotal = max(0, ($rawSubtotal - $discountTotal) + $taxTotal + $deliveryFee);

        return [
            'subtotal' => $rawSubtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'delivery_fee' => $deliveryFee,
            'grand_total' => $grandTotal,
            'free_shipping' => $freeShipping,
            'lines' => array_values($lines),
            'applied_promotions' => $appliedPromotions,
        ];
    }
}
