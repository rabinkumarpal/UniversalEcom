<?php

use App\Providers\AppServiceProvider;
use App\Providers\EcommerceServiceProvider;
use Packages\B2BCommerce\B2BCommerceServiceProvider;
use Packages\DomainConstruction\DomainConstructionServiceProvider;
use Packages\DriverLogistics\DriverLogisticsServiceProvider;
use Packages\InvoiceGst\GstInvoiceServiceProvider;
use Packages\LoyaltyWallet\LoyaltyWalletServiceProvider;
use Packages\PaymentGateways\PaymentGatewaysServiceProvider;
use Packages\PromotionEngine\PromotionEngineServiceProvider;
use Packages\VendorMarketplace\VendorMarketplaceServiceProvider;
use Packages\WhatsAppNotifications\WhatsAppNotificationsServiceProvider;

return [
    AppServiceProvider::class,
    EcommerceServiceProvider::class,
    PromotionEngineServiceProvider::class,
    VendorMarketplaceServiceProvider::class,
    DomainConstructionServiceProvider::class,
    LoyaltyWalletServiceProvider::class,
    B2BCommerceServiceProvider::class,
    DriverLogisticsServiceProvider::class,
    GstInvoiceServiceProvider::class,
    WhatsAppNotificationsServiceProvider::class,
    PaymentGatewaysServiceProvider::class,
];
