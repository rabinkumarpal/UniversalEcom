<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Cart\CartService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Packages\PromotionEngine\Models\Coupon;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        return response()->json([
            'data' => new CartResource($cart),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'options' => 'nullable|array',
        ]);

        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        $this->cartService->addItem($cart, $validated['variant_id'], $validated['quantity'], $validated['options'] ?? []);

        return response()->json([
            'data' => new CartResource($cart->fresh()),
            'meta' => ['message' => 'Item added to cart successfully.'],
        ]);
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);
        $this->cartService->updateItem($item, $validated['quantity']);

        return response()->json([
            'data' => new CartResource($cart->fresh()),
            'meta' => ['message' => 'Cart updated successfully.'],
        ]);
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);
        $this->cartService->removeItem($item);

        return response()->json([
            'data' => new CartResource($cart->fresh()),
            'meta' => ['message' => 'Item removed from cart.'],
        ]);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $code = strtoupper(trim($validated['coupon_code']));

        $coupon = Coupon::with('promotion')->where('code', $code)->first();

        if (! $coupon || ! $coupon->isValid($request->user())) {
            throw ValidationException::withMessages([
                'coupon_code' => ["Coupon [{$code}] is invalid, expired, or unavailable."],
            ]);
        }

        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        return response()->json([
            'data' => [
                'coupon_code' => $code,
                'cart' => new CartResource($cart),
            ],
            'meta' => ['message' => "Coupon [{$code}] applied successfully."],
        ]);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        return response()->json([
            'data' => [
                'cart' => new CartResource($cart),
            ],
            'meta' => ['message' => 'Coupon removed successfully.'],
        ]);
    }
}
