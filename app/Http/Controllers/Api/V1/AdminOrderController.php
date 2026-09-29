<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\AuditService;
use App\Domain\Orders\OrderStateMachine;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminOrderController extends Controller
{
    public function __construct(
        protected OrderStateMachine $stateMachine,
        protected AuditService $audit
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::with(['items', 'payments', 'invoice', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items', 'payments', 'invoice', 'user', 'statusHistory'])
            ->firstOrFail();

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function transition(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'note' => 'nullable|string|max:500',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $oldStatus = $order->status;

        $this->stateMachine->transitionTo(
            $order,
            $validated['status'],
            $request->user()?->id,
            $validated['note'] ?? null
        );

        $this->audit->orderTransition($order, $oldStatus, $validated['status'], $validated['note'] ?? null);

        return response()->json([
            'data' => new OrderResource($order->fresh(['statusHistory'])),
            'meta' => ['message' => "Order transitioned to {$validated['status']}."],
        ]);
    }

    public function refund(Request $request, string $orderNumber): JsonResponse
    {
        $this->authorize('refunds.create');

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'amount' => 'nullable|integer',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $refundAmount = $validated['amount'] ?? $order->grand_total;

        Payment::create([
            'order_id' => $order->id,
            'gateway' => $order->payments->first()?->gateway ?? 'manual',
            'transaction_id' => 'ref_'.bin2hex(random_bytes(8)),
            'amount' => -$refundAmount,
            'currency' => $order->currency ?? 'INR',
            'status' => 'refunded',
            'payload' => ['reason' => $validated['reason']],
        ]);

        $order->update(['payment_status' => 'refunded']);
        $this->stateMachine->transitionTo($order, 'refunded', $request->user()?->id, $validated['reason']);

        $this->audit->refundIssued($order, $refundAmount, $validated['reason']);

        return response()->json([
            'data' => new OrderResource($order->fresh(['statusHistory', 'payments'])),
            'meta' => ['message' => "Order #{$orderNumber} refund processed successfully."],
        ]);
    }
}
