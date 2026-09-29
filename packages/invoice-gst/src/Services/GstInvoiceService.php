<?php

namespace Packages\InvoiceGst\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\InvoiceGst\Models\GstInvoice;
use Packages\InvoiceGst\Models\GstInvoiceItem;

class GstInvoiceService
{
    /**
     * Standard Indian State to GST State Code mapping.
     */
    protected const STATE_CODES = [
        'JAMMU AND KASHMIR' => '01',
        'HIMACHAL PRADESH' => '02',
        'PUNJAB' => '03',
        'CHANDIGARH' => '04',
        'UTTARAKHAND' => '05',
        'HARYANA' => '06',
        'DELHI' => '07',
        'RAJASTHAN' => '08',
        'UTTAR PRADESH' => '09',
        'BIHAR' => '10',
        'SIKKIM' => '11',
        'ARUNACHAL PRADESH' => '12',
        'NAGALAND' => '13',
        'MANIPUR' => '14',
        'MIZORAM' => '15',
        'TRIPURA' => '16',
        'MEGHALAYA' => '17',
        'ASSAM' => '18',
        'WEST BENGAL' => '19',
        'JHARKHAND' => '20',
        'ODISHA' => '21',
        'CHHATTISGARH' => '22',
        'MADHYA PRADESH' => '23',
        'GUJARAT' => '24',
        'DAMAN AND DIU' => '25',
        'DADRA AND NAGAR HAVELI' => '26',
        'MAHARASHTRA' => '27',
        'ANDHRA PRADESH' => '28',
        'KARNATAKA' => '29',
        'GOA' => '30',
        'LAKSHADWEEP' => '31',
        'KERALA' => '32',
        'TAMIL NADU' => '33',
        'PUDUCHERRY' => '34',
        'ANDAMAN AND NICOBAR' => '35',
        'TELANGANA' => '36',
    ];

    /**
     * Statutory HSN code fallback mapping by product category keywords.
     */
    protected const HSN_MAP = [
        'cement' => '2523',
        'steel' => '7214',
        'tmt' => '7214',
        'rebar' => '7214',
        'block' => '6810',
        'brick' => '6901',
        'concrete' => '6810',
        'pipe' => '3917',
        'plumbing' => '3917',
        'paint' => '3208',
        'wire' => '8544',
        'electrical' => '8544',
        'tile' => '6907',
        'sand' => '2505',
        'aggregate' => '2517',
    ];

    /**
     * Authoritatively generate or retrieve a statutory GST Tax Invoice for an order.
     */
    public function generateForOrder(Order $order, array $overrides = []): GstInvoice
    {
        // 1. Return existing GST invoice if already issued (idempotency)
        $existing = GstInvoice::where('order_id', $order->id)->first();
        if ($existing && empty($overrides['force_reissue'])) {
            return $existing;
        }

        $invoiceDate = $overrides['invoice_date'] ?? now();
        $fy = $this->calculateFinancialYear($invoiceDate);
        $numberData = $this->generateNextInvoiceNumber($fy);

        // 2. Seller details (default platform entity or vendor attribution)
        $sellerState = $overrides['seller_state'] ?? config('invoice.seller_state', 'Karnataka');
        $sellerStateCode = $this->resolveStateCode($sellerState);
        $sellerGstin = $overrides['seller_gstin'] ?? config('invoice.seller_gstin', '29AAAAA0000A1Z5');
        $sellerName = $overrides['seller_name'] ?? config('invoice.seller_name', 'Universal Commerce Pvt Ltd');
        $sellerAddress = $overrides['seller_address'] ?? 'Plot #12, Whitefield Industrial Zone, EPIP Phase 1';
        $sellerCity = $overrides['seller_city'] ?? 'Bengaluru';
        $sellerPincode = $overrides['seller_pincode'] ?? '560066';

        // 3. Buyer details & B2B / B2C detection
        $shipping = $order->shipping_address_snapshot ?? [];
        $billing = $order->billing_address_snapshot ?? $shipping;

        $destinationState = $shipping['state'] ?? ($billing['state'] ?? 'Karnataka');
        $destinationStateCode = $this->resolveStateCode($destinationState);

        $isB2b = false;
        $buyerGstin = null;
        $buyerName = $billing['recipient_name'] ?? ($shipping['recipient_name'] ?? $order->user?->name);

        // Detect if order belongs to a B2B company
        if (class_exists(CompanyUser::class)) {
            $companyUser = CompanyUser::where('user_id', $order->user_id)
                ->with('company')
                ->first();
            if ($companyUser && $companyUser->company && $companyUser->company->isActive()) {
                $isB2b = true;
                $buyerName = $companyUser->company->name ?? ($companyUser->company->legal_name ?? $buyerName);
                $buyerGstin = $companyUser->company->tax_id;
            }
        }

        // 4. Supply Type: Intra-State vs Inter-State
        $supplyType = ($sellerStateCode === $destinationStateCode) ? 'INTRA_STATE' : 'INTER_STATE';

        // 5. Generate IRN hash & QR payload for E-Invoicing standard
        $irnHash = hash('sha256', $sellerGstin.$fy.'INV'.$numberData['invoice_number']);
        $qrPayload = json_encode([
            'seller_gstin' => $sellerGstin,
            'buyer_gstin' => $buyerGstin,
            'doc_no' => $numberData['invoice_number'],
            'doc_date' => $invoiceDate->format('d/m/Y'),
            'tot_val' => number_format($order->grand_total / 100, 2, '.', ''),
            'irn' => $irnHash,
        ]);

        // 6. Create parent GstInvoice record
        $invoice = GstInvoice::create([
            'order_id' => $order->id,
            'invoice_number' => $numberData['invoice_number'],
            'financial_year' => $fy,
            'sequence_number' => $numberData['sequence'],
            'invoice_date' => $invoiceDate,
            'supply_type' => $supplyType,
            'is_b2b' => $isB2b,
            'reverse_charge' => false,
            'seller_name' => $sellerName,
            'seller_gstin' => $sellerGstin,
            'seller_address' => $sellerAddress,
            'seller_city' => $sellerCity,
            'seller_state' => $sellerState,
            'seller_state_code' => $sellerStateCode,
            'seller_pincode' => $sellerPincode,
            'buyer_name' => $buyerName,
            'buyer_gstin' => $buyerGstin,
            'buyer_phone' => $shipping['phone'] ?? $billing['phone'] ?? null,
            'buyer_billing_address' => $billing,
            'buyer_shipping_address' => $shipping,
            'place_of_supply_state' => $destinationState,
            'place_of_supply_state_code' => $destinationStateCode,
            'total_amount' => $order->grand_total,
            'irn_hash' => $irnHash,
            'qr_code_payload' => $qrPayload,
            'status' => 'issued',
        ]);

        // 7. Calculate and insert GST Invoice Items
        $totTaxable = 0;
        $totCgst = 0;
        $totSgst = 0;
        $totIgst = 0;

        foreach ($order->items as $orderItem) {
            $gstRate = $this->resolveGstRate($orderItem);
            $hsnCode = $this->resolveHsnCode($orderItem);

            // Unit Price and line taxable value in paise
            $unitPrice = $orderItem->unit_price;
            $qty = $orderItem->quantity;
            $discount = $orderItem->discount ?? 0;
            $taxableValue = max(0, ($unitPrice * $qty) - $discount);

            $cgstRate = 0.00;
            $cgstAmt = 0;
            $sgstRate = 0.00;
            $sgstAmt = 0;
            $igstRate = 0.00;
            $igstAmt = 0;

            if ($supplyType === 'INTRA_STATE') {
                $cgstRate = round($gstRate / 2, 2);
                $sgstRate = round($gstRate / 2, 2);
                $cgstAmt = (int) round(($taxableValue * $cgstRate) / 100);
                $sgstAmt = (int) round(($taxableValue * $sgstRate) / 100);
            } else {
                $igstRate = $gstRate;
                $igstAmt = (int) round(($taxableValue * $igstRate) / 100);
            }

            $lineTotal = $taxableValue + $cgstAmt + $sgstAmt + $igstAmt;

            $totTaxable += $taxableValue;
            $totCgst += $cgstAmt;
            $totSgst += $sgstAmt;
            $totIgst += $igstAmt;

            GstInvoiceItem::create([
                'gst_invoice_id' => $invoice->id,
                'order_item_id' => $orderItem->id,
                'item_description' => $orderItem->product_name_snapshot.' ('.$orderItem->variant_name_snapshot.')',
                'hsn_code' => $hsnCode,
                'quantity' => $qty,
                'unit' => $orderItem->variant?->unit ?? 'unit',
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'taxable_value' => $taxableValue,
                'gst_rate' => $gstRate,
                'cgst_rate' => $cgstRate,
                'cgst_amount' => $cgstAmt,
                'sgst_rate' => $sgstRate,
                'sgst_amount' => $sgstAmt,
                'igst_rate' => $igstRate,
                'igst_amount' => $igstAmt,
                'total_amount' => $lineTotal,
            ]);
        }

        // 8. Update totals on invoice record
        $invoice->update([
            'taxable_amount' => $totTaxable,
            'cgst_amount' => $totCgst,
            'sgst_amount' => $totSgst,
            'igst_amount' => $totIgst,
            'round_off_amount' => max(0, $order->grand_total - ($totTaxable + $totCgst + $totSgst + $totIgst)),
            'total_amount' => $order->grand_total,
        ]);

        return $invoice->fresh(['items', 'order']);
    }

    /**
     * Calculate Indian Financial Year (Apr 1 to Mar 31).
     */
    public function calculateFinancialYear(Carbon $date): string
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 4) {
            $nextYearShort = substr((string) ($year + 1), -2);

            return "{$year}-{$nextYearShort}";
        }

        $yearShort = substr((string) $year, -2);
        $prevYear = $year - 1;

        return "{$prevYear}-{$yearShort}";
    }

    /**
     * Generate continuous, gap-free financial year sequence (e.g. INV/2026-27/0001).
     */
    public function generateNextInvoiceNumber(string $fy): array
    {
        $maxSeq = GstInvoice::where('financial_year', $fy)->max('sequence_number') ?? 0;
        $nextSeq = $maxSeq + 1;
        $padded = sprintf('%04d', $nextSeq);

        return [
            'sequence' => $nextSeq,
            'invoice_number' => "INV/{$fy}/{$padded}",
        ];
    }

    /**
     * Resolve 2-digit GST state code.
     */
    public function resolveStateCode(?string $stateName): string
    {
        if (empty($stateName)) {
            return '29'; // Default Karnataka
        }

        $clean = strtoupper(trim($stateName));
        if (isset(self::STATE_CODES[$clean])) {
            return self::STATE_CODES[$clean];
        }

        // Check if already a 2-digit code
        if (is_numeric($clean) && strlen($clean) === 2) {
            return $clean;
        }

        // Partial match
        foreach (self::STATE_CODES as $state => $code) {
            if (str_contains($clean, $state) || str_contains($state, $clean)) {
                return $code;
            }
        }

        return '29';
    }

    /**
     * Derive statutory HSN/SAC code from order item details.
     */
    public function resolveHsnCode(OrderItem $item): string
    {
        $name = strtolower($item->product_name_snapshot.' '.$item->variant_name_snapshot);

        foreach (self::HSN_MAP as $keyword => $hsn) {
            if (str_contains($name, $keyword)) {
                return $hsn;
            }
        }

        return '9999'; // General standard merchandise
    }

    /**
     * Resolve GST Rate percentage (e.g. 18.00).
     */
    public function resolveGstRate(OrderItem $item): float
    {
        if ($item->variant && $item->variant->taxClass) {
            return (float) $item->variant->taxClass->rate_percentage;
        }

        return 18.00; // Default 18% standard GST rate
    }

    /**
     * Cancel an issued tax invoice with a reason.
     */
    public function cancelInvoice(GstInvoice $invoice, string $reason): GstInvoice
    {
        $invoice->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $invoice;
    }
}
