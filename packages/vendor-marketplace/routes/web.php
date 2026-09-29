<?php

use Illuminate\Support\Facades\Route;
use Packages\VendorMarketplace\Controllers\VendorAuthController;
use Packages\VendorMarketplace\Controllers\VendorDashboardController;
use Packages\VendorMarketplace\Controllers\VendorOfferController;
use Packages\VendorMarketplace\Controllers\VendorOrderController;
use Packages\VendorMarketplace\Controllers\VendorPayoutController;
use Packages\VendorMarketplace\Controllers\VendorProfileController;
use Packages\VendorMarketplace\Middleware\EnsureVendorUser;

Route::middleware('web')->prefix('vendor')->group(function () {
    // Vendor Guest / Auth
    Route::get('/login', [VendorAuthController::class, 'showLogin'])->name('vendor.login');
    Route::post('/login', [VendorAuthController::class, 'login']);
    Route::get('/register', [VendorAuthController::class, 'showRegister'])->name('vendor.register');
    Route::post('/register', [VendorAuthController::class, 'register']);
    Route::post('/logout', [VendorAuthController::class, 'logout'])->name('vendor.logout');

    // Authenticated & Isolated Vendor Portal
    Route::middleware([EnsureVendorUser::class])->group(function () {
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('vendor.dashboard');

        // Offers Management
        Route::get('/offers', [VendorOfferController::class, 'index'])->name('vendor.offers.index');
        Route::get('/offers/create', [VendorOfferController::class, 'create'])->name('vendor.offers.create');
        Route::post('/offers', [VendorOfferController::class, 'store'])->name('vendor.offers.store');
        Route::post('/offers/{id}/update', [VendorOfferController::class, 'update'])->name('vendor.offers.update');

        // Orders & Fulfillment
        Route::get('/orders', [VendorOrderController::class, 'index'])->name('vendor.orders.index');
        Route::get('/orders/{id}', [VendorOrderController::class, 'show'])->name('vendor.orders.show');
        Route::get('/orders/{id}/packing-slip', [VendorOrderController::class, 'packingSlip'])->name('vendor.orders.packing-slip');
        Route::post('/orders/{id}/status', [VendorOrderController::class, 'updateStatus'])->name('vendor.orders.status');

        // Payouts Ledger & Settlement
        Route::get('/payouts', [VendorPayoutController::class, 'index'])->name('vendor.payouts.index');
        Route::post('/payouts/request', [VendorPayoutController::class, 'requestPayout'])->name('vendor.payouts.request');

        // Profile Settings
        Route::get('/profile', [VendorProfileController::class, 'index'])->name('vendor.profile.index');
        Route::post('/profile', [VendorProfileController::class, 'update'])->name('vendor.profile.update');
    });
});
