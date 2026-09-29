<?php

namespace Packages\PaymentGateways\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\PaymentGateways\Gateways\RazorpayPaymentGateway;

class RazorpayCheckoutController extends Controller
{
    public function __construct(
        protected RazorpayPaymentGateway $gateway
    ) {}

    /**
     * Initiate Razorpay checkout order for customer.
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|exists:orders,order_number',
        ]);

        $order = Order::where('order_number', $validated['order_number'])->firstOrFail();
        $payload = $this->gateway->createPayment($order);

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    /**
     * Verify payment signature received from client-side Razorpay modal.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);
        $verified = $this->gateway->verifyPayment($payment, $validated);

        if (! $verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cryptographic verification failed for Razorpay transaction.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment successfully captured and verified.',
            'order_number' => $payment->order?->order_number,
        ]);
    }

    /**
     * Inbound webhook handler for asynchronous Razorpay capture callbacks.
     */
    public function webhook(Request $request): JsonResponse
    {
        $result = $this->gateway->handleWebhook($request);
        $code = $result['code'] ?? 200;

        return response()->json($result, $code);
    }
}
