<?php

namespace Packages\LoyaltyWallet;

use App\Core\Events\OrderPlaced;
use App\Core\Registry\AddonRegistry;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Domain\Cart\CartService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Packages\LoyaltyWallet\Gateways\WalletPaymentGateway;
use Packages\LoyaltyWallet\Services\ReorderService;
use Packages\LoyaltyWallet\Services\WalletService;

class LoyaltyWalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WalletService::class, fn () => new WalletService);
        $this->app->singleton(ReorderService::class, fn ($app) => new ReorderService($app->make(CartService::class)));
        $this->app->singleton(WalletPaymentGateway::class, fn ($app) => new WalletPaymentGateway($app->make(WalletService::class)));
    }

    public function boot(): void
    {
        // Load isolated addon migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register addon in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new LoyaltyWalletAddon);

        // Register Wallet Payment Gateway in core PaymentGatewayRegistry
        $gatewayRegistry = $this->app->make(PaymentGatewayRegistry::class);
        $gatewayRegistry->register($this->app->make(WalletPaymentGateway::class));

        // Automatically credit 2% loyalty cashback on successful customer orders
        Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
            $order = $event->order;
            $user = $order->user;

            if ($user) {
                $cashback = (int) round($order->grand_total * 0.02);
                if ($cashback > 0) {
                    $walletService = $this->app->make(WalletService::class);
                    $wallet = $walletService->getOrCreateWallet($user);
                    $walletService->credit(
                        $wallet,
                        $cashback,
                        'cashback',
                        $order->order_number,
                        "2% Loyalty Cashback earned on order #{$order->order_number}"
                    );
                }
            }
        });
    }
}
