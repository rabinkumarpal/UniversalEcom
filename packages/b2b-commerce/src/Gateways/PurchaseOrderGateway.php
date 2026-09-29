<?php

namespace Packages\B2BCommerce\Gateways;

use App\Core\Contracts\PaymentGatewayContract;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Models\PurchaseOrder;
use RuntimeException;

class PurchaseOrderGateway implements PaymentGatewayContract
{
    public function id(): string
    {
        return 'purchase_order';
    }

    public function name(): string
    {
        return 'Corporate Purchase Order (Net Terms & Credit)';
    }

    public function createPayment(Order $order, array $options = []): array
    {
        $user = $order->user;

        if (! $user) {
            throw new RuntimeException('Corporate purchase orders require an authenticated user account.');
        }

        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('company')
            ->first();

        if (! $companyUser || ! $companyUser->company || ! $companyUser->company->isActive()) {
            throw new RuntimeException('Authenticated user is not linked to an active corporate company account.');
        }

        $company = $companyUser->company;

        if (! $company->hasAvailableCredit($order->grand_total)) {
            throw new RuntimeException('Company credit limit exceeded. Required: ₹'.number_format($order->grand_total / 100, 2).', Available: ₹'.number_format($company->credit_balance / 100, 2));
        }

        $needsApproval = $companyUser->requiresApproval($order->grand_total);
        $poNumber = ! empty($options['po_number']) ? $options['po_number'] : ('PO-'.strtoupper(Str::random(8)));

        // Reserve / deduct credit atomically
        $company->deductCredit($order->grand_total);

        $poStatus = $needsApproval ? 'pending_approval' : 'approved';
        $orderStatus = $needsApproval ? 'pending_approval' : 'confirmed';

        $order->status = $orderStatus;
        $order->payment_status = 'pending';
        $order->save();

        $dueDate = now()->addDays($company->payment_terms_days);

        $purchaseOrder = PurchaseOrder::create([
            'company_id' => $company->id,
            'order_id' => $order->id,
            'po_number' => $poNumber,
            'status' => $poStatus,
            'amount' => $order->grand_total,
            'payment_terms_days' => $company->payment_terms_days,
            'due_date' => $dueDate,
            'requester_user_id' => $user->id,
            'approver_user_id' => $needsApproval ? null : $user->id,
            'approved_at' => $needsApproval ? null : now(),
            'approval_notes' => $needsApproval
                ? 'Order amount ₹'.number_format($order->grand_total / 100, 2).' exceeds buyer limit of ₹'.number_format(($companyUser->spending_limit ?? 0) / 100, 2).'; awaiting supervisor sign-off.'
                : 'Approved under corporate credit line.',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $this->id(),
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => $needsApproval ? 'pending' : 'authorized',
            'transaction_id' => $poNumber,
            'payload' => [
                'company_id' => $company->id,
                'company_code' => $company->company_code,
                'purchase_order_id' => $purchaseOrder->id,
                'payment_terms_days' => $company->payment_terms_days,
                'due_date' => $dueDate->toDateString(),
                'requires_approval' => $needsApproval,
            ],
        ]);

        return [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'transaction_id' => $poNumber,
            'purchase_order_id' => $purchaseOrder->id,
            'requires_approval' => $needsApproval,
        ];
    }

    public function verifyPayment(Payment $payment, array $payload = []): bool
    {
        return in_array($payment->status, ['authorized', 'captured'], true);
    }

    public function handleWebhook(Request $request): array
    {
        return ['status' => 'ignored', 'message' => 'Corporate purchase orders do not use asynchronous webhook callbacks.'];
    }

    public function refund(Payment $payment, int $amountInCents, ?string $reason = null): bool
    {
        $companyId = $payment->payload['company_id'] ?? null;

        if ($companyId) {
            $company = Company::find($companyId);
            $company?->restoreCredit($amountInCents);
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
