<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
        'custom_options',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'custom_options' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Authoritative unit price based on tiered quantity.
     */
    public function getUnitPriceAttribute(): int
    {
        return $this->variant->getPriceForQuantity($this->quantity);
    }

    /**
     * Authoritative line total before promotion adjustments.
     */
    public function getLineTotalAttribute(): int
    {
        return $this->unit_price * $this->quantity;
    }
}
