<?php

namespace App\Domain\Payments;

use App\Core\Contracts\PaymentGatewayContract;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class CashOnDeliveryGateway implements PaymentGatewayContract
{
    public function id(): string
    {
        return 'cod';
    }

    public function name(): string
    {
        return 'Cash on Delivery (Pay upon Receipt)';
    }

    public function createPayment(Order $order, array $options = []): array
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $this->id(),
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => 'pending',
            'payload' => [
                'type' => 'cash_on_delivery',
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'payment_id' => $payment->id,
            'status' => 'pending',
            'instructions' => 'Please keep exact cash ready upon delivery.',
        ];
    }

    public function verifyPayment(Payment $payment, array $payload = []): bool
    {
        // COD is verified upon delivery
        return true;
    }

    public function handleWebhook(Request $request): array
    {
        return ['status' => 'ignored', 'message' => 'COD does not require webhooks.'];
    }

    public function refund(Payment $payment, int $amountInCents, ?string $reason = null): bool
    {
        $payment->update([
            'status' => 'refunded',
            'payload' => array_merge($payment->payload ?? [], [
                'refund_reason' => $reason,
                'refunded_at' => now()->toIso8601String(),
            ]),
        ]);

        return true;
    }
}
