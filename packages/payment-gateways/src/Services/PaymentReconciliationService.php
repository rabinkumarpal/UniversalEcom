<?php

namespace Packages\PaymentGateways\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;
use Packages\PaymentGateways\Models\PaymentGatewayConfig;
use Packages\PaymentGateways\Models\PaymentReconciliation;

class PaymentReconciliationService
{
    /**
     * Initialize a reconciliation ledger record for an order.
     */
    public function createForOrder(Order $order, string $method, ?Payment $payment = null, array $options = []): PaymentReconciliation
    {
        $recNumber = 'REC-'.date('Ymd').'-'.strtoupper(Str::random(6));
        $grandTotal = $order->grand_total;

        $advanceAmount = 0;
        $balanceAmount = $grandTotal;
        $collectedAmount = 0;
        $status = 'pending';

        if ($method === 'advanced_cod') {
            $config = PaymentGatewayConfig::getCodConfig();
            $threshold = $config->settings['advance_deposit_threshold'] ?? 1000000; // ₹10,000 in paise
            $percentage = $config->settings['advance_deposit_percentage'] ?? 15;

            if ($grandTotal >= $threshold) {
                $advanceAmount = (int) round(($grandTotal * $percentage) / 100);
                $balanceAmount = $grandTotal - $advanceAmount;
            }
        } elseif ($method === 'razorpay') {
            if ($payment && $payment->status === 'captured') {
                $collectedAmount = $grandTotal;
                $balanceAmount = 0;
                $status = 'settled';
            }
        }

        return PaymentReconciliation::create([
            'reconciliation_number' => $recNumber,
            'order_id' => $order->id,
            'payment_id' => $payment?->id,
            'method' => $method,
            'expected_amount' => $grandTotal,
            'collected_amount' => $collectedAmount,
            'advance_amount' => $advanceAmount,
            'balance_amount' => $balanceAmount,
            'settlement_status' => $status,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Record remittance collected by logistics driver upon delivery.
     */
    public function recordDriverRemittance(
        PaymentReconciliation $reconciliation,
        int $amount,
        User $driver,
        ?string $reference = null
    ): PaymentReconciliation {
        return $reconciliation->recordCollection($amount, $driver, $reference);
    }

    /**
     * Finance supervisor marks record settled with bank/reconciliation reference.
     */
    public function settle(
        PaymentReconciliation $reconciliation,
        int $amount,
        string $reference,
        User $verifier
    ): PaymentReconciliation {
        return $reconciliation->markSettled($amount, $reference, $verifier);
    }

    /**
     * Flag discrepancies for audit and investigation.
     */
    public function flagDiscrepancy(
        PaymentReconciliation $reconciliation,
        string $reason,
        User $verifier
    ): PaymentReconciliation {
        return $reconciliation->flagDiscrepancy($reason, $verifier);
    }
}
