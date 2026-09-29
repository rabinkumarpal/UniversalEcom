<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing Slip #{{ $vendorOrder->vendor_order_number }} — {{ $vendor->business_name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; color: #000000 !important; font-size: 10pt; }
            .print-border { border: 1px solid #000000 !important; }
            .print-bg { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased p-4 sm:p-8 min-h-screen">
    <!-- Top Action Toolbar -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('vendor.orders.show', $vendorOrder->id) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            &larr; Back to Order Details
        </a>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-black transition shadow-md flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Packing Slip</span>
            </button>
        </div>
    </div>

    <!-- Formal Packing Slip Container -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-300 rounded-xl shadow-lg overflow-hidden print:border print:shadow-none print:rounded-none">
        <!-- Header -->
        <div class="p-6 border-b border-slate-300 flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-600">Vendor Fulfillment Center</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">{{ $vendor->display_name ?: $vendor->legal_name }}</h1>
                <p class="text-xs text-slate-600 max-w-md mt-1">
                    {{ $vendor->contact_address ?? 'Depot Dispatch Facility' }}
                </p>
                <div class="text-xs text-slate-700 mt-1">
                    Email: <strong>{{ $vendor->email }}</strong>
                    @if($vendor->phone)
                        • Phone: <strong>{{ $vendor->phone }}</strong>
                    @endif
                    @if($vendor->tax_id)
                        • GSTIN: <strong class="font-mono">{{ $vendor->tax_id }}</strong>
                    @endif
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-1 bg-amber-500 text-slate-950 text-xs font-black uppercase tracking-wider rounded-lg">
                    PACKING SLIP &amp; MANIFEST
                </div>
                <div class="mt-2 text-xs">
                    <span class="text-slate-500">Vendor Order:</span>
                    <div class="font-mono font-black text-sm text-slate-900">{{ $vendorOrder->vendor_order_number }}</div>
                </div>
                <div class="text-xs mt-0.5">
                    <span class="text-slate-500">Platform Order:</span>
                    <span class="font-mono font-bold text-indigo-600">#{{ $vendorOrder->order->order_number }}</span>
                </div>
                <div class="text-xs mt-0.5">
                    <span class="text-slate-500">Date:</span>
                    <span class="font-bold text-slate-800">{{ $vendorOrder->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Destination Address Box -->
        <div class="p-6 border-b border-slate-300 bg-slate-50/50 print-bg">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1">Customer Delivery Destination</span>
            @if(!empty($vendorOrder->order->shipping_address_snapshot))
                @php $addr = $vendorOrder->order->shipping_address_snapshot; @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="text-sm font-bold text-slate-900">{{ $addr['recipient_name'] ?? ($addr['full_name'] ?? 'Customer') }}</div>
                        @if(!empty($addr['phone']))
                            <div class="text-slate-600 font-mono mt-0.5">Phone: {{ $addr['phone'] }}</div>
                        @endif
                    </div>
                    <div class="text-slate-700">
                        <div>{{ $addr['address_line_1'] ?? ($addr['address_line1'] ?? '') }}</div>
                        @if(!empty($addr['address_line_2']) || !empty($addr['address_line2']))
                            <div>{{ $addr['address_line_2'] ?? $addr['address_line2'] }}</div>
                        @endif
                        <div class="font-semibold text-slate-900 mt-0.5">
                            {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} — {{ $addr['pincode'] ?? ($addr['postal_code'] ?? '') }}
                        </div>
                    </div>
                </div>
            @else
                <p class="text-xs text-slate-500">Direct warehouse delivery or standard site address.</p>
            @endif
        </div>

        <!-- Packing Line Items Table -->
        <div class="p-6 border-b border-slate-300">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-3">Items To Pick &amp; Pack</h2>
            <table class="w-full text-left text-xs border border-slate-200">
                <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="p-2.5 text-center w-12">Verify</th>
                        <th class="p-2.5">SKU</th>
                        <th class="p-2.5">Product Description</th>
                        <th class="p-2.5">Variant</th>
                        <th class="p-2.5 text-center w-24">Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($vendorOrder->items as $item)
                        <tr>
                            <td class="p-2.5 text-center">
                                <div class="w-4 h-4 border-2 border-slate-400 rounded mx-auto"></div>
                            </td>
                            <td class="p-2.5 font-mono font-bold text-slate-800">{{ $item->orderItem->sku_snapshot ?? ($item->orderItem->sku ?? '-') }}</td>
                            <td class="p-2.5 font-bold text-slate-900">{{ $item->orderItem->product_name_snapshot ?? ($item->orderItem->product_title ?? 'Product Item') }}</td>
                            <td class="p-2.5 text-slate-600">{{ $item->orderItem->variant_name_snapshot ?? ($item->orderItem->variant_title ?? 'Standard') }}</td>
                            <td class="p-2.5 text-center font-mono font-black text-sm text-slate-900">{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Verification Sign-off Box -->
        <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-6 text-xs text-slate-600">
            <div class="border border-slate-200 rounded-lg p-3 space-y-4">
                <div class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Picker Details</div>
                <div class="space-y-2 text-[11px]">
                    <div>Name: _____________________</div>
                    <div>Date: _____________________</div>
                    <div>Time: _____________________</div>
                </div>
            </div>

            <div class="border border-slate-200 rounded-lg p-3 space-y-4">
                <div class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Packer Details</div>
                <div class="space-y-2 text-[11px]">
                    <div>Total Parcels: _____________</div>
                    <div>Packed By: _________________</div>
                    <div>Seal Number: ______________</div>
                </div>
            </div>

            <div class="border border-slate-200 rounded-lg p-3 space-y-4">
                <div class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Quality Inspection</div>
                <div class="space-y-2 text-[11px]">
                    <div>Inspected: [  ] Pass  [  ] Hold</div>
                    <div>Sign: _____________________</div>
                    <div>Waybill / Transporter: _____</div>
                </div>
            </div>
        </div>

        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 text-center text-[10px] text-slate-500">
            Universal Commerce Vendor Fulfillment Manifest • Please include this document inside the shipment parcel.
        </div>
    </div>
</body>
</html>
