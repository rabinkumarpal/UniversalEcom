<?php

namespace Packages\LoyaltyWallet\Gateways;

use App\Core\Contracts\PaymentGatewayContract;
use App\Core\Events\PaymentCaptured;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Packages\LoyaltyWallet\Services\WalletService;
use RuntimeException;

class WalletPaymentGateway implements PaymentGatewayContract
{
    public function __construct(protected WalletService $walletService) {}

    public function id(): string
    {
        return 'wallet';
    }

    public function name(): string
    {
        return 'Customer Digital Wallet';
    }

    public function createPayment(Order $order, array $options = []): array
    {
        $user = $order->user;

        if (! $user) {
            throw new RuntimeException('Wallet payment requires an authenticated customer account.');
        }

        $wallet = $this->walletService->getOrCreateWallet($user);

        if ($wallet->balance < $order->grand_total) {
            throw new RuntimeException('Insufficient wallet balance. Required: ₹'.number_format($order->grand_total / 100, 2).', Available: ₹'.number_format($wallet->balance / 100, 2));
        }

        // Atomically debit wallet balance
        $entry = $this->walletService->debit(
            $wallet,
            $order->grand_total,
            'order_payment',
            $order->order_number,
            "Payment for order {$order->order_number}"
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $this->id(),
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => 'captured',
            'transaction_id' => 'WLT-'.$entry->id,
            'payload' => [
                'wallet_account_id' => $wallet->id,
                'ledger_entry_id' => $entry->id,
                'debited_amount' => $entry->amount,
                'balance_after' => $entry->balance_after,
            ],
        ]);

        event(new PaymentCaptured($payment));

        return [
            'payment_id' => $payment->id,
            'status' => 'captured',
            'transaction_id' => $payment->transaction_id,
            'balance_remaining' => $entry->balance_after,
        ];
    }

    public function verifyPayment(Payment $payment, array $payload = []): bool
    {
        return $payment->status === 'captured';
    }

    public function handleWebhook(Request $request): array
    {
        return ['status' => 'ignored', 'message' => 'Internal wallet payments do not require webhooks.'];
    }

    public function refund(Payment $payment, int $amountInCents, ?string $reason = null): bool
    {
        $order = $payment->order;
        $user = $order->user;

        if ($user) {
            $wallet = $this->walletService->getOrCreateWallet($user);
            $this->walletService->credit(
                $wallet,
                $amountInCents,
                'refund',
                $order->order_number,
                "Refund for order {$order->order_number}: {$reason}"
            );
        }

        $payment->update([
            'status' => 'refunded',
            'payload' => array_merge($payment->payload ?? [], [
                'refunded_amount' => $amountInCents,
                'refund_reason' => $reason,
                'refunded_at' => now()->toIso8601String(),
            ]),
        ]);

        return true;
    }
}
