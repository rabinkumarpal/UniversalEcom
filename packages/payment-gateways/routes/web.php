<?php

use Illuminate\Support\Facades\Route;
use Packages\PaymentGateways\Controllers\AdminReconciliationController;
use Packages\PaymentGateways\Controllers\RazorpayCheckoutController;

// Public Customer & Inbound Gateway Routes
Route::prefix('payments/razorpay')->name('payments.razorpay.')->group(function () {
    Route::post('/initiate', [RazorpayCheckoutController::class, 'initiate'])->name('initiate');
    Route::post('/verify', [RazorpayCheckoutController::class, 'verify'])->name('verify');
});

// Inbound webhook for asynchronous capture callbacks (whitelisted from CSRF)
Route::post('/webhooks/razorpay', [RazorpayCheckoutController::class, 'webhook'])->name('webhooks.razorpay');

// Admin Financial Reconciliation & Gateway Management
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin/payments')->name('admin.payments.')->group(function () {
    Route::get('/reconciliation', [AdminReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('/reconciliation/{id}/settle', [AdminReconciliationController::class, 'settle'])->name('reconciliation.settle');
    Route::post('/reconciliation/{id}/discrepancy', [AdminReconciliationController::class, 'discrepancy'])->name('reconciliation.discrepancy');
    Route::get('/gateways', [AdminReconciliationController::class, 'gateways'])->name('gateways.index');
    Route::post('/gateways/{gateway}', [AdminReconciliationController::class, 'updateGateway'])->name('gateways.update');
});
