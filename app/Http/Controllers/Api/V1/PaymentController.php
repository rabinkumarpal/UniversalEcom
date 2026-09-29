<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Orders\OrderStateMachine;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected OrderStateMachine $stateMachine
    ) {}

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|exists:orders,order_number',
            'gateway' => 'required|string|in:cod,razorpay,stripe,wallet',
        ]);

        $order = Order::where('order_number', $validated['order_number'])->firstOrFail();

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $validated['gateway'],
            'amount' => $order->grand_total,
            'currency' => $order->currency ?? 'INR',
            'status' => 'pending',
            'payload' => [
                'client_token' => 'tok_'.bin2hex(random_bytes(16)),
                'initiated_at' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'order_number' => $order->order_number,
                'gateway' => $payment->gateway,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'client_token' => $payment->payload['client_token'],
            ],
            'meta' => ['message' => 'Payment intent created successfully.'],
        ], 201);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $payment = Payment::with('order')->findOrFail($id);

        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'status' => 'nullable|string|in:captured,failed',
        ]);

        $status = $validated['status'] ?? 'captured';

        $payment->update([
            'transaction_id' => $validated['transaction_id'],
            'status' => $status,
        ]);

        if ($status === 'captured') {
            $payment->order->update(['payment_status' => 'captured']);
            $this->stateMachine->transitionTo($payment->order, 'paid', null, "Payment captured via {$payment->gateway} ({$validated['transaction_id']}).");
        }

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'order_number' => $payment->order->order_number,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
            ],
            'meta' => ['message' => 'Payment verification processed.'],
        ]);
    }

    public function webhook(Request $request, string $gateway): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Webhook-Signature') ?? $request->header('X-Signature');
        $webhookSecret = config('services.'.$gateway.'.webhook_secret', 'universal_ecommerce_webhook_secret');

        // Signature validation
        if ($signature) {
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
            if (! hash_equals($expectedSignature, $signature)) {
                return response()->json(['message' => 'Invalid webhook signature.'], 403);
            }
        }

        $data = $request->all();
        $transactionId = $data['transaction_id'] ?? $data['id'] ?? null;
        $orderNumber = $data['order_number'] ?? null;
        $event = $data['event'] ?? 'payment.captured';

        // Idempotency check: if transaction_id already recorded as captured, exit cleanly
        if ($transactionId) {
            $existingPayment = Payment::where('transaction_id', $transactionId)
                ->where('status', 'captured')
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'status' => 'idempotent_ok',
                    'message' => 'Event previously processed.',
                ], 200);
            }
        }

        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
            if ($order && $event === 'payment.captured') {
                $payment = Payment::firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'transaction_id' => $transactionId,
                    ],
                    [
                        'gateway' => $gateway,
                        'amount' => $order->grand_total,
                        'currency' => $order->currency ?? 'INR',
                        'status' => 'captured',
                        'payload' => $data,
                    ]
                );

                $payment->update(['status' => 'captured']);
                $order->update(['payment_status' => 'captured']);
                if ($order->status !== 'paid') {
                    $this->stateMachine->transitionTo($order, 'paid', null, "Webhook verified payment capture via {$gateway}.");
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received and processed.',
        ]);
    }
}
