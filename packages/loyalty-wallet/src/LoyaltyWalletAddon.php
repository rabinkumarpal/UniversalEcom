<?php

namespace Packages\LoyaltyWallet;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;
use App\Core\Registry\PaymentGatewayRegistry;
use Packages\LoyaltyWallet\Gateways\WalletPaymentGateway;

class LoyaltyWalletAddon implements AddonInterface
{
    public function id(): string
    {
        return 'loyalty-wallet';
    }

    public function name(): string
    {
        return 'Customer Loyalty, Wallet Ledger & Product Reviews';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Append-only immutable wallet financial ledger, wallet checkout gateway, verified reviews, wishlists, and 1-click reorder.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.loyalty_wallet.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        $gatewayRegistry = app(PaymentGatewayRegistry::class);
        $gatewayRegistry->register(app(WalletPaymentGateway::class));
    }
}
