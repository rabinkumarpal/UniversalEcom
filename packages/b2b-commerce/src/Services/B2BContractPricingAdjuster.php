<?php

namespace Packages\B2BCommerce\Services;

use App\Core\Contracts\PricingAdjustmentContract;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Models\ContractPriceList;

class B2BContractPricingAdjuster implements PricingAdjustmentContract
{
    public function id(): string
    {
        return 'b2b-contract-pricing';
    }

    /**
     * Executes before general promotion engine (priority 10).
     */
    public function priority(): int
    {
        return 5;
    }

    public function calculateCartAdjustments(Cart $cart, array $context = []): array
    {
        $discountTotal = 0;
        $lineAdjustments = [];
        $appliedPromotions = [];

        $user = $cart->user ?? (auth()->check() ? auth()->user() : ($cart->user_id ? User::find($cart->user_id) : null));

        if (! $user) {
            return [
                'discount_total' => 0,
                'free_shipping' => false,
                'line_adjustments' => [],
                'applied_promotions' => [],
            ];
        }

        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('company.customerGroup')
            ->first();

        if (! $companyUser || ! $companyUser->company || ! $companyUser->company->isActive()) {
            return [
                'discount_total' => 0,
                'free_shipping' => false,
                'line_adjustments' => [],
                'applied_promotions' => [],
            ];
        }

        $company = $companyUser->company;
        $lines = $context['current_lines'] ?? [];

        // 1. Check for Company-specific Contract Price List
        $priceList = ContractPriceList::active()
            ->where('company_id', $company->id)
            ->with('variantPrices')
            ->first();

        // 2. Fallback to Customer Group Price List
        if (! $priceList && $company->customer_group_id) {
            $priceList = ContractPriceList::active()
                ->where('customer_group_id', $company->customer_group_id)
                ->with('variantPrices')
                ->first();
        }

        foreach ($lines as $variantId => $line) {
            $vid = (int) ($line['variant_id'] ?? $variantId);
            $qty = (int) ($line['quantity'] ?? 1);
            $unitPrice = (int) ($line['unit_price'] ?? 0);
            $baseTotal = (int) ($line['base_total'] ?? ($unitPrice * $qty));

            $matchedContractItem = null;

            if ($priceList) {
                $matchedContractItem = $priceList->variantPrices
                    ->where('product_variant_id', $vid)
                    ->where('min_quantity', '<=', $qty)
                    ->sortByDesc('min_quantity')
                    ->first();
            }

            if ($matchedContractItem && $matchedContractItem->custom_price < $unitPrice) {
                $unitDiscount = $unitPrice - $matchedContractItem->custom_price;
                $lineDiscount = $unitDiscount * $qty;
                $lineAdjustments[$vid] = $lineDiscount;
                $discountTotal += $lineDiscount;

                $appliedPromotions[] = [
                    'type' => 'b2b_contract_pricing',
                    'name' => "Negotiated Contract Price ({$company->company_code})",
                    'variant_id' => $vid,
                    'original_unit_price' => $unitPrice,
                    'contract_unit_price' => $matchedContractItem->custom_price,
                    'saved' => $lineDiscount,
                ];
            } elseif ($company->customerGroup && $company->customerGroup->discount_percentage > 0) {
                $percentage = (float) $company->customerGroup->discount_percentage;
                $lineDiscount = (int) round($baseTotal * ($percentage / 100));

                if ($lineDiscount > 0) {
                    $lineAdjustments[$vid] = $lineDiscount;
                    $discountTotal += $lineDiscount;

                    $appliedPromotions[] = [
                        'type' => 'b2b_group_discount',
                        'name' => "Customer Group ({$company->customerGroup->name} - {$percentage}%)",
                        'variant_id' => $vid,
                        'saved' => $lineDiscount,
                    ];
                }
            }
        }

        return [
            'discount_total' => $discountTotal,
            'free_shipping' => false,
            'line_adjustments' => $lineAdjustments,
            'applied_promotions' => $appliedPromotions,
        ];
    }

    public function recordOrderApplication(Order $order, array $adjustmentData): void
    {
        // Audit metadata preserved via order_items.metadata and purchase_orders table
    }
}
