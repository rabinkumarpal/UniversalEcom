<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice {{ $invoice->invoice_number }} — Universal Commerce</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; color: #000000 !important; font-size: 10pt; }
            .print-border { border: 1px solid #000000 !important; }
            .print-bg { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased p-4 sm:p-8 min-h-screen">
    <!-- Top Floating Toolbar (Hidden when printing) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ url()->previous() ?: route('account.orders') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back</span>
        </a>

        <div class="flex items-center gap-3">
            @if($invoice->isCancelled())
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300">
                    CANCELLED / CREDIT NOTE ISSUED
                </span>
            @endif

            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition shadow-md flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Formal Statutory Tax Invoice Document -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-300 rounded-xl shadow-xl overflow-hidden print:border print:shadow-none print:rounded-none">
        <!-- Document Header -->
        <div class="p-6 border-b border-slate-300 flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Tax Invoice (Rule 46 of CGST Rules)</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">{{ $invoice->seller_name }}</h1>
                <p class="text-xs text-slate-600 max-w-md mt-1">{{ $invoice->seller_address }}, {{ $invoice->seller_city }} — {{ $invoice->seller_pincode }}</p>
                <div class="text-xs text-slate-700 mt-1">
                    GSTIN: <strong class="font-mono text-slate-900">{{ $invoice->seller_gstin }}</strong> • State: <strong>{{ $invoice->seller_state }} ({{ $invoice->seller_state_code }})</strong>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black uppercase tracking-wider rounded-lg">
                    TAX INVOICE
                </div>
                <div class="mt-2 text-xs">
                    <span class="text-slate-500">Invoice No:</span>
                    <div class="font-mono font-black text-sm text-slate-900">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="text-xs mt-1">
                    <span class="text-slate-500">Date:</span>
                    <span class="font-bold text-slate-800">{{ $invoice->invoice_date->format('d/m/Y') }}</span>
                </div>
                <div class="text-xs mt-0.5">
                    <span class="text-slate-500">Order Ref:</span>
                    <span class="font-mono font-bold text-indigo-600">#{{ $invoice->order->order_number }}</span>
                </div>
            </div>
        </div>

        <!-- Supply & Customer Meta Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 border-b border-slate-300 text-xs">
            <!-- Bill To -->
            <div class="p-5 border-b sm:border-b-0 sm:border-r border-slate-300 space-y-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Details of Receiver (Billed To)</span>
                <div class="text-sm font-black text-slate-900">{{ $invoice->buyer_name }}</div>
                @if($invoice->buyer_gstin)
                    <div class="text-slate-700 font-mono">GSTIN: <strong>{{ $invoice->buyer_gstin }}</strong> (B2B Registered)</div>
                @else
                    <div class="text-slate-500 italic">Unregistered Consumer (B2C)</div>
                @endif
                <div class="text-slate-600">
                    {{ $invoice->buyer_billing_address['address_line_1'] ?? '' }},
                    {{ $invoice->buyer_billing_address['city'] ?? '' }}, {{ $invoice->buyer_billing_address['state'] ?? '' }} — {{ $invoice->buyer_billing_address['pincode'] ?? '' }}
                </div>
                @if($invoice->buyer_phone)
                    <div class="text-slate-500 font-mono">Phone: {{ $invoice->buyer_phone }}</div>
                @endif
            </div>

            <!-- Ship To & Supply Details -->
            <div class="p-5 space-y-1.5 bg-slate-50/50 print-bg">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Place of Supply &amp; Consignee (Shipped To)</span>
                <div class="text-sm font-black text-slate-900">{{ $invoice->buyer_shipping_address['recipient_name'] ?? $invoice->buyer_name }}</div>
                <div class="text-slate-600">
                    {{ $invoice->buyer_shipping_address['address_line_1'] ?? '' }},
                    {{ $invoice->buyer_shipping_address['city'] ?? '' }} — {{ $invoice->buyer_shipping_address['pincode'] ?? '' }}
                </div>
                <div class="pt-1 text-slate-700">
                    Place of Supply: <strong>{{ $invoice->place_of_supply_state }} (Code {{ $invoice->place_of_supply_state_code }})</strong>
                </div>
                <div class="flex items-center gap-3 text-[11px] pt-1">
                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-800 font-bold uppercase">
                        {{ $invoice->supply_type === 'INTRA_STATE' ? 'Intra-State Supply (CGST + SGST)' : 'Inter-State Supply (IGST)' }}
                    </span>
                    <span class="text-slate-500">Reverse Charge: <strong>{{ $invoice->reverse_charge ? 'Yes' : 'No' }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-black uppercase tracking-wider text-slate-600 print-bg">
                        <th class="py-2.5 px-3 w-8">#</th>
                        <th class="py-2.5 px-3">Description of Goods</th>
                        <th class="py-2.5 px-3 text-center">HSN/SAC</th>
                        <th class="py-2.5 px-3 text-center">Qty</th>
                        <th class="py-2.5 px-3 text-right">Rate (₹)</th>
                        <th class="py-2.5 px-3 text-right">Taxable Val (₹)</th>
                        @if($invoice->isIntraState())
                            <th class="py-2.5 px-3 text-right">CGST</th>
                            <th class="py-2.5 px-3 text-right">SGST</th>
                        @else
                            <th class="py-2.5 px-3 text-right">IGST</th>
                        @endif
                        <th class="py-2.5 px-3 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($invoice->items as $idx => $item)
                        <tr>
                            <td class="py-3 px-3 text-slate-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900">{{ $item->item_description }}</div>
                            </td>
                            <td class="py-3 px-3 text-center font-mono font-bold text-slate-700">{{ $item->hsn_code }}</td>
                            <td class="py-3 px-3 text-center font-mono">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format($item->unit_price / 100, 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold">{{ number_format($item->taxable_value / 100, 2) }}</td>

                            @if($invoice->isIntraState())
                                <td class="py-3 px-3 text-right font-mono text-[11px]">
                                    <div>{{ number_format($item->cgst_amount / 100, 2) }}</div>
                                    <span class="text-[9px] text-slate-400">({{ $item->cgst_rate }}%)</span>
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-[11px]">
                                    <div>{{ number_format($item->sgst_amount / 100, 2) }}</div>
                                    <span class="text-[9px] text-slate-400">({{ $item->sgst_rate }}%)</span>
                                </td>
                            @else
                                <td class="py-3 px-3 text-right font-mono text-[11px]">
                                    <div>{{ number_format($item->igst_amount / 100, 2) }}</div>
                                    <span class="text-[9px] text-slate-400">({{ $item->igst_rate }}%)</span>
                                </td>
                            @endif

                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900">
                                {{ number_format($item->total_amount / 100, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- HSN Statutory Summary Table & Totals Block -->
        <div class="border-t border-slate-300 grid grid-cols-1 md:grid-cols-12 text-xs">
            <!-- HSN Summary Left Column (7 cols) -->
            <div class="md:col-span-7 p-4 border-b md:border-b-0 md:border-r border-slate-300 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Statutory Tax Summary (By HSN/SAC)</span>
                <table class="w-full text-left text-[11px] border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold">
                            <th class="py-1">HSN</th>
                            <th class="py-1 text-right">Taxable (₹)</th>
                            @if($invoice->isIntraState())
                                <th class="py-1 text-right">CGST (₹)</th>
                                <th class="py-1 text-right">SGST (₹)</th>
                            @else
                                <th class="py-1 text-right">IGST (₹)</th>
                            @endif
                            <th class="py-1 text-right">Tax Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @foreach($invoice->hsnSummary() as $hsn)
                            <tr>
                                <td class="py-1.5 font-bold">{{ $hsn['hsn_code'] }}</td>
                                <td class="py-1.5 text-right">{{ number_format($hsn['taxable_value'] / 100, 2) }}</td>
                                @if($invoice->isIntraState())
                                    <td class="py-1.5 text-right">{{ number_format($hsn['cgst_amount'] / 100, 2) }}</td>
                                    <td class="py-1.5 text-right">{{ number_format($hsn['sgst_amount'] / 100, 2) }}</td>
                                @else
                                    <td class="py-1.5 text-right">{{ number_format($hsn['igst_amount'] / 100, 2) }}</td>
                                @endif
                                <td class="py-1.5 text-right font-bold text-slate-900">{{ number_format($hsn['total_tax'] / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="pt-2 border-t border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Amount Chargeable in Words:</span>
                    <div class="text-xs font-black text-slate-900 mt-0.5">{{ $invoice->amountInWords() }}</div>
                </div>
            </div>

            <!-- Grand Totals Right Column (5 cols) -->
            <div class="md:col-span-5 p-4 space-y-2 bg-slate-50/50 print-bg">
                <div class="flex justify-between py-1 border-b border-slate-200">
                    <span class="text-slate-600">Total Taxable Value:</span>
                    <span class="font-mono font-bold text-slate-900">₹{{ number_format($invoice->taxable_amount / 100, 2) }}</span>
                </div>

                @if($invoice->isIntraState())
                    <div class="flex justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-600">Central GST (CGST):</span>
                        <span class="font-mono text-slate-900">₹{{ number_format($invoice->cgst_amount / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-600">State GST (SGST):</span>
                        <span class="font-mono text-slate-900">₹{{ number_format($invoice->sgst_amount / 100, 2) }}</span>
                    </div>
                @else
                    <div class="flex justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-600">Integrated GST (IGST):</span>
                        <span class="font-mono text-slate-900">₹{{ number_format($invoice->igst_amount / 100, 2) }}</span>
                    </div>
                @endif

                @if($invoice->round_off_amount != 0)
                    <div class="flex justify-between py-1 border-b border-slate-200 text-slate-500">
                        <span>Round Off:</span>
                        <span class="font-mono">₹{{ number_format($invoice->round_off_amount / 100, 2) }}</span>
                    </div>
                @endif

                <div class="flex justify-between items-baseline pt-2 text-sm font-black text-slate-900">
                    <span>Invoice Total (INR):</span>
                    <span class="text-base font-black font-mono text-indigo-700">₹{{ number_format($invoice->total_amount / 100, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- E-Invoicing Verification & Signatory Footer -->
        <div class="p-6 border-t border-slate-300 grid grid-cols-1 sm:grid-cols-2 gap-6 items-end text-xs">
            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">E-Invoice Authentication</span>
                @if($invoice->irn_hash)
                    <div class="text-[9px] font-mono text-slate-500 break-all">
                        IRN: {{ $invoice->irn_hash }}
                    </div>
                @endif
                <div class="text-[10px] text-slate-500">
                    Certified that the particulars given above are true and correct.
                </div>
            </div>

            <div class="text-right space-y-12">
                <div class="text-xs font-bold text-slate-700">For {{ $invoice->seller_name }}</div>
                <div class="text-xs font-bold text-slate-900 pt-2 border-t border-slate-300 inline-block px-4">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>
