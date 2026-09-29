@extends('layouts.admin')

@section('title', 'Delivery & Logistics Operations')
@section('header_title', 'Delivery Logistics & Fleet Dispatch')
@section('header_subtitle', 'Monitor outbound consignments, driver dispatches, Proof of Delivery (POD), and site exceptions')

@section('content')
<div class="space-y-6">
    <!-- Top KPI Badges -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Consignments</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalShipments }}</div>
            <span class="text-[10px] text-slate-500">All generated shipments</span>
        </div>
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Out for Delivery</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $inTransitCount }}</div>
            <span class="text-[10px] text-slate-500">Assigned to fleet drivers</span>
        </div>
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Delivered &amp; POD</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $deliveredCount }}</div>
            <span class="text-[10px] text-slate-500">Verified site handovers</span>
        </div>
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Active Exceptions</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $exceptionCount }}</div>
            <span class="text-[10px] text-slate-500">Requires site coordinator action</span>
        </div>
    </div>

    <!-- Status Filter Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
        <div class="flex flex-wrap items-center gap-2">
            @php
                $currentStatus = request('status');
                $statuses = [
                    'all' => 'All Shipments',
                    'pending' => 'Pending Pick',
                    'picking' => 'Picking',
                    'packed' => 'Packed',
                    'out_for_delivery' => 'Out for Delivery',
                    'delivered' => 'Delivered (POD)',
                    'failed' => 'Exceptions',
                ];
            @endphp
            @foreach($statuses as $key => $label)
                <a href="{{ $key === 'all' ? route('admin.shipments.index') : route('admin.shipments.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($currentStatus === $key || ($key === 'all' && !$currentStatus)) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Showing: <strong class="text-slate-900 dark:text-white">{{ $shipments->total() }}</strong> consignments
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Shipment &amp; Order</th>
                        <th class="px-5 py-3.5">Customer &amp; Destination</th>
                        <th class="px-5 py-3.5">Carrier / Driver</th>
                        <th class="px-5 py-3.5">Tracking Number</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">POD Verification</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                    @forelse($shipments as $shipment)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                            <!-- Shipment Number & Order -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $shipment->shipment_number }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Order: <span class="text-indigo-600 dark:text-indigo-400 font-mono font-semibold">{{ $shipment->order->order_number }}</span></div>
                                <div class="text-[10px] text-slate-500 mt-1">{{ $shipment->created_at->format('M d, Y H:i') }}</div>
                            </td>

                            <!-- Customer & Destination -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $shipment->order->shipping_address_snapshot['recipient_name'] ?? 'Recipient' }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ $shipment->order->shipping_address_snapshot['city'] ?? '' }} — 
                                    <span class="font-mono text-slate-800 dark:text-slate-300 font-bold">{{ $shipment->order->shipping_address_snapshot['pincode'] ?? '' }}</span>
                                </div>
                                @if(!empty($shipment->order->shipping_address_snapshot['phone']))
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">📞 {{ $shipment->order->shipping_address_snapshot['phone'] }}</div>
                                @endif
                            </td>

                            <!-- Driver Details -->
                            <td class="px-5 py-4 align-top">
                                @if($shipment->carrier_or_driver_name)
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $shipment->carrier_or_driver_name }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{{ $shipment->driver_phone ?? 'No phone' }}</div>
                                @else
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 italic">Unassigned</span>
                                @endif
                            </td>

                            <!-- Tracking -->
                            <td class="px-5 py-4 align-top">
                                @if($shipment->tracking_number)
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-indigo-700 dark:text-indigo-300 font-mono text-[11px]">
                                        {{ $shipment->tracking_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 text-[11px]">—</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4 align-top">
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
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $badgeStyles[$shipment->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                    {{ str_replace('_', ' ', $shipment->status) }}
                                </span>
                                @if($shipment->exceptions->where('is_resolved', false)->count() > 0)
                                    <div class="text-[10px] text-rose-600 dark:text-rose-400 font-bold mt-1">⚠️ Exception Reported</div>
                                @endif
                            </td>

                            <!-- POD Details -->
                            <td class="px-5 py-4 align-top">
                                @if($shipment->status === 'delivered')
                                    <div class="text-emerald-600 dark:text-emerald-400 font-bold text-[11px]">✓ Confirmed</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400">Received by: {{ $shipment->pod_recipient_name ?? 'N/A' }}</div>
                                    @if($shipment->pod_otp)
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">OTP: {{ $shipment->pod_otp }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 text-[11px]">Pending delivery</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-5 py-4 align-top text-right">
                                <a href="{{ route('admin.shipments.show', $shipment->id) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-500">
                                No shipments found for this filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipments->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/30">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
