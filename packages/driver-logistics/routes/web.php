<?php

use Illuminate\Support\Facades\Route;
use Packages\DriverLogistics\Controllers\AdminFleetController;
use Packages\DriverLogistics\Controllers\DriverAuthController;
use Packages\DriverLogistics\Controllers\DriverPortalController;

/*
|--------------------------------------------------------------------------
| Driver Mobile Portal Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web'])->prefix('driver')->name('driver.')->group(function () {
    // Guest authentication
    Route::get('/login', [DriverAuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [DriverAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [DriverAuthController::class, 'logout'])->name('logout');

    // Authenticated Driver Portal
    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [DriverPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/shipments/{id}', [DriverPortalController::class, 'showShipment'])->name('shipments.show');
        Route::post('/shipments/{id}/start', [DriverPortalController::class, 'startDelivery'])->name('shipments.start');
        Route::post('/shipments/{id}/pod', [DriverPortalController::class, 'submitPod'])->name('shipments.pod');
        Route::post('/shipments/{id}/exception', [DriverPortalController::class, 'reportException'])->name('shipments.exception');
        Route::post('/status/toggle', [DriverPortalController::class, 'toggleDutyStatus'])->name('status.toggle');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Fleet Operations Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin/logistics')->name('admin.logistics.')->group(function () {
    Route::prefix('drivers')->name('drivers.')->group(function () {
        Route::get('/', [AdminFleetController::class, 'index'])->name('index');
        Route::post('/', [AdminFleetController::class, 'store'])->name('store');
        Route::post('/{id}/status', [AdminFleetController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/assign', [AdminFleetController::class, 'assignShipment'])->name('assign');
    });
});
