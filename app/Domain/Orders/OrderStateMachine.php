<?php

namespace App\Domain\Orders;

use App\Core\Events\OrderStatusUpdated;
use App\Domain\Delivery\ShipmentService;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use InvalidArgumentException;

class OrderStateMachine
{
    public function __construct(
        protected ?ShipmentService $shipmentService = null
    ) {
        $this->shipmentService = $shipmentService ?? app(ShipmentService::class);
    }

    /**
     * Allowed state transitions map.
     */
    protected const ALLOWED_TRANSITIONS = [
        'pending_payment' => ['paid', 'confirmed', 'cancelled'],
        'paid' => ['confirmed', 'cancelled', 'refunded'],
        'confirmed' => ['picking', 'cancelled', 'refunded'],
        'picking' => ['packed', 'cancelled'],
        'packed' => ['dispatched', 'cancelled'],
        'dispatched' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled', 'returned'],
        'delivered' => ['returned', 'refunded'],
        'cancelled' => ['refunded'],
        'refunded' => [],
        'returned' => ['refunded'],
    ];

    /**
     * Check if transition is valid.
     */
    public function canTransition(Order $order, string $newStatus): bool
    {
        $current = $order->status;
        $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Execute status transition with audit history and event dispatching.
     */
    public function transitionTo(Order $order, string $newStatus, ?int $userId = null, ?string $note = null): Order
    {
        if (! $this->canTransition($order, $newStatus)) {
            throw new InvalidArgumentException("Illegal order status transition from [{$order->status}] to [{$newStatus}].");
        }

        $previous = $order->status;
        $order->status = $newStatus;

        if ($newStatus === 'delivered') {
            $order->fulfillment_status = 'fulfilled';
        } elseif ($newStatus === 'paid') {
            $order->payment_status = 'captured';
        } elseif ($newStatus === 'refunded') {
            $order->payment_status = 'refunded';
        }

        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'previous_status' => $previous,
            'new_status' => $newStatus,
            'note' => $note,
            'user_id' => $userId ?? auth()->id(),
        ]);

        if (in_array($newStatus, ['confirmed', 'packed'], true) && $order->shipments()->count() === 0) {
            $this->shipmentService->createShipmentForOrder($order);
        }

        event(new OrderStatusUpdated($order, $previous, $newStatus, $userId, $note));

        return $order;
    }
}
