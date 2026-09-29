<?php

namespace Packages\VendorMarketplace\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'order_id',
        'vendor_order_number',
        'status',
        'subtotal',
        'commission_amount',
        'vendor_payout',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'commission_amount' => 'integer',
            'vendor_payout' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorOrderItem::class);
    }
}
