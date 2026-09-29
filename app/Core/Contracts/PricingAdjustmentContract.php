<?php

namespace App\Core\Contracts;

use App\Models\Cart;
use App\Models\Order;

interface PricingAdjustmentContract
{
    /**
     * Identifier for this adjustment provider (e.g. 'promotion-engine', 'coupon', 'wallet').
     */
    public function id(): string;

    /**
     * Priority of execution (lower numbers execute first).
     */
    public function priority(): int;

    /**
     * Calculate line-level and total adjustments for a cart.
     * Returns an array with:
     * - 'discount_total' (int in cents)
     * - 'free_shipping' (bool)
     * - 'line_adjustments' (array of variant_id => discount_amount)
     * - 'applied_promotions' (array of metadata snapshots for order audit)
     */
    public function calculateCartAdjustments(Cart $cart, array $context = []): array;

    /**
     * Commit adjustments when an order is officially placed.
     */
    public function recordOrderApplication(Order $order, array $adjustmentData): void;
}
