<?php

namespace Packages\PaymentGateways\Gateways;

use App\Core\Contracts\PaymentGatewayContract;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Packages\PaymentGateways\Models\PaymentGatewayConfig;
use Packages\PaymentGateways\Services\PaymentReconciliationService;

class AdvancedCodPaymentGateway implements PaymentGatewayContract
{
    public function __construct(
        protected PaymentReconciliationService $reconciliationService
    ) {}

    public function id(): string
    {
        return 'advanced_cod';
    }

    public function name(): string
    {
        return 'Cash on Delivery with Advance Deposit';
    }

    public function createPayment(Order $order, array $options = []): array
    {
        $config = PaymentGatewayConfig::getCodConfig();
        $threshold = $config->settings['advance_deposit_threshold'] ?? 1000000; // ₹10,000 in paise
        $percentage = $config->settings['advance_deposit_percentage'] ?? 15;

        $grandTotal = $order->grand_total;
        $requiresAdvance = $grandTotal >= $threshold;
        $advanceAmount = $requiresAdvance ? (int) round(($grandTotal * $percentage) / 100) : 0;
        $balanceAmount = $grandTotal - $advanceAmount;

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $this->id(),
            'amount' => $grandTotal,
            'currency' => $order->currency ?? 'INR',
            'status' => 'pending',
            'payload' => [
                'type' => 'advanced_cod',
                'requires_advance' => $requiresAdvance,
                'advance_percentage' => $requiresAdvance ? $percentage : 0,
                'advance_amount' => $advanceAmount,
                'balance_amount' => $balanceAmount,
                'threshold' => $threshold,
            ],
        ]);

        $this->reconciliationService->createForOrder($order, $this->id(), $payment);

        $instructions = $requiresAdvance
            ? 'High-value consignment: A '.($percentage).'% advance commitment deposit of ₹'.number_format($advanceAmount / 100, 2).' is required before vehicle dispatch. The remaining balance of ₹'.number_format($balanceAmount / 100, 2).' is payable to driver upon material unloading.'
            : 'Please keep exact cash ready upon delivery handover.';

        return [
            'payment_id' => $payment->id,
            'gateway' => $this->id(),
            'requires_advance' => $requiresAdvance,
            'advance_amount' => $advanceAmount,
            'balance_amount' => $balanceAmount,
            'status' => 'pending',
            'instructions' => $instructions,
        ];
    }

    public function verifyPayment(Payment $payment, array $payload = []): bool
    {
        return true;
    }

    public function handleWebhook(Request $request): array
    {
        return [
            'status' => 'ignored',
            'message' => 'Cash on Delivery does not require webhooks.',
        ];
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
