<?php

namespace Packages\InvoiceGst\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GstInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'financial_year',
        'sequence_number',
        'invoice_date',
        'supply_type',
        'is_b2b',
        'reverse_charge',
        'seller_name',
        'seller_gstin',
        'seller_address',
        'seller_city',
        'seller_state',
        'seller_state_code',
        'seller_pincode',
        'buyer_name',
        'buyer_gstin',
        'buyer_phone',
        'buyer_billing_address',
        'buyer_shipping_address',
        'place_of_supply_state',
        'place_of_supply_state_code',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'cess_amount',
        'round_off_amount',
        'total_amount',
        'irn_hash',
        'qr_code_payload',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_b2b' => 'boolean',
            'reverse_charge' => 'boolean',
            'buyer_billing_address' => 'array',
            'buyer_shipping_address' => 'array',
            'taxable_amount' => 'integer',
            'cgst_amount' => 'integer',
            'sgst_amount' => 'integer',
            'igst_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GstInvoiceItem::class);
    }

    public function isIntraState(): bool
    {
        return $this->supply_type === 'INTRA_STATE';
    }

    public function isInterState(): bool
    {
        return $this->supply_type === 'INTER_STATE';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Compute statutory HSN/SAC summary aggregation across line items.
     */
    public function hsnSummary(): array
    {
        $summary = [];

        foreach ($this->items as $item) {
            $hsn = $item->hsn_code ?: '9999';

            if (! isset($summary[$hsn])) {
                $summary[$hsn] = [
                    'hsn_code' => $hsn,
                    'taxable_value' => 0,
                    'gst_rate' => $item->gst_rate,
                    'cgst_rate' => $item->cgst_rate,
                    'cgst_amount' => 0,
                    'sgst_rate' => $item->sgst_rate,
                    'sgst_amount' => 0,
                    'igst_rate' => $item->igst_rate,
                    'igst_amount' => 0,
                    'total_tax' => 0,
                ];
            }

            $summary[$hsn]['taxable_value'] += $item->taxable_value;
            $summary[$hsn]['cgst_amount'] += $item->cgst_amount;
            $summary[$hsn]['sgst_amount'] += $item->sgst_amount;
            $summary[$hsn]['igst_amount'] += $item->igst_amount;
            $summary[$hsn]['total_tax'] += ($item->cgst_amount + $item->sgst_amount + $item->igst_amount);
        }

        return array_values($summary);
    }

    /**
     * Convert invoice grand total into Indian Rupees words.
     */
    public function amountInWords(): string
    {
        $rupees = (int) floor($this->total_amount / 100);
        $paise = $this->total_amount % 100;

        $words = $this->numberToWordsIndian($rupees).' Rupees';
        if ($paise > 0) {
            $words .= ' and '.$this->numberToWordsIndian($paise).' Paise';
        }

        return $words.' Only';
    }

    protected function numberToWordsIndian(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number < 20) {
            return $units[$number];
        }

        if ($number < 100) {
            return $tens[(int) ($number / 10)].($number % 10 ? ' '.$units[$number % 10] : '');
        }

        if ($number < 1000) {
            return $units[(int) ($number / 100)].' Hundred'.($number % 100 ? ' '.$this->numberToWordsIndian($number % 100) : '');
        }

        if ($number < 100000) {
            return $this->numberToWordsIndian((int) ($number / 1000)).' Thousand'.($number % 1000 ? ' '.$this->numberToWordsIndian($number % 1000) : '');
        }

        if ($number < 10000000) {
            return $this->numberToWordsIndian((int) ($number / 100000)).' Lakh'.($number % 100000 ? ' '.$this->numberToWordsIndian($number % 100000) : '');
        }

        return $this->numberToWordsIndian((int) ($number / 10000000)).' Crore'.($number % 10000000 ? ' '.$this->numberToWordsIndian($number % 10000000) : '');
    }
}
