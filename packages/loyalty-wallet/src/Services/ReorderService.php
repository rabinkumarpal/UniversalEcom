<?php

namespace Packages\LoyaltyWallet\Services;

use App\Domain\Cart\CartService;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;

class ReorderService
{
    public function __construct(protected CartService $cartService) {}

    /**
     * Re-populate a user's active cart with items from a previous order.
     *
     * @return array{added: array, skipped: array, cart: Cart}
     */
    public function reorder(Order $order, ?User $user = null, ?string $sessionToken = null): array
    {
        $cart = $this->cartService->getOrCreateCart($user ?? $order->user, $sessionToken);

        $added = [];
        $skipped = [];

        foreach ($order->items as $orderItem) {
            $variant = ProductVariant::find($orderItem->product_variant_id);

            if (! $variant || $variant->status !== 'active') {
                $skipped[] = [
                    'sku' => $orderItem->sku_snapshot,
                    'name' => $orderItem->product_name_snapshot,
                    'reason' => 'Product variant no longer active or available.',
                ];

                continue;
            }

            $availableStock = InventoryItem::where('product_variant_id', $variant->id)->sum('available');

            if ($availableStock <= 0) {
                $skipped[] = [
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'reason' => 'Currently out of stock.',
                ];

                continue;
            }

            $qtyToAdd = min($orderItem->quantity, $availableStock);
            $this->cartService->addItem($cart, $variant->id, $qtyToAdd);

            $added[] = [
                'sku' => $variant->sku,
                'name' => $orderItem->product_name_snapshot,
                'quantity' => $qtyToAdd,
            ];
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
            'cart' => $cart->refresh(),
        ];
    }
}
