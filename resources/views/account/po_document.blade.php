<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order {{ $po->po_number }} — {{ $company->name }}</title>
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
        <a href="{{ route('account.b2b.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Corporate Account</span>
        </a>

        <div class="flex items-center gap-3">
            @if($po->isApproved())
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                    AUTHORIZED PURCHASE ORDER
                </span>
            @elseif($po->isPendingApproval())
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                    AWAITING SUPERVISOR APPROVAL
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300">
                    REQUISITION REJECTED
                </span>
            @endif

            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition shadow-md flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Formal Commercial Purchase Order Document -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-300 rounded-xl shadow-xl overflow-hidden print:border print:shadow-none print:rounded-none">
        <!-- Document Header -->
        <div class="p-6 border-b border-slate-300 flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Commercial Purchase Order</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">{{ $company->name }}</h1>
                <p class="text-xs text-slate-600 max-w-md mt-1">
                    {{ $company->billing_address['address_line_1'] ?? 'Corporate Headquarters' }},
                    {{ $company->billing_address['city'] ?? 'Bengaluru' }} — {{ $company->billing_address['pincode'] ?? '560001' }}
                </p>
                <div class="text-xs text-slate-700 mt-1">
                    GSTIN: <strong class="font-mono text-slate-900">{{ $company->tax_id ?? 'Commercial Registered' }}</strong>
                    • Company Code: <strong class="font-mono text-slate-900">{{ $company->company_code }}</strong>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black uppercase tracking-wider rounded-lg">
                    PURCHASE ORDER
                </div>
                <div class="mt-2 text-xs">
                    <span class="text-slate-500">PO Number:</span>
                    <div class="font-mono font-black text-sm text-slate-900">{{ $po->po_number }}</div>
                </div>
                <div class="text-xs mt-1">
                    <span class="text-slate-500">Issue Date:</span>
                    <span class="font-bold text-slate-800">{{ $po->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="text-xs mt-0.5">
                    <span class="text-slate-500">Order Ref:</span>
                    <span class="font-mono font-bold text-indigo-600">#{{ $order?->order_number ?? 'PENDING' }}</span>
                </div>
                <div class="text-xs mt-0.5">
                    <span class="text-slate-500">Payment Terms:</span>
                    <span class="font-bold text-slate-800">Net {{ $po->payment_terms_days }} Days</span>
                </div>
                @if($po->due_date)
                    <div class="text-xs mt-0.5">
                        <span class="text-slate-500">Payment Due:</span>
                        <span class="font-bold text-rose-700">{{ $po->due_date->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Supplier & Delivery Site Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 border-b border-slate-300 text-xs">
            <!-- Vendor / Supplier -->
            <div class="p-5 border-b sm:border-b-0 sm:border-r border-slate-300 space-y-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Vendor / Supplier</span>
                <div class="text-sm font-black text-slate-900">Universal Commerce Pvt Ltd</div>
                <div class="text-slate-600">Plot #12, Whitefield Industrial Zone, EPIP Phase 1, Bengaluru — 560066</div>
                <div class="text-slate-700 font-mono">GSTIN: <strong>29AAAAA0000A1Z5</strong></div>
                <div class="text-slate-500">Contact: support@universal-commerce.test</div>
            </div>

            <!-- Ship To / Site Delivery Address -->
            <div class="p-5 space-y-1.5 bg-slate-50/50 print-bg">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Delivery Site / Consignee</span>
                <div class="text-sm font-black text-slate-900">
                    {{ $order?->shipping_address_snapshot['recipient_name'] ?? ($company->name . ' Project Site') }}
                </div>
                <div class="text-slate-600">
                    {{ $order?->shipping_address_snapshot['address_line_1'] ?? ($company->shipping_address['address_line_1'] ?? 'Site Location') }},
                    {{ $order?->shipping_address_snapshot['city'] ?? ($company->shipping_address['city'] ?? '') }}
                    @if(!empty($order?->shipping_address_snapshot['pincode']))
                        — {{ $order->shipping_address_snapshot['pincode'] }}
                    @endif
                </div>
                @if(!empty($order?->shipping_address_snapshot['phone']))
                    <div class="text-slate-500 font-mono">Site Contact: {{ $order->shipping_address_snapshot['phone'] }}</div>
                @endif
                <div class="text-slate-500 text-[11px] pt-1">
                    Requisition Buyer: <strong>{{ $po->requester->name ?? 'Corporate Officer' }}</strong> ({{ $po->requester->email ?? '' }})
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300 text-[10px] font-black uppercase tracking-wider text-slate-600 print-bg">
                        <th class="py-2.5 px-3 w-8">#</th>
                        <th class="py-2.5 px-3">Description of Materials / Items</th>
                        <th class="py-2.5 px-3 text-center">SKU</th>
                        <th class="py-2.5 px-3 text-center">Qty</th>
                        <th class="py-2.5 px-3 text-right">Unit Rate (₹)</th>
                        <th class="py-2.5 px-3 text-right">Tax (₹)</th>
                        <th class="py-2.5 px-3 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @if($order && $order->items->isNotEmpty())
                        @foreach($order->items as $idx => $item)
                            <tr>
                                <td class="py-3 px-3 text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $item->product_name_snapshot ?? ($item->variant?->product?->name ?? 'Item') }}</div>
                                    @if($item->variant_name_snapshot)
                                        <div class="text-[10px] text-slate-500">{{ $item->variant_name_snapshot }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-700">{{ $item->sku_snapshot ?? ($item->variant?->sku ?? '-') }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold">{{ $item->quantity }}</td>
                                <td class="py-3 px-3 text-right font-mono">{{ number_format($item->unit_price / 100, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-slate-500">{{ number_format(($item->tax ?? 0) / 100, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono font-black text-slate-900">
                                    {{ number_format($item->line_total / 100, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="py-3 px-3 text-slate-400 font-mono">1</td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900">Requisition Items under Order #{{ $order?->order_number ?? $po->po_number }}</div>
                            </td>
                            <td class="py-3 px-3 text-center font-mono text-slate-500">-</td>
                            <td class="py-3 px-3 text-center font-mono font-bold">1</td>
                            <td class="py-3 px-3 text-right font-mono">{{ number_format($po->amount / 100, 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-500">0.00</td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900">{{ number_format($po->amount / 100, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals & Authorization Section -->
        <div class="border-t border-slate-300 grid grid-cols-1 md:grid-cols-12 text-xs">
            <!-- Left: Supervisor Authorization Audit Block (7 cols) -->
            <div class="md:col-span-7 p-5 border-b md:border-b-0 md:border-r border-slate-300 space-y-3">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Supervisor Authorization Ledger</span>
                
                @if($po->isApproved())
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Authorized by: {{ $po->approver->name ?? 'Corporate Supervisor' }}</span>
                        </div>
                        <div class="text-[11px] text-emerald-700">
                            Approved On: <strong>{{ $po->approved_at?->format('d/m/Y H:i:s') ?? 'Authorized' }}</strong>
                        </div>
                        <div class="text-[11px] text-slate-600 mt-1 italic">
                            "{{ $po->approval_notes ?? 'Approved under corporate credit line.' }}"
                        </div>
                    </div>
                @elseif($po->isPendingApproval())
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs space-y-1">
                        <div class="font-bold text-amber-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Status: Pending Supervisor Approval</span>
                        </div>
                        <p class="text-[11px] text-amber-800">
                            {{ $po->approval_notes ?? 'Awaiting supervisor authorization before warehouse fulfillment.' }}
                        </p>
                    </div>
                @else
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs space-y-1">
                        <div class="font-bold text-rose-900">Status: Rejected</div>
                        <p class="text-[11px] text-rose-700">
                            {{ $po->approval_notes ?? 'Requisition rejected by company supervisor.' }}
                        </p>
                    </div>
                @endif

                <div class="pt-2 text-[10px] text-slate-500 leading-relaxed">
                    This document represents an official purchase commitment financed through the commercial credit facility granted to <strong>{{ $company->name }}</strong>. Terms are subject to Master Services Agreement and invoice due dates.
                </div>
            </div>

            <!-- Right: Financial Grand Totals (5 cols) -->
            <div class="md:col-span-5 p-5 space-y-2 bg-slate-50/50 print-bg">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono">₹{{ number_format(($order?->subtotal ?? $po->amount) / 100, 2) }}</span>
                </div>

                @if($order && $order->tax_total > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>GST Taxes:</span>
                        <span class="font-mono">₹{{ number_format($order->tax_total / 100, 2) }}</span>
                    </div>
                @endif

                @if($order && $order->delivery_fee > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Delivery &amp; Freight:</span>
                        <span class="font-mono">₹{{ number_format($order->delivery_fee / 100, 2) }}</span>
                    </div>
                @endif

                @if($order && $order->discount_total > 0)
                    <div class="flex justify-between text-emerald-600 font-semibold">
                        <span>Enterprise Discount:</span>
                        <span class="font-mono">-₹{{ number_format($order->discount_total / 100, 2) }}</span>
                    </div>
                @endif

                <div class="pt-2 border-t border-slate-300 flex justify-between items-center text-sm font-black text-slate-900">
                    <span>Total Purchase Order:</span>
                    <span class="font-mono text-base text-indigo-700">₹{{ number_format($po->amount / 100, 2) }}</span>
                </div>

                <div class="pt-2 text-[10px] text-slate-400 text-right">
                    Settlement Due: <strong>Net {{ $po->payment_terms_days }} Days</strong>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
