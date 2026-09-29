<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AdminCatalogController;
use App\Http\Controllers\Api\V1\AdminInventoryController;
use App\Http\Controllers\Api\V1\AdminOrderController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\VendorPortalController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health & System Observability Probe
    Route::get('/health', HealthCheckController::class);

    // Authentication (Rate-limited)
    Route::middleware('throttle:15,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    // Catalog Discovery (Public, Cached & Rate-limited)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/categories', [CatalogController::class, 'categories']);
        Route::get('/categories/{slug}', [CatalogController::class, 'category']);
        Route::get('/brands', [CatalogController::class, 'brands']);
        Route::get('/products', [CatalogController::class, 'products']);
        Route::get('/products/{slug}', [CatalogController::class, 'product']);
        Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
        Route::get('/search', [CatalogController::class, 'search']);
    });

    // Delivery & Serviceability (Public)
    Route::get('/delivery/serviceability', [DeliveryController::class, 'serviceability']);
    Route::get('/delivery/slots', [DeliveryController::class, 'slots']);

    // Cart Operations (Public / Session Token)
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon']);

    // Checkout Quote, Validation & Order Placement
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/checkout/validate', [CheckoutController::class, 'validateCheckout']);
        Route::post('/checkout/quote', [CheckoutController::class, 'quote']);
        Route::post('/orders', [CheckoutController::class, 'checkout']);
    });

    // Payments & Asynchronous Webhooks
    Route::post('/payments/create', [PaymentController::class, 'create']);
    Route::post('/payments/{id}/verify', [PaymentController::class, 'verify']);
    Route::post('/payments/webhook/{gateway}', [PaymentController::class, 'webhook']);

    // Authenticated Customer Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Orders & Reorder
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
        Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel']);
        Route::post('/orders/{orderNumber}/reorder', [OrderController::class, 'reorder']);

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist/items', [WishlistController::class, 'store']);
        Route::delete('/wishlist/items/{id}', [WishlistController::class, 'destroy']);

        // Delivery Addresses
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::patch('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

        // Product Reviews Submission
        Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);

        // Admin Management Endpoints
        Route::prefix('admin')->group(function () {
            Route::post('/products', [AdminCatalogController::class, 'storeProduct'])->middleware('permission:products.create');
            Route::get('/inventory', [AdminInventoryController::class, 'index']);
            Route::post('/inventory/{id}/adjust', [AdminInventoryController::class, 'adjust'])->middleware('permission:inventory.adjust');
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/{orderNumber}', [AdminOrderController::class, 'show']);
            Route::post('/orders/{orderNumber}/transition', [AdminOrderController::class, 'transition'])->middleware('permission:orders.update');
            Route::post('/orders/{orderNumber}/refund', [AdminOrderController::class, 'refund'])->middleware('permission:refunds.create');
        });

        // Vendor Portal Endpoints
        Route::prefix('vendor')->group(function () {
            Route::get('/profile', [VendorPortalController::class, 'profile']);
            Route::get('/offers', [VendorPortalController::class, 'offers']);
            Route::get('/orders', [VendorPortalController::class, 'orders']);
            Route::get('/payouts', [VendorPortalController::class, 'payouts']);
        });
    });
});
