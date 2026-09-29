<?php

use Illuminate\Support\Facades\Route;
use Packages\WhatsAppNotifications\Controllers\AdminCommunicationController;
use Packages\WhatsAppNotifications\Controllers\WhatsAppWebhookController;

// Inbound webhook for delivery and read receipts
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp');

// Admin Communications Portal
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin/communications/whatsapp')->name('admin.communications.whatsapp.')->group(function () {
    Route::get('/', [AdminCommunicationController::class, 'index'])->name('index');
    Route::get('/templates', [AdminCommunicationController::class, 'templates'])->name('templates');
    Route::post('/templates/{id}', [AdminCommunicationController::class, 'updateTemplate'])->name('templates.update');
    Route::post('/test', [AdminCommunicationController::class, 'sendTest'])->name('test');
});
