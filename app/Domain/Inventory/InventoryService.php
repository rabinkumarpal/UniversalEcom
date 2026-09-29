<?php

namespace App\Domain\Inventory;

use App\Core\Events\StockAdjusted;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Check if total available stock across warehouses satisfies the requested quantity.
     */
    public function checkAvailability(ProductVariant $variant, int $quantity): bool
    {
        $available = (int) $variant->inventoryItems()->sum('available');

        return $available >= $quantity;
    }

    /**
     * Atomically reserve stock for an order/checkout using pessimistic row-locking.
     * Prevents race conditions and overselling under high concurrency.
     */
    public function reserveStock(
        ProductVariant $variant,
        int $quantity,
        ?int $orderId = null,
        ?string $cartToken = null,
        int $ttlMinutes = 30
    ): StockReservation {
        return DB::transaction(function () use ($variant, $quantity, $orderId, $cartToken, $ttlMinutes) {
            // Find inventory item with row-locking
            $item = InventoryItem::where('product_variant_id', $variant->id)
                ->where('available', '>=', $quantity)
                ->lockForUpdate()
                ->first();

            if (! $item || $item->available < $quantity) {
                throw new RuntimeException("Insufficient stock for SKU [{$variant->sku}]. Requested: {$quantity}, Available: ".($item->available ?? 0));
            }

            // Reserve stock
            $item->reserved += $quantity;
            $item->syncAvailable();

            // Record reservation
            $reservation = StockReservation::create([
                'inventory_item_id' => $item->id,
                'order_id' => $orderId,
                'cart_token' => $cartToken,
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);

            // Append-only movement ledger
            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'reservation',
                'quantity' => -$quantity,
                'reference_type' => StockReservation::class,
                'reference_id' => $reservation->id,
                'reason' => "Stock reserved for order #{$orderId}",
                'user_id' => auth()->id(),
            ]);

            event(new StockAdjusted($item, $movement));

            return $reservation;
        });
    }

    /**
     * Release a reservation (e.g. checkout abandoned, order cancelled).
     */
    public function releaseReservation(StockReservation $reservation): void
    {
        if ($reservation->status !== 'active') {
            return;
        }

        DB::transaction(function () use ($reservation) {
            $item = InventoryItem::where('id', $reservation->inventory_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item->reserved = max(0, $item->reserved - $reservation->quantity);
            $item->syncAvailable();

            $reservation->update(['status' => 'released']);

            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'release',
                'quantity' => $reservation->quantity,
                'reference_type' => StockReservation::class,
                'reference_id' => $reservation->id,
                'reason' => "Released reservation #{$reservation->id}",
                'user_id' => auth()->id(),
            ]);

            event(new StockAdjusted($item, $movement));
        });
    }

    /**
     * Consume a reservation upon successful order placement / packing.
     */
    public function consumeReservation(StockReservation $reservation): void
    {
        if ($reservation->status !== 'active') {
            return;
        }

        DB::transaction(function () use ($reservation) {
            $item = InventoryItem::where('id', $reservation->inventory_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item->reserved = max(0, $item->reserved - $reservation->quantity);
            $item->on_hand = max(0, $item->on_hand - $reservation->quantity);
            $item->syncAvailable();

            $reservation->update(['status' => 'consumed']);

            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'out',
                'quantity' => -$reservation->quantity,
                'reference_type' => StockReservation::class,
                'reference_id' => $reservation->id,
                'reason' => "Stock fulfilled for order #{$reservation->order_id}",
                'user_id' => auth()->id(),
            ]);

            event(new StockAdjusted($item, $movement));
        });
    }

    /**
     * Manual inventory adjustment by admin or warehouse operator.
     */
    public function adjustStock(
        InventoryItem $item,
        int $quantityChange,
        string $type = 'adjustment',
        ?string $reason = null,
        ?int $userId = null
    ): InventoryMovement {
        return DB::transaction(function () use ($item, $quantityChange, $type, $reason, $userId) {
            $lockedItem = InventoryItem::where('id', $item->id)->lockForUpdate()->firstOrFail();

            $lockedItem->on_hand = max(0, $lockedItem->on_hand + $quantityChange);
            $lockedItem->syncAvailable();

            $movement = InventoryMovement::create([
                'inventory_item_id' => $lockedItem->id,
                'type' => $type,
                'quantity' => $quantityChange,
                'reason' => $reason ?? 'Manual stock adjustment',
                'user_id' => $userId ?? auth()->id(),
            ]);

            event(new StockAdjusted($lockedItem, $movement));

            return $movement;
        });
    }

    /**
     * Atomically transfer stock from one warehouse depot to another.
     * Prevents race conditions and guarantees balanced double-entry inventory ledger movements.
     *
     * @return array{source_movement: InventoryMovement, destination_movement: InventoryMovement}
     */
    public function transferStock(
        InventoryItem $sourceItem,
        Warehouse $destinationWarehouse,
        int $quantity,
        ?string $reason = null,
        ?int $userId = null
    ): array {
        if ($quantity <= 0) {
            throw new RuntimeException('Transfer quantity must be greater than zero.');
        }

        if ($sourceItem->warehouse_id === $destinationWarehouse->id) {
            throw new RuntimeException('Cannot transfer stock to the same warehouse depot.');
        }

        return DB::transaction(function () use ($sourceItem, $destinationWarehouse, $quantity, $reason, $userId) {
            $lockedSource = InventoryItem::where('id', $sourceItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSource->available < $quantity) {
                throw new RuntimeException("Insufficient available stock in {$lockedSource->warehouse?->name} ({$lockedSource->available} available, {$quantity} requested).");
            }

            // Find or create destination inventory item with pessimistic lock
            $destItem = InventoryItem::firstOrCreate(
                [
                    'warehouse_id' => $destinationWarehouse->id,
                    'product_variant_id' => $lockedSource->product_variant_id,
                ],
                [
                    'on_hand' => 0,
                    'reserved' => 0,
                    'available' => 0,
                    'reorder_level' => $lockedSource->reorder_level,
                ]
            );

            $lockedDest = InventoryItem::where('id', $destItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Deduct from source depot
            $lockedSource->on_hand -= $quantity;
            $lockedSource->syncAvailable();

            // Add to destination depot
            $lockedDest->on_hand += $quantity;
            $lockedDest->syncAvailable();

            $sourceMovement = InventoryMovement::create([
                'inventory_item_id' => $lockedSource->id,
                'type' => 'transfer_out',
                'quantity' => -$quantity,
                'reference_type' => Warehouse::class,
                'reference_id' => $destinationWarehouse->id,
                'reason' => "Transfer out to {$destinationWarehouse->name} ({$destinationWarehouse->code}): ".($reason ?? 'Inter-warehouse rebalancing'),
                'user_id' => $userId ?? auth()->id(),
            ]);

            $destMovement = InventoryMovement::create([
                'inventory_item_id' => $lockedDest->id,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'reference_type' => Warehouse::class,
                'reference_id' => $lockedSource->warehouse_id,
                'reason' => "Transfer in from {$lockedSource->warehouse?->name} ({$lockedSource->warehouse?->code}): ".($reason ?? 'Inter-warehouse rebalancing'),
                'user_id' => $userId ?? auth()->id(),
            ]);

            event(new StockAdjusted($lockedSource, $sourceMovement));
            event(new StockAdjusted($lockedDest, $destMovement));

            return [
                'source_movement' => $sourceMovement,
                'destination_movement' => $destMovement,
            ];
        });
    }
}
