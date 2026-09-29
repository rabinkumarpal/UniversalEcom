<?php

use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Web\AdminAttributeController;
use App\Http\Controllers\Web\AdminAuditController;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminBrandController;
use App\Http\Controllers\Web\AdminBulkCatalogController;
use App\Http\Controllers\Web\AdminCatalogWebController;
use App\Http\Controllers\Web\AdminCategoryController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminMarketplaceController;
use App\Http\Controllers\Web\AdminMediaController;
use App\Http\Controllers\Web\AdminPromotionsController;
use App\Http\Controllers\Web\AdminRoleController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminShipmentController;
use App\Http\Controllers\Web\AdminTagController;
use App\Http\Controllers\Web\AdminUserController;
use App\Http\Controllers\Web\ContentController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\StorefrontController;
use Packages\B2BCommerce\Controllers\Admin\AdminB2BCompanyController;
use Packages\B2BCommerce\Controllers\Admin\AdminB2BPriceListController;
use Packages\B2BCommerce\Controllers\Admin\AdminB2BPurchaseOrderController;
use Packages\B2BCommerce\Controllers\CustomerB2BController;
use Packages\LoyaltyWallet\Controllers\CustomerAccountController;

/*
|--------------------------------------------------------------------------
| Authentication Fallback Route
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:10,1')->get('/login', fn () => redirect()->route('admin.login'))->name('login');

/*
|--------------------------------------------------------------------------
| Storefront Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::get('/catalog', [StorefrontController::class, 'catalog'])->name('storefront.catalog');
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('storefront.product');

Route::prefix('cart')->name('storefront.cart')->group(function () {
    Route::get('/', [StorefrontController::class, 'cart']);
    Route::post('/add', [StorefrontController::class, 'addToCart'])->name('.add');
    Route::post('/update/{itemId}', [StorefrontController::class, 'updateCartItem'])->name('.update');
    Route::post('/remove/{itemId}', [StorefrontController::class, 'removeCartItem'])->name('.remove');
    Route::post('/coupon', [StorefrontController::class, 'applyCoupon'])->name('.coupon');
    Route::post('/coupon/remove', [StorefrontController::class, 'removeCoupon'])->name('.coupon.remove');
});

Route::post('/location', [StorefrontController::class, 'setLocation'])->name('storefront.set_location');

Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('storefront.checkout');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('storefront.order.place');
Route::get('/order-confirmation/{orderNumber}', [StorefrontController::class, 'orderConfirmation'])->name('storefront.order_confirmation');

/*
|--------------------------------------------------------------------------
| Content, Calculators & Knowledge Hub Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/faq', [ContentController::class, 'faq'])->name('content.faq');
Route::get('/about', [ContentController::class, 'about'])->name('content.about');
Route::get('/contact', [ContentController::class, 'contact'])->name('content.contact');
Route::post('/contact', [ContentController::class, 'submitContact'])->name('content.contact.submit');
Route::get('/calculators', [ContentController::class, 'calculators'])->name('content.calculators');
Route::get('/knowledge', [ContentController::class, 'knowledgeIndex'])->name('content.knowledge.index');
Route::get('/knowledge/{slug}', [ContentController::class, 'knowledgeArticle'])->name('content.knowledge.article');

/*
|--------------------------------------------------------------------------
| Customer Account & Loyalty Web Routes (Add-on)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->prefix('account')->name('account.')->group(function () {
    Route::get('/orders', [CustomerAccountController::class, 'orders'])->name('orders');
    Route::post('/reorder/{orderNumber}', [CustomerAccountController::class, 'reorder'])->name('reorder');
    Route::get('/wallet', [CustomerAccountController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/top-up', [CustomerAccountController::class, 'topUpWallet'])->name('wallet.topup');
    Route::get('/wishlist', [CustomerAccountController::class, 'wishlist'])->name('wishlist');
    Route::post('/wishlist/toggle', [CustomerAccountController::class, 'toggleWishlist'])->name('wishlist.toggle');
    Route::post('/product/{productId}/review', [CustomerAccountController::class, 'submitReview'])->name('product.review');

    // Delivery Addresses & Project Sites
    Route::get('/addresses', [CustomerAccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [CustomerAccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{id}', [CustomerAccountController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::post('/addresses/{id}/default', [CustomerAccountController::class, 'setDefaultAddress'])->name('addresses.default');

    // Customer Profile & Credentials
    Route::get('/profile', [CustomerAccountController::class, 'profile'])->name('profile');
    Route::post('/profile', [CustomerAccountController::class, 'updateProfile'])->name('profile.update');

    // Corporate B2B Account, Requisitions & Approvals
    Route::prefix('b2b')->name('b2b.')->group(function () {
        Route::get('/', [CustomerB2BController::class, 'index'])->name('index');
        Route::get('/po/{poNumber}', [CustomerB2BController::class, 'showPo'])->name('po.show');
        Route::post('/po/{id}/approve', [CustomerB2BController::class, 'approve'])->name('approve');
        Route::post('/po/{id}/reject', [CustomerB2BController::class, 'reject'])->name('reject');
    });
});

/*
|--------------------------------------------------------------------------
| Dynamic SEO Sitemap
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Admin Operations Web Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'role:admin,super-admin,catalog-manager,order-manager,inventory-manager,delivery-manager'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('orders');
        Route::get('/orders/{orderNumber}', [AdminDashboardController::class, 'showOrder'])->name('orders.show');
        Route::get('/orders/{orderNumber}/packing-slip', [AdminDashboardController::class, 'packingSlip'])->name('orders.packing_slip');
        Route::post('/orders/{orderNumber}/transition', [AdminDashboardController::class, 'transitionOrder'])->name('orders.transition');
        Route::post('/orders/{orderNumber}/invoice', [AdminDashboardController::class, 'generateInvoice'])->name('orders.invoice');
        Route::get('/inventory', [AdminDashboardController::class, 'inventory'])->name('inventory');
        Route::post('/inventory/{itemId}/adjust', [AdminDashboardController::class, 'adjustStock'])->name('inventory.adjust');
        Route::post('/inventory/{itemId}/transfer', [AdminDashboardController::class, 'transferStock'])->name('inventory.transfer');
        Route::get('/addons', [AdminDashboardController::class, 'addons'])->name('addons');
        Route::post('/addons/{id}/toggle', [AdminSettingsController::class, 'toggleAddon'])->name('addons.toggle');
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

        // Audit Log Viewer
        Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit');

        // Promotions Management
        Route::get('/promotions', [AdminPromotionsController::class, 'index'])->name('promotions.index');
        Route::get('/promotions/create', [AdminPromotionsController::class, 'create'])->name('promotions.create');
        Route::post('/promotions', [AdminPromotionsController::class, 'store'])->name('promotions.store');
        Route::get('/promotions/{id}/edit', [AdminPromotionsController::class, 'edit'])->name('promotions.edit');
        Route::put('/promotions/{id}', [AdminPromotionsController::class, 'update'])->name('promotions.update');
        Route::post('/promotions/{id}/toggle-status', [AdminPromotionsController::class, 'toggleStatus'])->name('promotions.toggle-status');
        Route::delete('/promotions/{id}', [AdminPromotionsController::class, 'destroy'])->name('promotions.destroy');

        // Admin Shipments & Logistics
        Route::prefix('shipments')->name('shipments.')->group(function () {
            Route::get('/', [AdminShipmentController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminShipmentController::class, 'show'])->name('show');
            Route::post('/{id}/dispatch', [AdminShipmentController::class, 'dispatch'])->name('dispatch');
            Route::post('/{id}/pod', [AdminShipmentController::class, 'recordPod'])->name('pod');
            Route::post('/{id}/exception', [AdminShipmentController::class, 'recordException'])->name('exception');
        });

        // Admin Bulk Catalog Import & Export
        Route::prefix('catalog/bulk')->name('catalog.bulk')->group(function () {
            Route::get('/', [AdminBulkCatalogController::class, 'index']);
            Route::post('/preview', [AdminBulkCatalogController::class, 'preview'])->name('.preview');
            Route::post('/commit', [AdminBulkCatalogController::class, 'commit'])->name('.commit');
            Route::get('/sample', [AdminBulkCatalogController::class, 'sample'])->name('.sample');
            Route::get('/export', [AdminBulkCatalogController::class, 'export'])->name('.export');
        });

        // Admin Catalog — Products
        Route::prefix('catalog/products')->name('catalog.products.')->group(function () {
            Route::get('/', [AdminCatalogWebController::class, 'index'])->name('index');
            Route::get('/create', [AdminCatalogWebController::class, 'create'])->name('create');
            Route::post('/', [AdminCatalogWebController::class, 'store'])->name('store');
            Route::post('/bulk-action', [AdminCatalogWebController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{id}/edit', [AdminCatalogWebController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminCatalogWebController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminCatalogWebController::class, 'destroy'])->name('destroy');
            Route::delete('/{id}/media/{mediaId}', [AdminCatalogWebController::class, 'destroyMedia'])->name('media.destroy');
            Route::post('/{id}/media/{mediaId}/primary', [AdminCatalogWebController::class, 'setPrimaryMedia'])->name('media.primary');
        });

        // Admin Catalog — Categories
        Route::prefix('catalog/categories')->name('catalog.categories.')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
            Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
            Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminCategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminCategoryController::class, 'destroy'])->name('destroy');
        });

        // Admin Catalog — Brands
        Route::prefix('catalog/brands')->name('catalog.brands.')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index'])->name('index');
            Route::get('/create', [AdminBrandController::class, 'create'])->name('create');
            Route::post('/', [AdminBrandController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminBrandController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminBrandController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminBrandController::class, 'destroy'])->name('destroy');
        });

        // Admin Catalog — Tags
        Route::prefix('catalog/tags')->name('catalog.tags.')->group(function () {
            Route::get('/', [AdminTagController::class, 'index'])->name('index');
            Route::post('/', [AdminTagController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminTagController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminTagController::class, 'destroy'])->name('destroy');
        });

        // Admin Catalog — Attribute Definitions
        Route::prefix('catalog/attributes')->name('catalog.attributes.')->group(function () {
            Route::get('/', [AdminAttributeController::class, 'index'])->name('index');
            Route::get('/create', [AdminAttributeController::class, 'create'])->name('create');
            Route::post('/', [AdminAttributeController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminAttributeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminAttributeController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminAttributeController::class, 'destroy'])->name('destroy');
        });

        // Admin Media & Assets Library
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [AdminMediaController::class, 'index'])->name('index');
            Route::post('/', [AdminMediaController::class, 'store'])->name('store');
            Route::delete('/{id}', [AdminMediaController::class, 'destroy'])->name('destroy');
            Route::get('/api', [AdminMediaController::class, 'api'])->name('api');
        });

        // Admin User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminUserController::class, 'show'])->name('show');
            Route::post('/{id}/status', [AdminUserController::class, 'updateStatus'])->name('status');
            Route::post('/{id}/roles', [AdminUserController::class, 'assignRole'])->name('roles');
        });

        // Admin Role & Permission Manager
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [AdminRoleController::class, 'index'])->name('index');
            Route::get('/create', [AdminRoleController::class, 'create'])->name('create');
            Route::post('/', [AdminRoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminRoleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminRoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminRoleController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/users', [AdminRoleController::class, 'assignUsers'])->name('assign_users');
        });

        // Admin Marketplace Operations
        Route::prefix('marketplace')->name('marketplace.')->group(function () {
            Route::get('/vendors', [AdminMarketplaceController::class, 'vendors'])->name('vendors');
            Route::post('/vendors/{id}/status', [AdminMarketplaceController::class, 'updateVendor'])->name('vendors.status');
            Route::get('/payouts', [AdminMarketplaceController::class, 'payouts'])->name('payouts');
            Route::post('/payouts/{id}/approve', [AdminMarketplaceController::class, 'approvePayout'])->name('payouts.approve');
            Route::post('/payouts/{id}/settle', [AdminMarketplaceController::class, 'settlePayout'])->name('payouts.settle');
        });

        // Admin B2B Commerce Operations
        Route::prefix('b2b')->name('b2b.')->group(function () {
            // Companies & Accounts
            Route::get('/companies', [AdminB2BCompanyController::class, 'index'])->name('companies');
            Route::post('/companies', [AdminB2BCompanyController::class, 'store'])->name('companies.store');
            Route::get('/companies/{id}', [AdminB2BCompanyController::class, 'show'])->name('companies.show');
            Route::post('/companies/{id}/status', [AdminB2BCompanyController::class, 'updateStatus'])->name('companies.status');
            Route::post('/companies/{id}/credit-limit', [AdminB2BCompanyController::class, 'updateCreditLimit'])->name('companies.credit_limit');
            Route::post('/companies/{id}/users', [AdminB2BCompanyController::class, 'assignUser'])->name('companies.users.assign');
            Route::delete('/companies/{id}/users/{userId}', [AdminB2BCompanyController::class, 'removeUser'])->name('companies.users.remove');

            // Contract Price Lists
            Route::get('/price-lists', [AdminB2BPriceListController::class, 'index'])->name('price_lists.index');
            Route::post('/price-lists', [AdminB2BPriceListController::class, 'store'])->name('price_lists.store');
            Route::post('/price-lists/{id}/variants', [AdminB2BPriceListController::class, 'addVariantPrice'])->name('price_lists.variant_price');

            // Corporate Purchase Orders
            Route::get('/purchase-orders', [AdminB2BPurchaseOrderController::class, 'index'])->name('purchase_orders.index');
            Route::post('/purchase-orders/{id}/approve', [AdminB2BPurchaseOrderController::class, 'approve'])->name('purchase_orders.approve');
            Route::post('/purchase-orders/{id}/reject', [AdminB2BPurchaseOrderController::class, 'reject'])->name('purchase_orders.reject');
        });
    });
});

Route::get('/health', HealthCheckController::class)->name('health');
