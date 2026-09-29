<?php

namespace App\Domain\Cart;

use App\Core\Events\CartCalculated;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pricing\PricingPipeline;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    public function __construct(
        protected PricingPipeline $pricingPipeline,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Resolve active cart for authenticated user or anonymous session token.
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionToken = null): Cart
    {
        if ($user) {
            // First check if user already has an active cart
            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            // If not, check if there is an active anonymous session cart to claim
            if (! $cart && $sessionToken) {
                $sessionCart = Cart::where('session_token', $sessionToken)
                    ->whereNull('user_id')
                    ->where('status', 'active')
                    ->first();

                if ($sessionCart) {
                    $sessionCart->user_id = $user->id;
                    $sessionCart->save();
                    $cart = $sessionCart;
                }
            }

            if (! $cart) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'session_token' => $sessionToken ?? Str::random(40),
                    'status' => 'active',
                ]);
            }
        } else {
            $token = $sessionToken ?? session()->getId() ?? Str::random(40);
            $cart = Cart::firstOrCreate(
                ['session_token' => $token, 'status' => 'active'],
                ['user_id' => null]
            );
        }

        return $cart->load(['items.variant.product', 'items.variant.quantityTiers', 'items.variant.taxClass']);
    }

    /**
     * Add variant to cart with stock validation.
     */
    public function addItem(Cart $cart, int $variantId, int $quantity = 1, array $options = []): CartItem
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $variant = ProductVariant::where('status', 'active')->findOrFail($variantId);

        // Find existing cart item or new
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $newTotalQty = ($item ? $item->quantity : 0) + $quantity;

        // Authoritative stock check
        if (! $this->inventoryService->checkAvailability($variant, $newTotalQty)) {
            throw new InvalidArgumentException("Requested quantity ({$newTotalQty}) exceeds available inventory for SKU {$variant->sku}.");
        }

        if ($item) {
            $item->quantity = $newTotalQty;
            $item->custom_options = $options ?: $item->custom_options;
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'custom_options' => $options ?: null,
            ]);
        }

        return $item;
    }

    /**
     * Update item quantity with stock validation.
     */
    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $item->delete();

            return $item;
        }

        if (! $this->inventoryService->checkAvailability($item->variant, $quantity)) {
            throw new InvalidArgumentException("Requested quantity ({$quantity}) exceeds available stock.");
        }

        $item->quantity = $quantity;
        $item->save();

        return $item;
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Compute authoritative cart summary.
     */
    public function getCartTotals(Cart $cart, ?Address $destination = null): array
    {
        $cart->load(['items.variant.product', 'items.variant.quantityTiers', 'items.variant.taxClass']);
        $totals = $this->pricingPipeline->calculate($cart, $destination);

        event(new CartCalculated($cart, $totals));

        return $totals;
    }
}
