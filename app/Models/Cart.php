<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Total item count.
     */
    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    /**
     * Authoritative raw subtotal before adjustments.
     */
    public function getRawSubtotalAttribute(): int
    {
        return (int) $this->items->sum(function (CartItem $item) {
            return $item->variant->getPriceForQuantity($item->quantity) * $item->quantity;
        });
    }
}
