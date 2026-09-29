<?php

namespace App\Core\Events;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockAdjusted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InventoryItem $item,
        public readonly InventoryMovement $movement
    ) {}
}
