<?php

namespace Packages\WhatsAppNotifications;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;

class WhatsAppNotificationsAddon implements AddonInterface
{
    public function id(): string
    {
        return 'whatsapp-notifications';
    }

    public function name(): string
    {
        return 'WhatsApp & SMS Communications Gateway';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Multi-channel customer communications hub delivering real-time WhatsApp & SMS notifications for order confirmations, delivery OTP pins, live route tracking, digital POD handover receipts, and delivery exception alerts.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.whatsapp_notifications.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Addon boot hooks
    }
}
