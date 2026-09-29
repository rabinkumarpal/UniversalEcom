@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)
@section('header_title', 'Order Details')
@section('header_subtitle', 'Comprehensive order snapshot, fulfillment pipeline, and audit ledger')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders') }}" class="p-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-black font-mono text-slate-900 dark:text-white">{{ $order->order_number }}</h1>
                    @php
                        $statusColors = [
                            'pending_payment' => 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                            'paid' => 'bg-cyan-50 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800/50',
                            'confirmed' => 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/50',
                            'picking' => 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/50',
                            'packed' => 'bg-violet-50 dark:bg-violet-950 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800/50',
                            'dispatched' => 'bg-purple-50 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/50',
                            'out_for_delivery' => 'bg-teal-50 dark:bg-teal-950 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800/50',
                            'delivered' => 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
                            'cancelled' => 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
                            'returned' => 'bg-orange-50 dark:bg-orange-950 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800/50',
                            'refunded' => 'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700',
                        ];
                        $badge = $statusColors[$order->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide border {{ $badge }}">
                        {{ $order->status }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        Payment: {{ $order->payment_status }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Placed on {{ $order->created_at->format('M d, Y \a\t h:i A') }} ({{ $order->created_at->diffForHumans() }})</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.packing_slip', $order->order_number) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs font-bold text-indigo-700 dark:text-indigo-300 transition shadow-xs">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Warehouse Pick &amp; Pack Slip</span>
            </a>
            <a href="{{ route('storefront.order_confirmation', $order->order_number) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-950 hover:bg-slate-50 dark:hover:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition shadow-xs">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Storefront Invoice</span>
            </a>
        </div>
    </div>

    <!-- Visual Order Progression Stepper & Quick Action Command Bar -->
    @php
        $milestones = [
            1 => [
                'key' => 'placed',
                'label' => 'Order Placed',
                'statuses' => ['pending_payment', 'paid', 'confirmed', 'picking', 'packed', 'dispatched', 'out_for_delivery', 'delivered'],
                'history_keys' => ['pending_payment', 'paid', 'confirmed'],
            ],
            2 => [
                'key' => 'confirmed',
                'label' => 'Confirmed',
                'statuses' => ['confirmed', 'picking', 'packed', 'dispatched', 'out_for_delivery', 'delivered'],
                'history_keys' => ['confirmed'],
            ],
            3 => [
                'key' => 'picking_packed',
                'label' => 'Pick & Pack',
                'statuses' => ['picking', 'packed', 'dispatched', 'out_for_delivery', 'delivered'],
                'history_keys' => ['picking', 'packed'],
            ],
            4 => [
                'key' => 'in_transit',
                'label' => 'Dispatched',
                'statuses' => ['dispatched', 'out_for_delivery', 'delivered'],
                'history_keys' => ['dispatched', 'out_for_delivery'],
            ],
            5 => [
                'key' => 'delivered',
                'label' => 'Delivered',
                'statuses' => ['delivered'],
                'history_keys' => ['delivered'],
            ],
        ];

        $currentMilestone = match($order->status) {
            'pending_payment', 'paid' => 1,
            'confirmed' => 2,
            'picking', 'packed' => 3,
            'dispatched', 'out_for_delivery' => 4,
            'delivered' => 5,
            default => 0,
        };

        $quickAction = match($order->status) {
            'pending_payment', 'paid' => ['target' => 'confirmed', 'label' => 'Confirm Order'],
            'confirmed' => ['target' => 'picking', 'label' => 'Start Warehouse Picking'],
            'picking' => ['target' => 'packed', 'label' => 'Mark Order Packed'],
            'packed' => ['target' => 'dispatched', 'label' => 'Dispatch to Fleet Carrier'],
            'dispatched' => ['target' => 'out_for_delivery', 'label' => 'Set Out for Delivery'],
            'out_for_delivery' => ['target' => 'delivered', 'label' => 'Confirm Gate Delivery (POD)'],
            default => null,
        };

        $historyByStatus = $order->statusHistory->keyBy('new_status');
    @endphp

    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Fulfillment Pipeline &amp; Lifecycle Stepper</span>
                        @if($currentMilestone > 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                                Milestone {{ $currentMilestone }} of 5
                            </span>
                        @endif
                    </h2>
                    <p class="text-[11px] text-slate-500">Live order state machine tracking with operator audit custody</p>
                </div>
            </div>

            @if($quickAction)
                <form action="{{ route('admin.orders.transition', $order->order_number) }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="status" value="{{ $quickAction['target'] }}">
                    <input type="hidden" name="note" value="Quick progression via Fulfillment Command Center">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 shrink-0">
                        <span>{{ $quickAction['label'] }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            @endif
        </div>

        @if(in_array($order->status, ['cancelled', 'returned', 'refunded']))
            @php $termHistory = $order->statusHistory->first(); @endphp
            <div class="p-3 rounded-xl text-xs flex items-center justify-between {{ $order->status === 'cancelled' ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-900' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-900' }}">
                <div class="flex items-center gap-2 font-semibold">
                    <span class="text-base">{{ $order->status === 'cancelled' ? '🛑' : '↩️' }}</span>
                    <span>Order {{ ucfirst($order->status) }} on {{ $termHistory?->created_at->format('M d, Y \a\t H:i') ?? 'N/A' }} by <strong>{{ $termHistory?->user?->name ?? 'System' }}</strong></span>
                    @if($termHistory?->note)
                        <span class="italic text-[11px] opacity-90">&bull; "{{ $termHistory->note }}"</span>
                    @endif
                </div>
                <span class="text-[10px] uppercase font-black px-2 py-0.5 rounded bg-white/50 dark:bg-black/30">
                    Terminal State
                </span>
            </div>
        @endif

        <!-- Connected Progress Stepper Track -->
        <div class="py-2 overflow-x-auto">
            <div class="min-w-[540px] flex items-start justify-between relative px-2">
                <!-- Background Connection Track Line -->
                <div class="absolute top-4 left-10 right-10 h-0.5 bg-slate-200 dark:bg-slate-800 -z-0"></div>

                @foreach($milestones as $stepNum => $m)
                    @php
                        $isCompleted = ($currentMilestone > $stepNum) || ($currentMilestone === 5 && $stepNum === 5);
                        $isCurrent = ($currentMilestone === $stepNum && $currentMilestone !== 5);
                        $isPending = ($currentMilestone < $stepNum);

                        $hist = null;
                        foreach ($m['history_keys'] as $hKey) {
                            if (isset($historyByStatus[$hKey])) {
                                $hist = $historyByStatus[$hKey];
                                break;
                            }
                        }
                    @endphp

                    <div class="flex flex-col items-center text-center relative z-10 flex-1 px-1">
                        <!-- Step Circle Icon -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition {{ $isCompleted ? 'bg-emerald-600 text-white shadow-xs' : ($isCurrent ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 dark:ring-indigo-950 shadow-xs' : 'bg-white dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 text-slate-400') }}">
                            @if($isCompleted)
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @elseif($isCurrent)
                                <span class="animate-pulse">●</span>
                            @else
                                <span class="text-[11px]">{{ $stepNum }}</span>
                            @endif
                        </div>

                        <!-- Step Label -->
                        <div class="mt-2">
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider block">Step 0{{ $stepNum }}</span>
                            <span class="font-bold text-xs {{ $isCurrent ? 'text-indigo-600 dark:text-indigo-400 font-extrabold' : ($isCompleted ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400') }}">
                                {{ $m['label'] }}
                            </span>
                        </div>

                        <!-- Step Audit / Timestamp Subtext -->
                        <div class="text-[10px] mt-0.5 leading-tight">
                            @if($hist)
                                <span class="font-mono text-slate-600 dark:text-slate-400 font-medium">{{ $hist->created_at->format('M d, H:i') }}</span>
                                @if($hist->user)
                                    <span class="block text-slate-400 truncate max-w-[110px] mx-auto">{{ $hist->user->name }}</span>
                                @endif
                            @elseif($isCurrent)
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider text-[9px]">Active Now</span>
                            @else
                                <span class="text-slate-400 italic">Pending</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Content Area (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Line Items Table -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Ordered Items ({{ $order->items->count() }})</h3>
                    <span class="text-xs text-slate-500">Currency: <strong class="text-slate-900 dark:text-white font-mono">{{ $order->currency }}</strong></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3">Item Details</th>
                                <th class="px-5 py-3 text-right">Unit Price</th>
                                <th class="px-5 py-3 text-center">Qty</th>
                                <th class="px-5 py-3 text-right">Tax</th>
                                <th class="px-5 py-3 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-900">
                            @foreach($order->items as $item)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition">
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                                            {{ $item->product_name_snapshot }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            {{ $item->variant_name_snapshot }}
                                        </div>
                                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                            SKU: {{ $item->sku_snapshot }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono">
                                        ₹{{ number_format($item->unit_price / 100, 2) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-500">
                                        ₹{{ number_format($item->tax / 100, 2) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                        ₹{{ number_format($item->line_total / 100, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Financial Totals -->
                <div class="p-5 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <div class="w-72 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>Subtotal:</span>
                            <span class="font-mono">₹{{ number_format($order->subtotal / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>GST / Tax Total:</span>
                            <span class="font-mono">₹{{ number_format($order->tax_total / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                            <span>Delivery / Logistics Fee:</span>
                            <span class="font-mono">₹{{ number_format($order->delivery_fee / 100, 2) }}</span>
                        </div>
                        @if($order->discount_total > 0)
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                <span>Discounts Applied:</span>
                                <span class="font-mono">-₹{{ number_format($order->discount_total / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between font-black text-sm text-slate-900 dark:text-white">
                            <span>Grand Total:</span>
                            <span class="font-mono text-base text-indigo-600 dark:text-indigo-400">₹{{ number_format($order->grand_total / 100, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipments & Delivery Operations -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Logistics & Shipments</h3>
                    <a href="{{ route('admin.shipments.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">All Shipments &rarr;</a>
                </div>

                @if($order->shipments->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($order->shipments as $shipment)
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-slate-900 dark:text-white text-xs">{{ $shipment->shipment_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                            {{ $shipment->status }}
                                        </span>
                                    </div>
                                    <a href="{{ route('admin.shipments.show', $shipment->id) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Manage Shipment &rarr;
                                    </a>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <div>
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Assigned Driver / Fleet</div>
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $shipment->driver?->user?->name ?? ($shipment->carrier_or_driver_name ?? 'Unassigned') }}</div>
                                        @if($shipment->driver_phone)
                                            <div class="text-[11px] text-slate-500 font-mono">📞 {{ $shipment->driver_phone }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Origin Warehouse</div>
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $shipment->warehouse?->name ?? 'Default Hub' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Delivery Security OTP</div>
                                        <div class="font-mono font-bold text-slate-900 dark:text-white">{{ $shipment->delivery_otp ?? 'N/A' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">POD Delivered At</div>
                                        <div class="text-slate-800 dark:text-slate-200">{{ $shipment->delivered_at ? $shipment->delivered_at->format('M d, H:i') : 'Pending delivery' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-xs text-slate-500 bg-slate-50 dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-800">
                        No active shipments dispatched yet for this order. Once order is confirmed and picked, create or dispatch a shipment.
                    </div>
                @endif
            </div>

            <!-- Immutable Status History & Audit Trail Timeline -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status Transition Timeline & Audit Ledger</h3>
                
                @if($order->statusHistory->isNotEmpty())
                    <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                        @foreach($order->statusHistory as $history)
                            <div class="relative">
                                <div class="absolute -left-6 top-1 w-4 h-4 rounded-full border-2 border-white dark:border-slate-950 bg-indigo-600"></div>
                                <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-1.5 font-bold">
                                            @if($history->previous_status)
                                                <span class="text-slate-500 uppercase text-[10px]">{{ $history->previous_status }}</span>
                                                <span class="text-slate-400">&rarr;</span>
                                            @endif
                                            <span class="text-indigo-600 dark:text-indigo-400 uppercase text-[11px] font-black">{{ $history->new_status }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $history->created_at->format('M d, Y H:i:s') }} ({{ $history->created_at->diffForHumans() }})
                                        </div>
                                    </div>
                                    @if($history->note)
                                        <p class="text-slate-600 dark:text-slate-300 text-[11px] italic">"{{ $history->note }}"</p>
                                    @endif
                                    <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-800/80">
                                        Actor: <strong class="text-slate-700 dark:text-slate-300">{{ $history->user?->name ?? 'System Automated Service' }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-slate-500 italic">No transition history recorded yet.</div>
                @endif
            </div>
        </div>

        <!-- Right Sidebar Area (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Order State Machine Transition Widget -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Execute State Transition</h3>

                @php
                    $nextStates = match($order->status) {
                        'pending_payment' => ['confirmed' => 'Confirm Order', 'paid' => 'Mark Paid', 'cancelled' => 'Cancel Order'],
                        'paid' => ['confirmed' => 'Confirm Order', 'cancelled' => 'Cancel Order', 'refunded' => 'Refund Order'],
                        'confirmed' => ['picking' => 'Start Picking', 'cancelled' => 'Cancel Order'],
                        'picking' => ['packed' => 'Mark Packed', 'cancelled' => 'Cancel Order'],
                        'packed' => ['dispatched' => 'Mark Dispatched', 'cancelled' => 'Cancel Order'],
                        'dispatched' => ['out_for_delivery' => 'Out for Delivery', 'cancelled' => 'Cancel Order'],
                        'out_for_delivery' => ['delivered' => 'Mark Delivered', 'cancelled' => 'Cancel Order', 'returned' => 'Mark Returned'],
                        'delivered' => ['returned' => 'Mark Returned', 'refunded' => 'Refund Order'],
                        default => []
                    };
                @endphp

                @if(!empty($nextStates))
                    <form action="{{ route('admin.orders.transition', $order->order_number) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Target Status</label>
                            <select name="status" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                                @foreach($nextStates as $st => $label)
                                    <option value="{{ $st }}">{{ $label }} ({{ $st }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Audit Note</label>
                            <textarea name="note" rows="2" placeholder="Reason or context for transition..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            Apply Transition
                        </button>
                    </form>
                @else
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-500 text-center">
                        This order has reached terminal status (<strong>{{ $order->status }}</strong>). No further state transitions are allowed.
                    </div>
                @endif
            </div>

            <!-- Customer Profile Card -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Customer Details</h3>
                <div class="space-y-1.5 text-xs">
                    <div class="font-bold text-slate-900 dark:text-white text-sm">
                        {{ $order->user?->name ?? ($order->shipping_address_snapshot['recipient_name'] ?? 'Guest Customer') }}
                    </div>
                    <div class="text-slate-500">
                        {{ $order->user?->email ?? ($order->billing_address_snapshot['email'] ?? 'No registered email') }}
                    </div>
                    @if(!empty($order->shipping_address_snapshot['phone']))
                        <div class="font-mono text-slate-600 dark:text-slate-300">
                            📞 {{ $order->shipping_address_snapshot['phone'] }}
                        </div>
                    @endif
                    <div class="pt-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $order->user ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                            {{ $order->user ? 'Registered Customer' : 'Guest Checkout' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipping Address Snapshot -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Shipping Destination</h3>
                @php $ship = $order->shipping_address_snapshot; @endphp
                @if($ship)
                    <div class="text-xs text-slate-700 dark:text-slate-300 space-y-1 leading-relaxed">
                        <div class="font-bold text-slate-900 dark:text-white">{{ $ship['recipient_name'] ?? 'N/A' }}</div>
                        @if(!empty($ship['phone']))
                            <div class="font-mono text-[11px] text-slate-500">Phone: {{ $ship['phone'] }}</div>
                        @endif
                        <div>{{ $ship['address_line_1'] ?? '' }}</div>
                        @if(!empty($ship['address_line_2']))
                            <div>{{ $ship['address_line_2'] }}</div>
                        @endif
                        <div>{{ $ship['city'] ?? '' }}, {{ $ship['state'] ?? '' }} — {{ $ship['pincode'] ?? '' }}</div>
                    </div>
                @else
                    <div class="text-xs text-slate-400 italic">No shipping address recorded.</div>
                @endif
            </div>

            <!-- Billing Address Snapshot -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Billing Address</h3>
                @php $bill = $order->billing_address_snapshot; @endphp
                @if($bill)
                    <div class="text-xs text-slate-700 dark:text-slate-300 space-y-1 leading-relaxed">
                        <div class="font-bold text-slate-900 dark:text-white">{{ $bill['recipient_name'] ?? 'N/A' }}</div>
                        <div>{{ $bill['address_line_1'] ?? '' }}</div>
                        <div>{{ $bill['city'] ?? '' }}, {{ $bill['state'] ?? '' }} — {{ $bill['pincode'] ?? '' }}</div>
                    </div>
                @else
                    <div class="text-xs text-slate-400 italic">Same as shipping address.</div>
                @endif
            </div>

            <!-- Invoice Reference -->
            <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3 transition-colors">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statutory Tax Invoice</h3>
                    @if($order->gstInvoice)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            GST Issued
                        </span>
                    @elseif($order->invoice)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            Order Receipt
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            Not Issued
                        </span>
                    @endif
                </div>

                @if($order->gstInvoice)
                    <div>
                        <div class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $order->gstInvoice->invoice_number }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Issued: {{ $order->gstInvoice->invoice_date?->format('M d, Y') ?? 'Generated' }}
                            @if(isset($order->gstInvoice->total_amount))
                                • <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($order->gstInvoice->total_amount / 100, 2) }}</span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('account.invoices.show', $order->gstInvoice->invoice_number) }}" target="_blank"
                       class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-bold text-xs inline-flex items-center justify-center gap-1.5 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>View / Print Tax Invoice</span>
                    </a>
                @elseif($order->invoice)
                    <div>
                        <div class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $order->invoice->invoice_number }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Issued: {{ $order->invoice->issued_at?->format('M d, Y') ?? 'Generated' }}
                            @if(isset($order->invoice->amount))
                                • <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($order->invoice->amount / 100, 2) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('account.invoices.show', $order->invoice->invoice_number) }}" target="_blank"
                           class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 hover:bg-indigo-100 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-bold text-xs inline-flex items-center justify-center gap-1.5 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            <span>View / Print Tax Invoice</span>
                        </a>

                        @if($order->status !== 'cancelled')
                            <form method="POST" action="{{ route('admin.orders.invoice', $order->order_number) }}">
                                @csrf
                                <button type="submit" class="w-full py-1.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>Issue GST Compliance Number</span>
                                </button>
                            </form>
                        @endif
                    </div>
                @elseif($order->status !== 'cancelled')
                    <p class="text-xs text-slate-500">A statutory GST compliant invoice can be generated on-demand for this order.</p>
                    <form method="POST" action="{{ route('admin.orders.invoice', $order->order_number) }}">
                        @csrf
                        <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Generate GST Tax Invoice</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
