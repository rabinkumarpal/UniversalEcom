<?php

namespace Packages\VendorMarketplace\Models;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_order_id',
        'order_item_id',
        'quantity',
        'unit_price',
        'commission_amount',
        'payout_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'commission_amount' => 'integer',
            'payout_amount' => 'integer',
        ];
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
