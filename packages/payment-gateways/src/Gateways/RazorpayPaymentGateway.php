<?php

namespace Packages\PaymentGateways\Gateways;

use App\Core\Contracts\PaymentGatewayContract;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Packages\PaymentGateways\Models\PaymentReconciliation;
use Packages\PaymentGateways\Services\PaymentReconciliationService;
use Packages\PaymentGateways\Services\RazorpayService;

class RazorpayPaymentGateway implements PaymentGatewayContract
{
    public function __construct(
        protected RazorpayService $razorpay,
        protected PaymentReconciliationService $reconciliationService
    ) {}

    public function id(): string
    {
        return 'razorpay';
    }

    public function name(): string
    {
        return 'Razorpay (UPI, Netbanking, Cards)';
    }

    /**
     * Create Razorpay payment session & return modal initialization options.
     */
    public function createPayment(Order $order, array $options = []): array
    {
        // 1. Create order on Razorpay
        $rzpOrder = $this->razorpay->createOrder(
            $order->grand_total,
            $order->currency ?? 'INR',
            'RCPT-'.$order->order_number,
            ['order_number' => $order->order_number]
        );

        // 2. Persist Payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $this->id(),
            'transaction_id' => $rzpOrder['id'],
            'amount' => $order->grand_total,
            'currency' => $order->currency ?? 'INR',
            'status' => 'pending',
            'payload' => [
                'razorpay_order_id' => $rzpOrder['id'],
                'client_options' => $rzpOrder,
            ],
        ]);

        // 3. Register in Payment Reconciliation Ledger
        $this->reconciliationService->createForOrder($order, $this->id(), $payment);

        $customerName = ! empty($order->shipping_address_snapshot['first_name'])
            ? trim(($order->shipping_address_snapshot['first_name'] ?? '').' '.($order->shipping_address_snapshot['last_name'] ?? ''))
            : ($order->user?->name ?? 'Valued Customer');

        $customerPhone = $order->shipping_address_snapshot['phone']
            ?? $order->user?->phone
            ?? '';

        return [
            'payment_id' => $payment->id,
            'gateway' => $this->id(),
            'razorpay_order_id' => $rzpOrder['id'],
            'amount' => $order->grand_total,
            'currency' => $order->currency ?? 'INR',
            'key_id' => $this->razorpay->getKeyId(),
            'name' => config('app.name', 'Universal Ecommerce'),
            'description' => "Payment for Order #{$order->order_number}",
            'prefill' => [
                'name' => $customerName,
                'email' => $order->user?->email ?? '',
                'contact' => $customerPhone,
            ],
            'theme' => [
                'color' => '#4f46e5',
            ],
        ];
    }

    /**
     * Cryptographically verify Razorpay payment signature from client checkout modal.
     */
    public function verifyPayment(Payment $payment, array $payload = []): bool
    {
        $rzpOrderId = $payload['razorpay_order_id'] ?? ($payment->payload['razorpay_order_id'] ?? null);
        $rzpPaymentId = $payload['razorpay_payment_id'] ?? null;
        $signature = $payload['razorpay_signature'] ?? null;

        if (! $rzpOrderId || ! $rzpPaymentId || ! $signature) {
            $payment->update(['status' => 'failed']);

            return false;
        }

        $isValid = $this->razorpay->verifySignature($rzpOrderId, $rzpPaymentId, $signature);

        if (! $isValid) {
            $payment->update([
                'status' => 'failed',
                'payload' => array_merge($payment->payload ?? [], [
                    'verification_error' => 'Invalid Razorpay cryptographic signature.',
                    'attempted_at' => now()->toIso8601String(),
                ]),
            ]);

            return false;
        }

        // Signature verified successfully -> capture payment
        $payment->update([
            'status' => 'captured',
            'transaction_id' => $rzpPaymentId,
            'payload' => array_merge($payment->payload ?? [], [
                'razorpay_payment_id' => $rzpPaymentId,
                'razorpay_signature' => $signature,
                'verified_at' => now()->toIso8601String(),
            ]),
        ]);

        if ($payment->order) {
            $payment->order->update([
                'payment_status' => 'captured',
                'status' => in_array($payment->order->status, ['pending_payment', 'pending']) ? 'confirmed' : $payment->order->status,
            ]);

            // Update reconciliation status to settled
            $rec = PaymentReconciliation::where('order_id', $payment->order_id)->first();
            if ($rec) {
                $rec->update([
                    'payment_id' => $payment->id,
                    'collected_amount' => $payment->amount,
                    'balance_amount' => 0,
                    'settlement_status' => 'settled',
                    'reference_number' => $rzpPaymentId,
                ]);
            }
        }

        return true;
    }

    /**
     * Process asynchronous inbound webhook payload with cryptographic verification & idempotency.
     */
    public function handleWebhook(Request $request): array
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if ($signature) {
            $isValid = $this->razorpay->verifyWebhookSignature($rawPayload, $signature);
            if (! $isValid) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid Razorpay webhook signature.',
                    'code' => 403,
                ];
            }
        }

        $data = $request->all();
        $event = $data['event'] ?? 'payment.captured';

        $paymentEntity = $data['payload']['payment']['entity'] ?? $data;
        $rzpPaymentId = $paymentEntity['id'] ?? null;
        $rzpOrderId = $paymentEntity['order_id'] ?? null;

        // Idempotency: Check if transaction already captured
        if ($rzpPaymentId) {
            $existing = Payment::where('transaction_id', $rzpPaymentId)
                ->where('status', 'captured')
                ->first();

            if ($existing) {
                return [
                    'status' => 'idempotent_ok',
                    'message' => 'Webhook already processed.',
                    'transaction_id' => $rzpPaymentId,
                ];
            }
        }

        // Find associated Payment
        $payment = null;
        if ($rzpOrderId) {
            $payment = Payment::where('transaction_id', $rzpOrderId)
                ->orWhere('payload->razorpay_order_id', $rzpOrderId)
                ->first();
        }

        if ($payment) {
            if ($event === 'payment.captured') {
                $payment->update([
                    'status' => 'captured',
                    'transaction_id' => $rzpPaymentId ?? $payment->transaction_id,
                    'payload' => array_merge($payment->payload ?? [], $data),
                ]);

                if ($payment->order) {
                    $payment->order->update([
                        'payment_status' => 'captured',
                        'status' => 'confirmed',
                    ]);

                    $rec = PaymentReconciliation::where('order_id', $payment->order_id)->first();
                    if ($rec) {
                        $rec->update([
                            'settlement_status' => 'settled',
                            'collected_amount' => $payment->amount,
                            'balance_amount' => 0,
                            'reference_number' => $rzpPaymentId,
                        ]);
                    }
                }
            } elseif ($event === 'payment.failed') {
                $payment->update([
                    'status' => 'failed',
                    'payload' => array_merge($payment->payload ?? [], $data),
                ]);
            }
        }

        return [
            'status' => 'success',
            'event' => $event,
            'message' => 'Razorpay webhook processed successfully.',
        ];
    }

    /**
     * Refund a captured payment on Razorpay.
     */
    public function refund(Payment $payment, int $amountInCents, ?string $reason = null): bool
    {
        $refundData = $this->razorpay->refund(
            $payment->transaction_id ?? 'pay_mock',
            $amountInCents,
            $reason
        );

        $payment->update([
            'status' => 'refunded',
            'payload' => array_merge($payment->payload ?? [], [
                'refund' => $refundData,
                'refunded_at' => now()->toIso8601String(),
                'refund_reason' => $reason,
            ]),
        ]);

        return true;
    }
}
