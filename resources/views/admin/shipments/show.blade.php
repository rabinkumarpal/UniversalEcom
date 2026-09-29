@extends('layouts.admin')

@section('title', 'Shipment ' . $shipment->shipment_number)
@section('header_title', 'Consignment Details: ' . $shipment->shipment_number)
@section('header_subtitle', 'Order #' . $shipment->order->order_number . ' • Manage dispatch, driver tracking, and site Proof-of-Delivery')

@section('content')
<div class="space-y-6">
    <!-- Back Link -->
    <div>
        <a href="{{ route('admin.shipments.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 transition">
            &larr; Back to Shipments &amp; Logistics
        </a>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold">
            {{ session('warning') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Details: Left Column (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Consignment Status Header -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4 shadow-xs transition-colors">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Shipment Number</span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ $shipment->shipment_number }}</h2>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Linked to Order: <a href="{{ route('admin.orders', ['search' => $shipment->order->order_number]) }}" class="text-indigo-600 dark:text-indigo-400 font-mono font-bold hover:underline">{{ $shipment->order->order_number }}</a>
                    </div>
                </div>
                <div>
                    @php
                        $badgeStyles = [
                            'pending' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                            'picking' => 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                            'packed' => 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                            'out_for_delivery' => 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                            'delivered' => 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                            'failed' => 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border {{ $badgeStyles[$shipment->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        {{ str_replace('_', ' ', $shipment->status) }}
                    </span>
                </div>
            </div>

            <!-- Destination & Site Information -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs transition-colors">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Site Destination Snapshot</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="text-slate-500 font-medium">Recipient Name</div>
                        <div class="text-slate-900 dark:text-white font-bold mt-0.5">{{ $shipment->order->shipping_address_snapshot['recipient_name'] ?? 'Recipient' }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 font-medium">Contact Phone</div>
                        <div class="text-slate-900 dark:text-white font-mono mt-0.5">{{ $shipment->order->shipping_address_snapshot['phone'] ?? 'N/A' }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <div class="text-slate-500 font-medium">Delivery Address &amp; Pincode</div>
                        <div class="text-slate-700 dark:text-slate-300 mt-0.5">
                            {{ $shipment->order->shipping_address_snapshot['address_line_1'] ?? '' }}
                            @if(!empty($shipment->order->shipping_address_snapshot['address_line_2']))
                                , {{ $shipment->order->shipping_address_snapshot['address_line_2'] }}
                            @endif
                            , {{ $shipment->order->shipping_address_snapshot['city'] ?? '' }}, {{ $shipment->order->shipping_address_snapshot['state'] ?? '' }}
                            — <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $shipment->order->shipping_address_snapshot['pincode'] ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Pick-List -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs transition-colors">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Manifest Pick-List Items</h3>
                    <span class="text-xs text-slate-500">{{ $shipment->order->items->count() }} line items</span>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs">
                    @foreach($shipment->order->items as $item)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white">{{ $item->product_name_snapshot }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Variant: <span class="text-slate-700 dark:text-slate-300">{{ $item->variant_name_snapshot }}</span> • 
                                    SKU: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $item->sku_snapshot }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ $item->quantity }} Units</div>
                                <div class="text-[10px] text-slate-500">₹{{ number_format($item->line_total / 100, 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Exceptions History -->
            @if($shipment->exceptions->count() > 0)
                <div class="bg-white dark:bg-slate-950 rounded-2xl border border-rose-200 dark:border-rose-900/50 overflow-hidden shadow-xs transition-colors">
                    <div class="p-5 border-b border-rose-200 dark:border-rose-900/40 bg-rose-50 dark:bg-rose-950/20 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-300">Recorded Delivery Exceptions</h3>
                        <span class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $shipment->exceptions->count() }} recorded</span>
                    </div>
                    <div class="divide-y divide-rose-100 dark:divide-rose-900/30 text-xs">
                        @foreach($shipment->exceptions as $exception)
                            <div class="p-4 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-mono font-bold text-[10px] border border-rose-200 dark:border-rose-800">
                                        {{ $exception->exception_code }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ $exception->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                <p class="text-slate-700 dark:text-slate-300 text-xs mt-1">{{ $exception->notes }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Operations Actions: Right Column (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Dispatch / Driver Assignment Card -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs transition-colors">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    Fleet Dispatch
                </h3>

                @if($shipment->status === 'out_for_delivery' || $shipment->status === 'delivered')
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                        <div class="text-slate-500 dark:text-slate-400">Assigned Driver: <strong class="text-slate-900 dark:text-white">{{ $shipment->carrier_or_driver_name }}</strong></div>
                        @if($shipment->driver)
                            <div class="text-slate-500 dark:text-slate-400">Vehicle: <strong class="text-slate-900 dark:text-white font-mono">{{ $shipment->driver->vehicle_number }}</strong> ({{ $shipment->driver->vehicle_type }})</div>
                        @endif
                        <div class="text-slate-500 dark:text-slate-400">Driver Phone: <span class="text-slate-900 dark:text-white font-mono">{{ $shipment->driver_phone ?? 'N/A' }}</span></div>
                        <div class="text-slate-500 dark:text-slate-400">Tracking Ref: <span class="text-indigo-600 dark:text-indigo-400 font-mono font-bold">{{ $shipment->tracking_number ?? 'N/A' }}</span></div>
                        @if($shipment->delivery_otp)
                            <div class="text-slate-500 dark:text-slate-400">Dispatch Delivery OTP: <span class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-mono font-black border border-amber-300 dark:border-amber-700">{{ $shipment->delivery_otp }}</span></div>
                        @endif
                        @if($shipment->dispatched_at)
                            <div class="text-[11px] text-slate-500">Dispatched: {{ $shipment->dispatched_at->format('M d, Y H:i') }}</div>
                        @endif
                    </div>
                @endif

                @if($shipment->status !== 'delivered')
                    <form method="POST" action="{{ route('admin.shipments.dispatch', $shipment->id) }}" class="space-y-3 pt-2">
                        @csrf
                        @if(isset($availableDrivers) && $availableDrivers->isNotEmpty())
                            <div>
                                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Fleet Driver (Company Vehicle)</label>
                                <select name="driver_id" id="driver_id" onchange="const opt = this.options[this.selectedIndex]; if(opt.value) { document.getElementById('carrier_input').value = opt.dataset.name; if(opt.dataset.phone) document.getElementById('phone_input').value = opt.dataset.phone; }"
                                        class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- Choose active fleet driver (or manual carrier) --</option>
                                    @foreach($availableDrivers as $driver)
                                        <option value="{{ $driver->id }}"
                                                data-name="{{ $driver->user?->name }} ({{ $driver->vehicle_number }})"
                                                data-phone="{{ $driver->user?->phone }}"
                                                {{ old('driver_id', $shipment->driver_id) == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->user?->name }} • {{ $driver->vehicle_number }} ({{ $driver->vehicle_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Driver / Logistics Partner Name</label>
                            <input type="text" name="carrier_or_driver_name" id="carrier_input" value="{{ old('carrier_or_driver_name', $shipment->carrier_or_driver_name) }}" placeholder="e.g. Ramesh Kumar (Truck #KA-01-AB-1234)"
                                   class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Driver Contact Phone</label>
                            <input type="text" name="driver_phone" id="phone_input" value="{{ old('driver_phone', $shipment->driver_phone) }}" placeholder="+91 9876543210"
                                   class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Tracking Number</label>
                            <input type="text" name="tracking_number" value="{{ old('tracking_number', $shipment->tracking_number) }}" placeholder="Auto-generated if left empty"
                                   class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition shadow-xs">
                            Dispatch Consignment &rarr;
                        </button>
                    </form>
                @endif
            </div>

            <!-- Proof-of-Delivery (POD) Card -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs transition-colors">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Proof of Delivery (POD)
                </h3>

                @if($shipment->status === 'delivered')
                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs space-y-3">
                        <div class="text-emerald-700 dark:text-emerald-400 font-bold">✓ Consignment Handover Complete</div>
                        <div class="text-slate-700 dark:text-slate-300">Received By: <strong>{{ $shipment->pod_recipient_name }}</strong></div>
                        @if($shipment->pod_otp)
                            <div class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">Verified OTP: <strong class="text-emerald-700 dark:text-emerald-300">{{ $shipment->pod_otp }}</strong></div>
                        @endif
                        @if($shipment->pod_latitude && $shipment->pod_longitude)
                            <div class="text-slate-500 dark:text-slate-400 text-[11px]">
                                GPS Geo: <a href="https://www.google.com/maps/search/?api=1&query={{ $shipment->pod_latitude }},{{ $shipment->pod_longitude }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline font-mono">{{ $shipment->pod_latitude }}, {{ $shipment->pod_longitude }}</a>
                            </div>
                        @endif
                        <div class="text-[10px] text-slate-500">Timestamp: {{ $shipment->delivered_at?->format('M d, Y H:i:s') }}</div>

                        @if($shipment->pod_signature_data)
                            <div class="pt-2 border-t border-emerald-200 dark:border-emerald-800/50">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Customer Digital Signature:</div>
                                <div class="p-2 rounded-lg bg-slate-900 inline-block border border-slate-700">
                                    <img src="{{ $shipment->pod_signature_data }}" alt="POD Signature" class="max-h-20 object-contain">
                                </div>
                            </div>
                        @endif

                        @if($shipment->pod_photo_path)
                            <div class="pt-2 border-t border-emerald-200 dark:border-emerald-800/50">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Site Unloading Photo:</div>
                                <img src="{{ asset('storage/' . $shipment->pod_photo_path) }}" alt="POD Photo" class="w-full max-h-40 rounded-lg object-cover border border-slate-300 dark:border-slate-700">
                            </div>
                        @endif
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.shipments.pod', $shipment->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Site Receiver Full Name</label>
                            <input type="text" name="pod_recipient_name" required placeholder="e.g. Suresh Gowda (Site Engineer)"
                                   class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Delivery OTP</label>
                                <input type="text" name="pod_otp" placeholder="6-digit OTP"
                                       class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">GPS Latitude</label>
                                <input type="text" name="pod_latitude" placeholder="12.9716"
                                       class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Handover Notes</label>
                            <input type="text" name="notes" placeholder="e.g. Unloaded at Block-B ground yard"
                                   class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs transition shadow-xs">
                            Confirm Proof of Delivery &rarr;
                        </button>
                    </form>
                @endif
            </div>

            <!-- Delivery Exception Logging Card -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs transition-colors">
                <h3 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Log Delivery Exception
                </h3>

                <form method="POST" action="{{ route('admin.shipments.exception', $shipment->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Exception Reason</label>
                        <select name="exception_code" required
                                class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none">
                            <option value="CUSTOMER_UNAVAILABLE">Customer / Site In-charge Unavailable</option>
                            <option value="WRONG_ADDRESS">Wrong Address / Site Gate Inaccessible</option>
                            <option value="PINCODE_NOT_SERVICEABLE">Pincode or Heavy Truck Restrictions</option>
                            <option value="STOCK_SHORTAGE">Stock Damage / Shortage at Unloading</option>
                            <option value="VEHICLE_ISSUE">Vehicle Breakdown / Transport Delay</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Incident Notes</label>
                        <textarea name="notes" rows="2" required placeholder="Describe what happened at the delivery site..."
                                  class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs transition shadow-xs">
                        Log Exception &amp; Mark Failed &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
