@extends('layouts.admin')

@section('title', 'Order Management')
@section('header_title', 'Order Management & State Machine')
@section('header_subtitle', 'Enforce centralized, immutable order state transitions and monitor fulfillment')

@section('content')
<div class="space-y-6">
    <!-- Search & Filter Controls -->
    <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3 transition-colors">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.orders') }}" class="flex items-center gap-2 flex-1 max-w-lg">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Order #, customer name, phone, city..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Search
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.orders') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-900 dark:hover:text-white transition">Reset</a>
                @endif
            </form>

            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium shrink-0">
                Matching Orders: <strong class="text-slate-900 dark:text-white">{{ $orders->total() }}</strong>
            </div>
        </div>

        <!-- Status Filter Pills -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-900">
            @php
                $currentStatus = request('status', 'all');
                $statusTabs = [
                    'all' => ['label' => 'All Orders', 'count' => $totalOrdersCount ?? $orders->total()],
                    'pending_payment' => ['label' => 'Pending', 'count' => $statusCounts['pending_payment'] ?? 0],
                    'confirmed' => ['label' => 'Confirmed', 'count' => $statusCounts['confirmed'] ?? 0],
                    'picking' => ['label' => 'Picking', 'count' => $statusCounts['picking'] ?? 0],
                    'packed' => ['label' => 'Packed', 'count' => $statusCounts['packed'] ?? 0],
                    'dispatched' => ['label' => 'Dispatched', 'count' => $statusCounts['dispatched'] ?? 0],
                    'delivered' => ['label' => 'Delivered', 'count' => $statusCounts['delivered'] ?? 0],
                    'cancelled' => ['label' => 'Cancelled', 'count' => $statusCounts['cancelled'] ?? 0],
                ];
            @endphp
            @foreach($statusTabs as $key => $tab)
                <a href="{{ $key === 'all' ? route('admin.orders', array_filter(['search' => request('search')])) : route('admin.orders', array_filter(['status' => $key, 'search' => request('search')])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($currentStatus === $key) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($currentStatus === $key) ? 'bg-indigo-700/80 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Order</th>
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Items Snapshot</th>
                        <th class="px-5 py-3.5">Payment</th>
                        <th class="px-5 py-3.5">Total</th>
                        <th class="px-5 py-3.5">Current Status</th>
                        <th class="px-5 py-3.5">Transition State</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                            <!-- Order Number & Date -->
                            <td class="px-5 py-4 align-top">
                                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="font-mono font-bold text-indigo-600 dark:text-indigo-400 hover:underline text-sm block">
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ $order->created_at->format('M d, Y H:i') }}</div>
                                <div class="flex items-center gap-2 mt-1">
                                    <a href="{{ route('admin.orders.show', $order->order_number) }}" class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        View Details &rarr;
                                    </a>
                                    <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                    <a href="{{ route('admin.orders.packing_slip', $order->order_number) }}" target="_blank" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Pick Slip &rarr;
                                    </a>
                                </div>
                            </td>

                            <!-- Customer Info -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $order->user?->name ?? 'Guest Customer' }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $order->user?->email ?? 'No email' }}</div>
                                @if(!empty($order->shipping_address_snapshot['phone']))
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">📞 {{ $order->shipping_address_snapshot['phone'] }}</div>
                                @endif
                            </td>

                            <!-- Items Snapshot -->
                            <td class="px-5 py-4 align-top max-w-xs">
                                <div class="space-y-1">
                                    @foreach($order->items as $item)
                                        <div class="text-[11px] truncate">
                                            <span class="font-bold text-slate-900 dark:text-slate-200">{{ $item->quantity }}×</span>
                                            <span class="text-slate-700 dark:text-slate-300">{{ $item->product_title_snapshot }}</span>
                                            <span class="text-slate-500">({{ $item->variant_title_snapshot }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Payment Details -->
                            <td class="px-5 py-4 align-top">
                                @php $payment = $order->payments->first(); @endphp
                                <div class="font-semibold text-slate-900 dark:text-white uppercase">{{ $payment?->gateway ?? 'COD' }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Status: <strong class="text-emerald-600 dark:text-emerald-400">{{ $payment?->status ?? 'pending' }}</strong></div>
                            </td>

                            <!-- Grand Total -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-black text-slate-900 dark:text-white text-sm">₹{{ number_format($order->grand_total / 100, 2) }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">Tax: ₹{{ number_format($order->tax_total / 100, 2) }}</div>
                            </td>

                            <!-- Current Status Badge -->
                            <td class="px-5 py-4 align-top">
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
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide border {{ $badge }}">
                                    {{ $order->status }}
                                </span>
                            </td>

                            <!-- State Machine Transition Form -->
                            <td class="px-5 py-4 align-top min-w-44">
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
                                    <form action="{{ route('admin.orders.transition', $order->order_number) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <select name="status" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] text-slate-900 dark:text-slate-200 px-2.5 py-1.5 focus:ring-1 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                                            @foreach($nextStates as $st => $label)
                                                <option value="{{ $st }}">{{ $label }} ({{ $st }})</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="note" placeholder="Optional audit note..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-lg text-[10px] text-slate-900 dark:text-slate-300 px-2 py-1 placeholder-slate-400 dark:placeholder-slate-600 focus:bg-white dark:focus:bg-slate-900">
                                        <button type="submit" class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-[11px] font-bold transition">
                                            Apply Transition
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 italic">Terminal state reached</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">No orders found matching the filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
