<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\AuditService;
use App\Domain\Cart\CartService;
use App\Domain\Orders\OrderStateMachine;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        protected OrderStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)
            ->with(['items', 'payments', 'invoice'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with(['items', 'payments', 'invoice', 'statusHistory'])
            ->firstOrFail();

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->stateMachine->transitionTo($order, 'cancelled', $user->id, 'Cancelled by customer.');
        $this->audit->orderCancelled($order, 'Cancelled by customer.', $user->id);

        return response()->json([
            'data' => new OrderResource($order->fresh()),
            'meta' => ['message' => 'Order cancelled successfully.'],
        ]);
    }

    public function reorder(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with('items')
            ->firstOrFail();

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart(
            $user,
            $request->header('X-Session-Token') ?? $request->input('session_token')
        );

        $addedCount = 0;
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                $cartService->addItem($cart, $item->product_variant_id, $item->quantity);
                $addedCount++;
            }
        }

        return response()->json([
            'data' => new CartResource($cart->fresh()),
            'meta' => [
                'message' => "Successfully repopulated cart with {$addedCount} items from order #{$orderNumber}.",
            ],
        ]);
    }
}
