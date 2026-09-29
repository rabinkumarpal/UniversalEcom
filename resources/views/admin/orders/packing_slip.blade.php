<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick & Pack Slip — {{ $order->order_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11px !important;
                padding: 0 !important;
            }
            .page-break {
                page-break-after: always;
            }
            .print-border {
                border-color: #000000 !important;
            }
        }
        @media screen {
            body {
                background-color: #f8fafc;
            }
        }
    </style>
</head>
<body class="text-slate-900 font-sans antialiased">

    <!-- Non-Printing Top Action Bar -->
    <div class="no-print bg-slate-900 text-white px-4 sm:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-50 shadow-md">
        <div class="flex items-center gap-3">
            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-indigo-600 text-white">WAREHOUSE OPERATION</span>
            <span class="text-sm font-bold">Pick &amp; Pack Slip: <span class="font-mono text-indigo-300">{{ $order->order_number }}</span></span>
            <span class="text-xs text-slate-400">({{ $order->items->sum('quantity') }} items to fulfill)</span>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.show', $order->order_number) }}" class="px-3.5 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 text-xs font-semibold text-slate-300 transition">
                &larr; Back to Order Details
            </a>
            <button onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Packing Slip</span>
            </button>
        </div>
    </div>

    <!-- Printable Slip Canvas -->
    <div class="max-w-4xl mx-auto my-6 sm:my-8 bg-white p-6 sm:p-10 border border-slate-200 print:border-0 print:m-0 print:p-4 rounded-xl shadow-xs print:shadow-none">
        
        <!-- Header & Barcode -->
        <div class="flex justify-between items-start border-b-2 border-slate-900 pb-5">
            <div>
                <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">Universal Central Logistics Depot</div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">WAREHOUSE PICK &amp; PACK SLIP</h1>
                <p class="text-xs text-slate-600 mt-1">Authoritative picking manifest and dispatch custody documentation</p>
            </div>
            <div class="text-right">
                <div class="font-mono text-xl font-black text-slate-900 tracking-wider">{{ $order->order_number }}</div>
                <!-- Simulated Industrial Barcode -->
                <div class="mt-1 font-mono text-[22px] tracking-[6px] select-none text-slate-800 leading-none">
                    ||| | |||| | ||| || |||
                </div>
                <div class="text-[10px] text-slate-500 font-mono mt-1">
                    Printed: {{ now()->format('Y-m-d H:i:s') }}
                </div>
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4 border-b border-slate-200 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Order Placed</span>
                <span class="font-bold text-slate-800">{{ $order->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Current Status</span>
                <span class="font-black uppercase text-indigo-700">{{ $order->status }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Payment Method</span>
                @php $pay = $order->payments->first(); @endphp
                <span class="font-bold text-slate-800 uppercase">{{ $pay?->gateway ?? 'COD' }} ({{ $order->payment_status }})</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Quantity</span>
                <span class="font-black text-slate-900 text-sm">{{ $order->items->sum('quantity') }} Units</span>
            </div>
        </div>

        <!-- Addresses & Dispatch Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-5 border-b border-slate-200 text-xs leading-relaxed">
            <!-- Shipping Destination -->
            @php $ship = $order->shipping_address_snapshot; @endphp
            <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">1. Delivery Destination &amp; Site Access</h3>
                <div class="font-bold text-slate-900 text-sm">{{ $ship['recipient_name'] ?? 'N/A' }}</div>
                @if(!empty($ship['phone']))
                    <div class="font-mono text-slate-700 font-semibold mt-0.5">📞 Phone: {{ $ship['phone'] }}</div>
                @endif
                <div class="text-slate-700 mt-1">
                    {{ $ship['address_line_1'] ?? '' }}
                    @if(!empty($ship['address_line_2']))
                        <br>{{ $ship['address_line_2'] }}
                    @endif
                    <br>{{ $ship['city'] ?? '' }}, {{ $ship['state'] ?? '' }} — <strong>{{ $ship['pincode'] ?? '' }}</strong>
                </div>
                @if(!empty($ship['is_site_address']))
                    <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        ⚠ Heavy Vehicle / Project Site Gate
                    </div>
                @endif
                @if(!empty($order->notes))
                    <div class="mt-2 text-[11px] p-2 bg-yellow-50 border border-yellow-200 rounded text-yellow-900">
                        <strong>Site Instructions:</strong> {{ $order->notes }}
                    </div>
                @endif
            </div>

            <!-- Warehouse Depot & Dispatch Info -->
            @php $shipment = $order->shipments->first(); @endphp
            <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">2. Fulfillment Depot &amp; Fleet Dispatch</h3>
                <div class="space-y-1.5">
                    <div>
                        <span class="text-slate-500">Origin Warehouse:</span>
                        <strong class="text-slate-900">{{ $shipment?->warehouse?->name ?? 'Central Regional Depot (DEPOT-01)' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500">Shipment Number:</span>
                        <span class="font-mono font-bold text-slate-900">{{ $shipment?->shipment_number ?? 'PENDING ALLOCATION' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Assigned Driver / Fleet:</span>
                        <strong class="text-slate-900">{{ $shipment?->driver?->user?->name ?? ($shipment?->carrier_or_driver_name ?? 'Unassigned Fleet') }}</strong>
                        @if($shipment?->driver_phone)
                            <span class="font-mono text-slate-600">({{ $shipment->driver_phone }})</span>
                        @endif
                    </div>
                    @if($shipment?->delivery_otp)
                        <div>
                            <span class="text-slate-500">Gate POD Security OTP:</span>
                            <span class="font-mono font-black text-slate-900 text-xs bg-slate-200 px-1.5 py-0.5 rounded">{{ $shipment->delivery_otp }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="text-slate-500">Customer Account:</span>
                        <span class="font-semibold text-slate-800">{{ $order->user?->name ?? 'Guest Buyer' }} ({{ $order->user?->email ?? 'N/A' }})</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pick Items Table -->
        <div class="py-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3 flex items-center justify-between">
                <span>3. Warehouse Pick &amp; Inspection Checklist</span>
                <span class="text-[11px] font-normal text-slate-500">Verify SKU, Bin Location, and Unit Count</span>
            </h3>

            <table class="w-full text-left text-xs border border-slate-200 print-border">
                <thead class="bg-slate-100 print:bg-slate-200 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200 print-border">
                    <tr>
                        <th class="p-2.5 text-center w-10">Pick</th>
                        <th class="p-2.5 w-32">Bin / Location</th>
                        <th class="p-2.5 w-36">SKU Code</th>
                        <th class="p-2.5">Item Description &amp; Variant</th>
                        <th class="p-2.5 text-center w-16">Ordered</th>
                        <th class="p-2.5 text-center w-24">Qty Picked</th>
                        <th class="p-2.5 text-center w-24">QC Checked</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 print-border">
                    @foreach($order->items as $idx => $item)
                        @php
                            $inv = $item->variant?->inventoryItems?->first();
                            $binCode = $inv ? ($inv->warehouse?->code . ' / BIN-' . str_pad($inv->id, 3, '0', STR_PAD_LEFT)) : 'DEPOT-MAIN';
                        @endphp
                        <tr class="{{ $idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white' }}">
                            <td class="p-2.5 text-center align-middle">
                                <div class="w-4 h-4 border-2 border-slate-800 rounded mx-auto"></div>
                            </td>
                            <td class="p-2.5 font-mono text-[11px] font-bold text-slate-700">
                                {{ $binCode }}
                            </td>
                            <td class="p-2.5 font-mono text-[11px] font-bold text-slate-900">
                                {{ $item->sku_snapshot }}
                            </td>
                            <td class="p-2.5">
                                <div class="font-bold text-slate-900 text-xs">{{ $item->product_name_snapshot }}</div>
                                <div class="text-[11px] text-slate-500">{{ $item->variant_name_snapshot }}</div>
                            </td>
                            <td class="p-2.5 text-center font-black text-slate-900 text-sm">
                                {{ $item->quantity }}
                            </td>
                            <td class="p-2.5 text-center text-slate-400">
                                <div class="border-b border-slate-400 w-14 mx-auto pb-1 font-mono text-slate-800 font-bold">&nbsp;</div>
                            </td>
                            <td class="p-2.5 text-center text-slate-400">
                                <div class="w-4 h-4 border-2 border-slate-400 rounded mx-auto"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Packing & Warehouse Sign-off Block -->
        <div class="mt-6 pt-6 border-t-2 border-slate-900 text-xs">
            <h4 class="text-[10px] font-black uppercase tracking-wider text-slate-500 mb-4">4. Verification &amp; Custody Handover Sign-Off</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Picker -->
                <div class="border border-slate-300 rounded-lg p-3 space-y-3">
                    <div class="font-bold text-slate-900 border-b border-slate-200 pb-1">Warehouse Picker</div>
                    <div class="text-[11px] text-slate-500 space-y-2">
                        <div>Name: ______________________</div>
                        <div>Sign: ______________________</div>
                        <div>Date: _______ Time: ________</div>
                    </div>
                </div>

                <!-- QC / Packer -->
                <div class="border border-slate-300 rounded-lg p-3 space-y-3">
                    <div class="font-bold text-slate-900 border-b border-slate-200 pb-1">QC &amp; Packing Specialist</div>
                    <div class="text-[11px] text-slate-500 space-y-2">
                        <div>Name: ______________________</div>
                        <div>Sign: ______________________</div>
                        <div>Packages / Bags: __________</div>
                    </div>
                </div>

                <!-- Dispatch / Driver -->
                <div class="border border-slate-300 rounded-lg p-3 space-y-3">
                    <div class="font-bold text-slate-900 border-b border-slate-200 pb-1">Fleet Dispatch / Gate Exit</div>
                    <div class="text-[11px] text-slate-500 space-y-2">
                        <div>Driver: ____________________</div>
                        <div>Vehicle Reg: _______________</div>
                        <div>Gate Out Time: _____________</div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-between items-center text-[10px] text-slate-400 font-mono">
                <span>Universal Warehouse Management &bull; Multi-Depot Operations</span>
                <span>Document ID: WPK-{{ $order->order_number }}-{{ now()->format('Ymd') }}</span>
            </div>
        </div>

    </div>

</body>
</html>
