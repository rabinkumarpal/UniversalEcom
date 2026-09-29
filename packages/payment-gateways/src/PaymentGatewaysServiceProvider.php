<?php

namespace Packages\PaymentGateways;

use App\Core\Registry\AddonRegistry;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\ServiceProvider;
use Packages\PaymentGateways\Gateways\AdvancedCodPaymentGateway;
use Packages\PaymentGateways\Gateways\RazorpayPaymentGateway;
use Packages\PaymentGateways\Models\PaymentReconciliation;
use Packages\PaymentGateways\Services\PaymentReconciliationService;
use Packages\PaymentGateways\Services\RazorpayService;

class PaymentGatewaysServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RazorpayService::class, fn () => new RazorpayService);
        $this->app->singleton(PaymentReconciliationService::class, fn () => new PaymentReconciliationService);

        $this->app->singleton(RazorpayPaymentGateway::class, function ($app) {
            return new RazorpayPaymentGateway(
                $app->make(RazorpayService::class),
                $app->make(PaymentReconciliationService::class)
            );
        });

        $this->app->singleton(AdvancedCodPaymentGateway::class, function ($app) {
            return new AdvancedCodPaymentGateway(
                $app->make(PaymentReconciliationService::class)
            );
        });
    }

    public function boot(): void
    {
        // 1. Load isolated migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 2. Load isolated routes & views
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'payment-gateways');

        // 3. Register in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new PaymentGatewaysAddon);

        // 4. Register payment gateways into core PaymentGatewayRegistry
        $gatewayRegistry = $this->app->make(PaymentGatewayRegistry::class);
        $gatewayRegistry->register($this->app->make(RazorpayPaymentGateway::class));
        $gatewayRegistry->register($this->app->make(AdvancedCodPaymentGateway::class));

        // 5. Dynamically resolve relationships without modifying core schemas
        Order::resolveRelationUsing('reconciliations', function (Order $order) {
            return $order->hasMany(PaymentReconciliation::class);
        });

        Payment::resolveRelationUsing('reconciliation', function (Payment $payment) {
            return $payment->hasOne(PaymentReconciliation::class);
        });
    }
}
