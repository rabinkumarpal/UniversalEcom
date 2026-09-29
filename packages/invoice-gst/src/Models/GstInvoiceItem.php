<?php

namespace Packages\InvoiceGst\Models;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'gst_invoice_id',
        'order_item_id',
        'item_description',
        'hsn_code',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'taxable_value',
        'gst_rate',
        'cgst_rate',
        'cgst_amount',
        'sgst_rate',
        'sgst_amount',
        'igst_rate',
        'igst_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'discount_amount' => 'integer',
            'taxable_value' => 'integer',
            'gst_rate' => 'float',
            'cgst_rate' => 'float',
            'cgst_amount' => 'integer',
            'sgst_rate' => 'float',
            'sgst_amount' => 'integer',
            'igst_rate' => 'float',
            'igst_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(GstInvoice::class, 'gst_invoice_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
