<?php

namespace App\Domain\Checkout;

use App\Core\Contracts\ShippingRateContract;
use App\Core\Events\OrderPlaced;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Domain\Delivery\ShipmentService;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pricing\PricingPipeline;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        protected PricingPipeline $pricingPipeline,
        protected InventoryService $inventoryService,
        protected ShippingRateContract $shippingProvider,
        protected PaymentGatewayRegistry $paymentRegistry,
        protected ?ShipmentService $shipmentService = null
    ) {
        $this->shipmentService = $shipmentService ?? app(ShipmentService::class);
    }

    /**
     * Complete checkout and generate an immutable order.
     * All calculations are server-authoritative.
     */
    public function checkout(
        Cart $cart,
        Address $shippingAddress,
        ?Address $billingAddress = null,
        string $paymentGateway = 'cod',
        ?string $notes = null,
        ?User $user = null,
        array $options = []
    ): Order {
        $cart->load(['items.variant.product', 'items.variant.quantityTiers', 'items.variant.taxClass']);

        if ($cart->items->isEmpty()) {
            throw new InvalidArgumentException('Cannot checkout with an empty cart.');
        }

        // 1. Verify serviceability
        if (! $this->shippingProvider->isServiceable($shippingAddress->pincode, $shippingAddress)) {
            throw new InvalidArgumentException("Destination pincode [{$shippingAddress->pincode}] is not serviceable.");
        }

        // 2. Authoritative price calculation
        $totals = $this->pricingPipeline->calculate($cart, $shippingAddress, $options);

        return DB::transaction(function () use (
            $cart,
            $shippingAddress,
            $billingAddress,
            $paymentGateway,
            $notes,
            $user,
            $totals,
            $options
        ) {
            $orderNumber = 'ORD-'.strtoupper(Str::random(10));

            // 3. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user?->id ?? $cart->user_id,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'currency' => 'INR',
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'delivery_fee' => $totals['delivery_fee'],
                'grand_total' => $totals['grand_total'],
                'billing_address_snapshot' => ($billingAddress ?? $shippingAddress)->toArray(),
                'shipping_address_snapshot' => $shippingAddress->toArray(),
                'notes' => $notes,
                'placed_at' => now(),
            ]);

            // 4. Create Order Items & Reserve Inventory
            $reservations = [];
            foreach ($totals['lines'] as $line) {
                $itemModel = $cart->items->firstWhere('id', $line['cart_item_id']);
                $variant = $itemModel->variant;

                // Concurrency-safe reservation with pessimistic locking
                $reservation = $this->inventoryService->reserveStock(
                    $variant,
                    $line['quantity'],
                    $order->id,
                    $cart->session_token
                );
                $reservations[] = $reservation;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'sku_snapshot' => $line['sku'],
                    'product_name_snapshot' => $line['product_name'],
                    'variant_name_snapshot' => $line['variant_name'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'discount' => $line['discount'],
                    'tax' => $line['tax'],
                    'line_total' => $line['line_total'],
                    'metadata' => [
                        'tax_rate' => $line['tax_rate'],
                        'unit' => $variant->unit,
                        'pack_size' => $variant->pack_size,
                    ],
                ]);
            }

            // 5. Initialize payment with registered gateway
            $gateway = $this->paymentRegistry->get($paymentGateway);
            $gateway->createPayment($order, $options);

            // If COD or instant wallet payment, immediately confirm order
            if (in_array($paymentGateway, ['cod', 'wallet'], true)) {
                $order->status = 'confirmed';
                $order->payment_status = ($paymentGateway === 'wallet') ? 'captured' : 'pending';
                $order->save();
            }

            // Consume reservation immediately for confirmed orders (reservations remain held if pending_approval)
            if ($order->status === 'confirmed') {
                foreach ($reservations as $res) {
                    $this->inventoryService->consumeReservation($res);
                }

                if ($order->shipments()->count() === 0) {
                    $this->shipmentService->createShipmentForOrder($order);
                }
            }

            // 6. Generate Invoice
            Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => 'INV-'.date('Ymd').'-'.strtoupper(Str::random(6)),
                'amount' => $order->grand_total,
                'status' => ($order->status === 'confirmed') ? 'issued' : 'pending',
                'issued_at' => now(),
            ]);

            // 7. Mark cart as converted
            $cart->update(['status' => 'converted']);

            // 8. Fire OrderPlaced event for plugins (Vendor Marketplace, Promotions)
            event(new OrderPlaced($order, [
                'applied_promotions' => $totals['applied_promotions'],
            ]));

            return $order->load(['items', 'payments', 'invoice']);
        });
    }
}
