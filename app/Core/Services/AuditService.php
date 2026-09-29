<?php

namespace App\Core\Services;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Centralized audit logging service.
 *
 * Captures actor (auth user), IP address, entity reference, and before/after
 * state for every security-sensitive action on the platform.
 */
class AuditService
{
    /**
     * Record a generic audit entry.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function log(
        string $action,
        Model $entity,
        array $old = [],
        array $new = [],
        ?int $actorId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Audit an order status transition.
     */
    public function orderTransition(Order $order, string $oldStatus, string $newStatus, ?string $note = null): AuditLog
    {
        return $this->log(
            'order.transition',
            $order,
            ['status' => $oldStatus],
            ['status' => $newStatus, 'note' => $note],
        );
    }

    /**
     * Audit a refund issued against an order.
     */
    public function refundIssued(Order $order, int $amount, string $reason): AuditLog
    {
        return $this->log(
            'refund.issued',
            $order,
            ['payment_status' => $order->payment_status],
            ['refund_amount' => $amount, 'reason' => $reason],
        );
    }

    /**
     * Audit a customer or staff-initiated order cancellation.
     */
    public function orderCancelled(Order $order, string $reason, ?int $actorId = null): AuditLog
    {
        return $this->log(
            'order.cancelled',
            $order,
            ['status' => $order->status],
            ['reason' => $reason],
            $actorId,
        );
    }

    /**
     * Audit a stock adjustment.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function stockAdjusted(InventoryItem $item, array $old, array $new): AuditLog
    {
        return $this->log('stock.adjusted', $item, $old, $new);
    }

    /**
     * Audit a product catalog creation.
     */
    public function productCreated(Model $product, array $data = []): AuditLog
    {
        return $this->log('product.created', $product, [], $data);
    }

    /**
     * Audit a price change on a product variant.
     */
    public function priceChanged(ProductVariant $variant, array $old, array $new): AuditLog
    {
        return $this->log('price.changed', $variant, $old, $new);
    }

    /**
     * Audit a platform settings change.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function settingChanged(array $old, array $new): AuditLog
    {
        // Settings are not a single DB entity — use a virtual model-like approach
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'setting.changed',
            'entity_type' => 'SystemSetting',
            'entity_id' => 0,
            'old_values' => $old ?: null,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Audit a user role assignment or removal.
     */
    public function roleChanged(User $target, array $old, array $new): AuditLog
    {
        return $this->log('role.changed', $target, $old, $new);
    }
}
