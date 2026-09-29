<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Domain\Delivery\DeliveryService;
use App\Domain\Pricing\PricingPipeline;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PricingPipeline $pricingPipeline
    ) {}

    /**
     * Provide an authoritative checkout quote before order creation.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'nullable|integer|exists:addresses,id',
            'pincode' => 'nullable|string',
            'coupon_code' => 'nullable|string',
        ]);

        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        $address = null;
        if (! empty($validated['address_id'])) {
            $address = Address::find($validated['address_id']);
        } elseif (! empty($validated['pincode'])) {
            $address = new Address(['pincode' => $validated['pincode']]);
        }

        $quote = $this->pricingPipeline->calculate($cart, $address, [
            'coupon_code' => $validated['coupon_code'] ?? null,
        ]);

        return response()->json([
            'data' => $quote,
        ]);
    }

    /**
     * Finalize checkout and create an authoritative order.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => 'required_without:shipping_address|integer|exists:addresses,id',
            'shipping_address' => 'required_without:shipping_address_id|array',
            'shipping_address.recipient_name' => 'required_with:shipping_address|string',
            'shipping_address.phone' => 'required_with:shipping_address|string',
            'shipping_address.address_line_1' => 'required_with:shipping_address|string',
            'shipping_address.city' => 'required_with:shipping_address|string',
            'shipping_address.state' => 'required_with:shipping_address|string',
            'shipping_address.pincode' => 'required_with:shipping_address|string',
            'payment_gateway' => 'nullable|string|in:cod,razorpay,stripe',
            'notes' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string',
        ]);

        $user = $request->user();
        $cart = $this->cartService->getOrCreateCart(
            $user,
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        if (! empty($validated['shipping_address_id'])) {
            $shippingAddress = Address::findOrFail($validated['shipping_address_id']);
        } else {
            $shippingAddress = Address::create(array_merge($validated['shipping_address'], [
                'user_id' => $user?->id ?? 1,
            ]));
        }

        $order = $this->checkoutService->checkout(
            $cart,
            $shippingAddress,
            $shippingAddress,
            $validated['payment_gateway'] ?? 'cod',
            $validated['notes'] ?? null,
            $user,
            ['coupon_code' => $validated['coupon_code'] ?? null]
        );

        return response()->json([
            'data' => new OrderResource($order),
            'meta' => [
                'message' => 'Order placed successfully.',
                'order_number' => $order->order_number,
            ],
        ], 201);
    }

    /**
     * Pre-validate checkout requirements without placing order.
     */
    public function validateCheckout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pincode' => 'nullable|string',
            'shipping_address_id' => 'nullable|integer|exists:addresses,id',
        ]);

        $cart = $this->cartService->getOrCreateCart(
            $request->user(),
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty.',
                'errors' => ['cart' => ['Cart contains no items to checkout.']],
            ], 422);
        }

        $pincode = $validated['pincode'] ?? null;
        if (! $pincode && ! empty($validated['shipping_address_id'])) {
            $address = Address::find($validated['shipping_address_id']);
            $pincode = $address?->pincode;
        }

        $deliveryService = app(DeliveryService::class);
        $isServiceable = $pincode ? $deliveryService->isServiceable($pincode) : true;

        // Check stock availability
        $outOfStockItems = [];
        foreach ($cart->items as $item) {
            $available = $item->variant->inventoryItems->sum('available');
            if ($available < $item->quantity) {
                $outOfStockItems[] = [
                    'sku' => $item->variant->sku,
                    'name' => $item->variant->name,
                    'requested' => $item->quantity,
                    'available' => $available,
                ];
            }
        }

        $isValid = $isServiceable && empty($outOfStockItems);

        return response()->json([
            'data' => [
                'is_valid' => $isValid,
                'is_serviceable' => $isServiceable,
                'pincode' => $pincode,
                'items_count' => $cart->items->count(),
                'out_of_stock_items' => $outOfStockItems,
            ],
            'meta' => [
                'status' => $isValid ? 'ready_to_checkout' : 'validation_failed',
            ],
        ], $isValid ? 200 : 422);
    }
}
