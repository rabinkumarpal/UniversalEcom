<?php

namespace Packages\PromotionEngine\Services;

use App\Core\Contracts\PricingAdjustmentContract;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Order;
use Illuminate\Support\Str;
use Packages\PromotionEngine\Models\Coupon;
use Packages\PromotionEngine\Models\Promotion;
use Packages\PromotionEngine\Models\PromotionReward;
use Packages\PromotionEngine\Models\PromotionUsage;

class PromotionCalculationService implements PricingAdjustmentContract
{
    public function id(): string
    {
        return 'promotion-engine';
    }

    public function priority(): int
    {
        return 10;
    }

    /**
     * Authoritative calculation of applicable promotions and coupons.
     *
     * Returns:
     *   discount_total       int    — total paise off the subtotal
     *   free_shipping        bool   — whether delivery fee should be waived
     *   line_adjustments     array  — per-variant discount amounts
     *   applied_promotions   array  — list of applied promotion summaries
     *   scarcity_signals     array  — per-variant "Only N left" badges (stock_scarcity type only)
     *   spending_goal        array|null — spending goal progress data for UI widget
     */
    public function calculateCartAdjustments(Cart $cart, array $context = []): array
    {
        $discountTotal = 0;
        $freeShipping = false;
        $lineAdjustments = [];
        $appliedPromotions = [];
        $scarcitySignals = [];
        $spendingGoal = null;

        $lines = $context['current_lines'] ?? [];
        $couponCode = $context['options']['coupon_code'] ?? request()->input('coupon_code');
        $subtotal = $cart->raw_subtotal ?? 0;

        // Build a quick lookup: variant_id → quantity in cart
        $cartVariantQtys = [];
        foreach ($lines as $variantId => $line) {
            $cartVariantQtys[(int) $variantId] = (int) ($line['quantity'] ?? 0);
        }

        $activePromotions = Promotion::where('status', 'active')
            ->orderBy('priority')
            ->with(['bundleItems', 'mixMatchItems'])
            ->get()
            ->filter(fn (Promotion $p) => $p->isValidNow());

        foreach ($activePromotions as $promo) {
            // Coupon-gated promotions: only evaluate when matching code is provided
            if ($promo->code && strtoupper($promo->code) !== strtoupper((string) $couponCode)) {
                continue;
            }

            $applied = false;
            $promoDiscount = 0;

            switch ($promo->type) {

                // ------------------------------------------------------------------
                // Percentage off entire cart
                // ------------------------------------------------------------------
                case 'percentage':
                    $percent = (float) ($promo->configuration['discount_percentage'] ?? 0);
                    if ($percent > 0) {
                        foreach ($lines as $variantId => $line) {
                            $itemDiscount = (int) round($line['base_total'] * ($percent / 100.0));
                            $lineAdjustments[$variantId] = ($lineAdjustments[$variantId] ?? 0) + $itemDiscount;
                            $promoDiscount += $itemDiscount;
                        }
                        $applied = true;
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Fixed amount off cart total
                    // ------------------------------------------------------------------
                case 'fixed':
                    $amount = (int) ($promo->configuration['fixed_amount'] ?? 0);
                    if ($amount > 0) {
                        $promoDiscount = min($subtotal, $amount);
                        $applied = true;
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Spending goal — unlock reward when cart reaches a threshold
                    // ------------------------------------------------------------------
                case 'spending_goal':
                    $goal = (int) ($promo->configuration['goal_amount'] ?? 0);
                    $rewardType = $promo->configuration['reward_type'] ?? 'free_shipping';

                    // Always provide the progress widget data for the cart UI
                    $spendingGoal = [
                        'promotion_id' => $promo->id,
                        'promotion_name' => $promo->name,
                        'goal_amount' => $goal,
                        'current_amount' => $subtotal,
                        'remaining' => max(0, $goal - $subtotal),
                        'unlocked' => $subtotal >= $goal,
                        'reward_type' => $rewardType,
                        'reward_label' => $rewardType === 'free_shipping' ? 'FREE DELIVERY' : ('₹'.number_format(($promo->configuration['reward_amount'] ?? 0) / 100, 2).' OFF'),
                    ];

                    if ($goal > 0 && $subtotal >= $goal) {
                        if ($rewardType === 'free_shipping') {
                            $freeShipping = true;
                            $applied = true;
                        } elseif ($rewardType === 'fixed_discount') {
                            $promoDiscount += (int) ($promo->configuration['reward_amount'] ?? 0);
                            $applied = true;
                        }
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Unconditional free shipping
                    // ------------------------------------------------------------------
                case 'free_shipping':
                    $freeShipping = true;
                    $applied = true;
                    break;

                    // ------------------------------------------------------------------
                    // Buy X Get Y (same or different product — free or % off)
                    // ------------------------------------------------------------------
                case 'bogo':
                    $buyQty = (int) ($promo->configuration['buy_quantity'] ?? 1);
                    $getQty = (int) ($promo->configuration['get_quantity'] ?? 1);
                    foreach ($lines as $variantId => $line) {
                        if ($line['quantity'] >= $buyQty) {
                            $freeCount = (int) floor($line['quantity'] / ($buyQty + $getQty)) * $getQty;
                            if ($freeCount > 0) {
                                $itemDiscount = $freeCount * $line['unit_price'];
                                $lineAdjustments[$variantId] = ($lineAdjustments[$variantId] ?? 0) + $itemDiscount;
                                $promoDiscount += $itemDiscount;
                                $applied = true;
                            }
                        }
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Bundle — all required variant+qty must be present in cart
                    // ------------------------------------------------------------------
                case 'bundle':
                    $bundleItems = $promo->bundleItems;
                    $allPresent = true;

                    foreach ($bundleItems as $bundleItem) {
                        $cartQty = $cartVariantQtys[$bundleItem->product_variant_id] ?? 0;
                        if ($cartQty < $bundleItem->required_quantity) {
                            $allPresent = false;
                            break;
                        }
                    }

                    if ($allPresent && $bundleItems->isNotEmpty()) {
                        $percent = (float) ($promo->configuration['discount_percentage'] ?? 0);
                        $fixed = (int) ($promo->configuration['fixed_amount'] ?? 0);

                        if ($percent > 0) {
                            // Apply percentage to the bundle item lines only
                            foreach ($bundleItems as $bundleItem) {
                                $vid = $bundleItem->product_variant_id;
                                if (isset($lines[$vid])) {
                                    $itemDiscount = (int) round($lines[$vid]['base_total'] * ($percent / 100.0));
                                    $lineAdjustments[$vid] = ($lineAdjustments[$vid] ?? 0) + $itemDiscount;
                                    $promoDiscount += $itemDiscount;
                                }
                            }
                            $applied = true;
                        } elseif ($fixed > 0) {
                            $promoDiscount = min($subtotal, $fixed);
                            $applied = true;
                        }
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Mix & Match — choose ≥ min_quantity items from the eligible pool
                    // ------------------------------------------------------------------
                case 'mix_match':
                    $minQty = (int) ($promo->configuration['min_quantity'] ?? 1);
                    $eligibleIds = $promo->mixMatchItems->pluck('product_variant_id')->map(fn ($id) => (int) $id)->toArray();
                    $matchQty = 0;

                    foreach ($eligibleIds as $vid) {
                        $matchQty += $cartVariantQtys[$vid] ?? 0;
                    }

                    if ($matchQty >= $minQty && ! empty($eligibleIds)) {
                        $percent = (float) ($promo->configuration['discount_percentage'] ?? 0);
                        $fixed = (int) ($promo->configuration['fixed_amount'] ?? 0);

                        if ($percent > 0) {
                            foreach ($eligibleIds as $vid) {
                                if (isset($lines[$vid])) {
                                    $itemDiscount = (int) round($lines[$vid]['base_total'] * ($percent / 100.0));
                                    $lineAdjustments[$vid] = ($lineAdjustments[$vid] ?? 0) + $itemDiscount;
                                    $promoDiscount += $itemDiscount;
                                }
                            }
                            $applied = true;
                        } elseif ($fixed > 0) {
                            $promoDiscount = min($subtotal, $fixed);
                            $applied = true;
                        }
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Buy X Get Y Bundle — multi-line buy triggers a reward line
                    // configuration: {
                    //   qualifying_lines: [{variant_id, quantity}, ...],
                    //   reward_variant_id: int,
                    //   reward_quantity: int
                    // }
                    // ------------------------------------------------------------------
                case 'buy_x_get_y_bundle':
                    $qualifyingLines = $promo->configuration['qualifying_lines'] ?? [];
                    $rewardVariantId = (int) ($promo->configuration['reward_variant_id'] ?? 0);
                    $rewardQty = (int) ($promo->configuration['reward_quantity'] ?? 1);
                    $allQualify = true;

                    foreach ($qualifyingLines as $ql) {
                        $vid = (int) ($ql['variant_id'] ?? 0);
                        $reqQty = (int) ($ql['quantity'] ?? 1);
                        if (($cartVariantQtys[$vid] ?? 0) < $reqQty) {
                            $allQualify = false;
                            break;
                        }
                    }

                    if ($allQualify && ! empty($qualifyingLines)) {
                        // The reward variant is free — zero out its cost if it's in the cart
                        if ($rewardVariantId && isset($lines[$rewardVariantId])) {
                            $freeItemDiscount = min($lines[$rewardVariantId]['unit_price'] * $rewardQty, $lines[$rewardVariantId]['base_total']);
                            $lineAdjustments[$rewardVariantId] = ($lineAdjustments[$rewardVariantId] ?? 0) + $freeItemDiscount;
                            $promoDiscount += $freeItemDiscount;
                        }
                        $applied = true;
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Countdown — time-limited campaign (same math as percentage/fixed)
                    // isValidNow() already enforces the window; we just add display hint
                    // ------------------------------------------------------------------
                case 'countdown':
                    $percent = (float) ($promo->configuration['discount_percentage'] ?? 0);
                    $fixed = (int) ($promo->configuration['fixed_amount'] ?? 0);

                    if ($percent > 0) {
                        foreach ($lines as $variantId => $line) {
                            $itemDiscount = (int) round($line['base_total'] * ($percent / 100.0));
                            $lineAdjustments[$variantId] = ($lineAdjustments[$variantId] ?? 0) + $itemDiscount;
                            $promoDiscount += $itemDiscount;
                        }
                        $applied = true;
                    } elseif ($fixed > 0) {
                        $promoDiscount = min($subtotal, $fixed);
                        $applied = true;
                    }
                    break;

                    // ------------------------------------------------------------------
                    // Stock scarcity — signals only, no discount
                    // configuration: {threshold: 10, eligible_variant_ids: [1,2,3]}
                    // ------------------------------------------------------------------
                case 'stock_scarcity':
                    $threshold = (int) ($promo->configuration['threshold'] ?? 10);
                    $eligibleIds = $promo->configuration['eligible_variant_ids'] ?? [];

                    $query = InventoryItem::query();
                    if (! empty($eligibleIds)) {
                        $query->whereIn('product_variant_id', $eligibleIds);
                    }

                    $lowStockItems = $query->where('available', '<=', $threshold)
                        ->where('available', '>', 0)
                        ->get(['product_variant_id', 'available']);

                    foreach ($lowStockItems as $stockItem) {
                        $scarcitySignals[$stockItem->product_variant_id] = [
                            'available' => $stockItem->available,
                            'label' => "Only {$stockItem->available} left",
                        ];
                    }
                    // No $applied = true: scarcity is a signal, not a discount
                    break;
            }

            if ($applied) {
                $discountTotal += $promoDiscount;
                $appliedPromotions[] = [
                    'promotion_id' => $promo->id,
                    'name' => $promo->name,
                    'type' => $promo->type,
                    'discount_amount' => $promoDiscount,
                    'free_shipping' => $freeShipping,
                    'ends_at' => $promo->ends_at?->toIso8601String(),
                ];

                // Non-stackable: stop after first applied promotion
                if (! $promo->stackable) {
                    break;
                }
            }
        }

        // Evaluate explicit coupon code (separate pass — avoids double-applying)
        if ($couponCode) {
            $coupon = Coupon::with('promotion')->where('code', strtoupper($couponCode))->first();
            if ($coupon && $coupon->isValid($cart->user)) {
                $alreadyApplied = collect($appliedPromotions)->contains('promotion_id', $coupon->promotion_id);
                if (! $alreadyApplied && $coupon->promotion) {
                    $promo = $coupon->promotion;
                    $couponDiscount = 0;
                    if ($promo->type === 'percentage') {
                        $percent = (float) ($promo->configuration['discount_percentage'] ?? 0);
                        $couponDiscount = (int) round($subtotal * ($percent / 100.0));
                    } elseif ($promo->type === 'fixed') {
                        $couponDiscount = (int) ($promo->configuration['fixed_amount'] ?? 0);
                    }

                    if ($couponDiscount > 0) {
                        $discountTotal += $couponDiscount;
                        $appliedPromotions[] = [
                            'promotion_id' => $promo->id,
                            'coupon_code' => $coupon->code,
                            'name' => "Coupon: {$coupon->code}",
                            'type' => 'coupon',
                            'discount_amount' => $couponDiscount,
                            'free_shipping' => false,
                            'ends_at' => null,
                        ];
                    }
                }
            }
        }

        return [
            'discount_total' => $discountTotal,
            'free_shipping' => $freeShipping,
            'line_adjustments' => $lineAdjustments,
            'applied_promotions' => $appliedPromotions,
            'scarcity_signals' => $scarcitySignals,
            'spending_goal' => $spendingGoal,
        ];
    }

    /**
     * Record applied promotions and trigger rewards on completed order.
     */
    public function recordOrderApplication(Order $order, array $adjustmentData): void
    {
        $applied = $adjustmentData['applied_promotions'] ?? [];

        foreach ($applied as $data) {
            if (empty($data['promotion_id'])) {
                continue;
            }

            $promotion = Promotion::find($data['promotion_id']);
            if (! $promotion) {
                continue;
            }

            // Record usage
            PromotionUsage::create([
                'promotion_id' => $promotion->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $data['discount_amount'] ?? 0,
            ]);

            // Increment coupon uses_count
            if (! empty($data['coupon_code'])) {
                Coupon::where('code', $data['coupon_code'])->increment('uses_count');
            }

            // Generate next-order coupon reward if configured
            if (! empty($promotion->configuration['generate_next_order_coupon'])) {
                $rewardConfig = $promotion->configuration['generate_next_order_coupon'];
                $couponCode = 'NEXT-'.strtoupper(Str::random(6));

                $nextPromo = Promotion::create([
                    'name' => "Next Order Reward ({$couponCode})",
                    'slug' => 'next-order-'.strtolower($couponCode),
                    'type' => $rewardConfig['type'] ?? 'fixed',
                    'status' => 'active',
                    'priority' => 5,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($rewardConfig['valid_days'] ?? 30),
                    'usage_limit' => 1,
                    'configuration' => [
                        'fixed_amount' => $rewardConfig['amount'] ?? 5000,
                        'discount_percentage' => $rewardConfig['percentage'] ?? 0,
                    ],
                ]);

                $rewardCoupon = Coupon::create([
                    'code' => $couponCode,
                    'promotion_id' => $nextPromo->id,
                    'user_id' => $order->user_id,
                    'max_uses' => 1,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($rewardConfig['valid_days'] ?? 30),
                    'is_active' => true,
                ]);

                PromotionReward::create([
                    'promotion_id' => $promotion->id,
                    'user_id' => $order->user_id,
                    'reward_type' => 'next_order_coupon',
                    'reward_payload' => ['code' => $couponCode, 'coupon_id' => $rewardCoupon->id],
                    'status' => 'active',
                    'expires_at' => $rewardCoupon->ends_at,
                ]);
            }
        }
    }
}
