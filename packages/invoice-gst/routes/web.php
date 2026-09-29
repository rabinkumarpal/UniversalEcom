<?php

use Illuminate\Support\Facades\Route;
use Packages\InvoiceGst\Controllers\AdminInvoiceController;
use Packages\InvoiceGst\Controllers\CustomerInvoiceController;

Route::middleware(['web'])->group(function () {
    // Customer Invoices (Auth Protected)
    Route::middleware(['auth'])->prefix('account/invoices')->name('account.invoices.')->group(function () {
        Route::get('/{invoiceNumber}', [CustomerInvoiceController::class, 'show'])
            ->where('invoiceNumber', '.*')
            ->name('show');
    });

    // Admin Finance & GST Operations
    Route::middleware(['auth'])->prefix('admin/invoices')->name('admin.invoices.')->group(function () {
        Route::get('/', [AdminInvoiceController::class, 'index'])->name('index');
        Route::post('/{id}/cancel', [AdminInvoiceController::class, 'cancel'])->name('cancel');
    });
});
