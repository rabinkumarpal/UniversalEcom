<?php

namespace Packages\InvoiceGst;

use App\Core\Events\OrderStatusUpdated;
use App\Core\Registry\AddonRegistry;
use App\Domain\Checkout\Events\OrderPlaced;
use App\Models\Order;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Packages\InvoiceGst\Models\GstInvoice;
use Packages\InvoiceGst\Services\GstInvoiceService;

class GstInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GstInvoiceService::class, fn () => new GstInvoiceService);
    }

    public function boot(): void
    {
        // 1. Load isolated migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 2. Load web routes and views
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'invoice-gst');

        // 3. Register in AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new GstInvoiceAddon);

        // 4. Dynamically resolve relation on Order
        Order::resolveRelationUsing('gstInvoice', function (Order $order) {
            return $order->hasOne(GstInvoice::class);
        });

        // 5. Automatically generate GST Tax Invoice when order is placed and confirmed
        Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
            if ($event->order && in_array($event->order->status, ['confirmed', 'paid'])) {
                app(GstInvoiceService::class)->generateForOrder($event->order);
            }
        });

        Event::listen(OrderStatusUpdated::class, function (OrderStatusUpdated $event) {
            if ($event->order && in_array($event->newStatus, ['confirmed', 'paid', 'dispatched'])) {
                app(GstInvoiceService::class)->generateForOrder($event->order);
            }
        });
    }
}
