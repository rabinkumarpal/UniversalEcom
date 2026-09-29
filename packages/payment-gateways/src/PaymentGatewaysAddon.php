<?php

namespace Packages\PaymentGateways;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;

class PaymentGatewaysAddon implements AddonInterface
{
    public function id(): string
    {
        return 'payment-gateways';
    }

    public function name(): string
    {
        return 'Payment Gateways & Financial Reconciliation Pack';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Enterprise multi-channel payment gateway pack delivering Razorpay UPI/Netbanking/Card payments with HMAC SHA256 webhook signature verification and Cash on Delivery with advance deposits and driver cash collection reconciliation.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.payment_gateways.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Addon boot hooks
    }
}
