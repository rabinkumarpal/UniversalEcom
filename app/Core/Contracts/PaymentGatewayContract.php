<?php

namespace App\Core\Contracts;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayContract
{
    /**
     * Unique gateway identifier (e.g. 'cod', 'razorpay', 'stripe').
     */
    public function id(): string;

    /**
     * Display name for storefront checkout.
     */
    public function name(): string;

    /**
     * Create payment session or prepare order for payment.
     * Returns an array with payment token, redirect URL, or metadata.
     */
    public function createPayment(Order $order, array $options = []): array;

    /**
     * Verify payment status server-side (never trust client redirect alone).
     */
    public function verifyPayment(Payment $payment, array $payload = []): bool;

    /**
     * Process an asynchronous webhook payload with signature verification & idempotency.
     */
    public function handleWebhook(Request $request): array;

    /**
     * Refund a captured payment (full or partial).
     */
    public function refund(Payment $payment, int $amountInCents, ?string $reason = null): bool;
}
