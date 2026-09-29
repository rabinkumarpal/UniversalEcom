<?php

namespace Packages\B2BCommerce;

use App\Core\Registry\AddonRegistry;
use App\Core\Registry\PaymentGatewayRegistry;
use App\Core\Registry\PricingAdjustmentRegistry;
use App\Domain\Inventory\InventoryService;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Packages\B2BCommerce\Gateways\PurchaseOrderGateway;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\B2BCommerce\Services\B2BApprovalService;
use Packages\B2BCommerce\Services\B2BContractPricingAdjuster;
use Packages\B2BCommerce\Services\CompanyService;

class B2BCommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CompanyService::class, fn () => new CompanyService);
        $this->app->singleton(B2BApprovalService::class, fn ($app) => new B2BApprovalService($app->make(InventoryService::class)));
        $this->app->singleton(PurchaseOrderGateway::class, fn () => new PurchaseOrderGateway);
        $this->app->singleton(B2BContractPricingAdjuster::class, fn () => new B2BContractPricingAdjuster);
    }

    public function boot(): void
    {
        // Load isolated addon migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register addon in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new B2BCommerceAddon);

        // Register Purchase Order Payment Gateway in core PaymentGatewayRegistry
        $gatewayRegistry = $this->app->make(PaymentGatewayRegistry::class);
        $gatewayRegistry->register($this->app->make(PurchaseOrderGateway::class));

        // Register B2B Contract Pricing Adjuster in core PricingAdjustmentRegistry
        $pricingRegistry = $this->app->make(PricingAdjustmentRegistry::class);
        $pricingRegistry->register($this->app->make(B2BContractPricingAdjuster::class));

        // Dynamically resolve User relationships to maintain core isolation (spec §11)
        User::resolveRelationUsing('companyUser', function (User $user) {
            return $user->hasOne(CompanyUser::class);
        });

        User::resolveRelationUsing('company', function (User $user) {
            return $user->hasOneThrough(Company::class, CompanyUser::class, 'user_id', 'id', 'id', 'company_id');
        });
    }
}
