<?php

namespace Packages\WhatsAppNotifications;

use App\Core\Events\OrderPlaced;
use App\Core\Registry\AddonRegistry;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Packages\WhatsAppNotifications\Models\NotificationLog;
use Packages\WhatsAppNotifications\Services\CommunicationDispatchService;
use Packages\WhatsAppNotifications\Services\WhatsAppGatewayClient;

class WhatsAppNotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppGatewayClient::class, fn () => new WhatsAppGatewayClient);
        $this->app->singleton(CommunicationDispatchService::class, function ($app) {
            return new CommunicationDispatchService($app->make(WhatsAppGatewayClient::class));
        });
    }

    public function boot(): void
    {
        // 1. Load isolated migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 2. Load web routes and views
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'whatsapp-notifications');

        // 3. Register in core AddonRegistry
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new WhatsAppNotificationsAddon);

        // 4. Dynamically resolve relationships without altering core schemas
        Order::resolveRelationUsing('notifications', function (Order $order) {
            return $order->hasMany(NotificationLog::class);
        });

        Shipment::resolveRelationUsing('notifications', function (Shipment $shipment) {
            return $shipment->hasMany(NotificationLog::class);
        });

        // 5. Trigger Order Confirmation WhatsApp notification on OrderPlaced
        Event::listen(OrderPlaced::class, function (OrderPlaced $event) {
            if ($event->order) {
                app(CommunicationDispatchService::class)->notifyOrderConfirmed($event->order);
            }
        });
    }
}
