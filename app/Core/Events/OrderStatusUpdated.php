<?php

namespace App\Core\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?int $userId = null,
        public readonly ?string $note = null
    ) {}
}
