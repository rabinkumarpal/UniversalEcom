@extends('driver-logistics::layouts.driver')

@section('title', 'Driver Manifest Dashboard')

@section('content')
<div class="space-y-4">
    <!-- Shift & Vehicle Profile Header -->
    <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-lg space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Driver Shift Profile</span>
                <h2 class="text-base font-black text-white">{{ $driver->user?->name ?? 'Fleet Operator' }}</h2>
                <div class="text-xs text-slate-400 font-mono">Lic: {{ $driver->license_number }}</div>
            </div>
            <div class="text-right">
                <div class="px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-400 font-mono font-bold text-xs">
                    {{ $driver->vehicle_number }}
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ $driver->vehicle_type }}</div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between">
            <span class="text-xs text-slate-400">Shift Duty Status</span>
            <form method="POST" action="{{ route('driver.status.toggle') }}">
                @csrf
                <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1.5 border {{ $driver->status === 'active' ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-300 hover:bg-emerald-500/30' : 'bg-rose-500/20 border-rose-500/50 text-rose-300 hover:bg-rose-500/30' }}">
                    <span class="w-2 h-2 rounded-full {{ $driver->status === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' }}"></span>
                    <span>{{ $driver->status === 'active' ? 'On Duty (Active)' : 'Off Duty' }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 gap-3">
        <div class="p-3.5 rounded-2xl bg-slate-800/70 border border-slate-700/70">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active Drops</div>
            <div class="text-2xl font-black text-amber-400 font-mono mt-0.5">{{ $activeShipments->count() }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">En route / pending</div>
        </div>

        <div class="p-3.5 rounded-2xl bg-slate-800/70 border border-slate-700/70">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Delivered Today</div>
            <div class="text-2xl font-black text-emerald-400 font-mono mt-0.5">{{ $deliveredToday->count() }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Verified with POD</div>
        </div>
    </div>

    <!-- Route Manifest Drops -->
    <div id="active-shipments" class="space-y-3 pt-1">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span>Active Route Drops ({{ $activeShipments->count() }})</span>
            </h3>
            <span class="text-[10px] text-slate-400 font-mono">Today's Schedule</span>
        </div>

        @forelse($activeShipments as $index => $shipment)
            @php
                $address = $shipment->order?->shipping_address_snapshot ?? [];
                $fullAddress = trim(($address['address_line_1'] ?? '') . ', ' . ($address['city'] ?? '') . ', ' . ($address['pincode'] ?? ''));
                $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($fullAddress);
                $phone = $address['phone'] ?? null;
            @endphp

            <div class="rounded-2xl bg-slate-800/80 border border-slate-700/80 overflow-hidden shadow-md">
                <!-- Drop Number & Status Banner -->
                <div class="px-4 py-2.5 bg-slate-800 border-b border-slate-700/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center font-mono">
                            {{ $index + 1 }}
                        </span>
                        <span class="font-mono text-xs font-bold text-white">{{ $shipment->shipment_number }}</span>
                    </div>

                    @if($shipment->status === 'out_for_delivery')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse">
                            Out for Delivery
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-700 text-slate-300 border border-slate-600">
                            Assigned
                        </span>
                    @endif
                </div>

                <!-- Card Body -->
                <div class="p-4 space-y-3">
                    <!-- Recipient & Site Contact -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-xs font-bold text-white">{{ $address['recipient_name'] ?? 'Site Receiver' }}</div>
                            <div class="text-xs text-slate-300 leading-snug mt-0.5">{{ $fullAddress }}</div>
                        </div>

                        @if($phone)
                            <a href="tel:{{ $phone }}" class="p-2.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/30 transition shrink-0" title="Call Customer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </a>
                        @endif
                    </div>

                    <!-- Navigation Action -->
                    <div class="flex items-center gap-2 pt-1">
                        <a href="{{ $mapsUrl }}" target="_blank" class="flex-1 py-2 px-3 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 transition">
                            <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Open Navigation</span>
                        </a>

                        <a href="{{ route('driver.shipments.show', $shipment->id) }}" class="py-2 px-3 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold flex items-center justify-center gap-1 transition">
                            <span>Details</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    <!-- Primary Action -->
                    <div class="pt-1">
                        @if($shipment->status === 'out_for_delivery')
                            <a href="{{ route('driver.shipments.show', $shipment->id) }}" class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs flex items-center justify-center gap-2 shadow-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Unload &amp; Complete POD &rarr;</span>
                            </a>
                        @else
                            <form method="POST" action="{{ route('driver.shipments.start', $shipment->id) }}">
                                @csrf
                                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs flex items-center justify-center gap-2 shadow-md transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Start Trip (Out for Delivery) &rarr;</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 rounded-2xl bg-slate-800/40 border border-slate-700/50 text-center space-y-2">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-800 flex items-center justify-center text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-300">All Caught Up!</h4>
                <p class="text-xs text-slate-500">No pending shipments assigned to your vehicle right now. Check back with dispatch central.</p>
            </div>
        @endforelse
    </div>

    <!-- Completed Today Summary -->
    @if($deliveredToday->count() > 0)
        <div class="space-y-3 pt-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Completed Deliveries Today ({{ $deliveredToday->count() }})</span>
            </h3>

            <div class="space-y-2">
                @foreach($deliveredToday as $delivered)
                    <div class="p-3 rounded-xl bg-slate-800/50 border border-slate-700/60 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-mono font-bold text-white">{{ $delivered->shipment_number }}</div>
                            <div class="text-[11px] text-slate-400">Receiver: {{ $delivered->pod_recipient_name ?? 'Recipient' }}</div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                Delivered
                            </span>
                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                {{ $delivered->delivered_at?->format('H:i') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
