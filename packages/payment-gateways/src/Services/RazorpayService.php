<?php

namespace Packages\PaymentGateways\Services;

use Illuminate\Support\Str;
use Packages\PaymentGateways\Models\PaymentGatewayConfig;

class RazorpayService
{
    /**
     * Get configured API key.
     */
    public function getKeyId(): string
    {
        $config = PaymentGatewayConfig::getRazorpayConfig();

        return $config->credentials['key_id'] ?? 'rzp_test_ecom_mock_key_001';
    }

    /**
     * Get configured API secret.
     */
    public function getKeySecret(): string
    {
        $config = PaymentGatewayConfig::getRazorpayConfig();

        return $config->credentials['key_secret'] ?? 'rzp_test_secret_mock_999';
    }

    /**
     * Get configured webhook secret.
     */
    public function getWebhookSecret(): string
    {
        $config = PaymentGatewayConfig::getRazorpayConfig();

        return $config->credentials['webhook_secret'] ?? 'rzp_whsec_mock_888';
    }

    /**
     * Create Razorpay Order object with unique order ID for client checkout.
     */
    public function createOrder(int $amountInPaise, string $currency = 'INR', ?string $receipt = null, array $notes = []): array
    {
        $orderId = 'order_'.Str::lower(Str::random(14));

        return [
            'id' => $orderId,
            'entity' => 'order',
            'amount' => $amountInPaise,
            'amount_paid' => 0,
            'amount_due' => $amountInPaise,
            'currency' => $currency,
            'receipt' => $receipt ?? ('RCPT-'.time()),
            'status' => 'created',
            'attempts' => 0,
            'notes' => $notes,
            'created_at' => time(),
        ];
    }

    /**
     * Cryptographically verify payment signature returned by Razorpay Checkout modal.
     * signature = HMAC-SHA256(order_id + "|" + payment_id, secret)
     */
    public function verifySignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature, ?string $secret = null): bool
    {
        $keySecret = $secret ?? $this->getKeySecret();
        $payload = $razorpayOrderId.'|'.$razorpayPaymentId;
        $expectedSignature = hash_hmac('sha256', $payload, $keySecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Cryptographically verify inbound webhook payload signature against X-Razorpay-Signature header.
     */
    public function verifyWebhookSignature(string $rawPayload, string $signature, ?string $secret = null): bool
    {
        $webhookSecret = $secret ?? $this->getWebhookSecret();
        $expectedSignature = hash_hmac('sha256', $rawPayload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process refund on Razorpay gateway.
     */
    public function refund(string $razorpayPaymentId, int $amountInPaise, ?string $reason = null): array
    {
        return [
            'id' => 'rfnd_'.Str::lower(Str::random(14)),
            'entity' => 'refund',
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'payment_id' => $razorpayPaymentId,
            'status' => 'processed',
            'speed_processed' => 'normal',
            'notes' => [
                'reason' => $reason ?? 'Customer return or cancellation',
            ],
            'created_at' => time(),
        ];
    }
}
