<?php

namespace App\Core\Events;

use App\Models\Cart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Cart $cart,
        public readonly array $totals
    ) {}
}
