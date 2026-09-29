@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('header_title', 'System Operations Dashboard')
@section('header_subtitle', 'Live metrics, fulfillment queues, and extensible platform status')

@section('content')
<div class="space-y-8">
    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Revenue Card -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Settled Revenue</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">₹{{ number_format($totalRevenue / 100, 2) }}</div>
            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1 font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span> Authoritative Net Settled
            </div>
        </div>

        <!-- Orders Card -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Platform Orders</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">{{ number_format($totalOrders) }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1">
                <span>Multi-channel &amp; guest carts</span>
            </div>
        </div>

        <!-- Pending Fulfillment -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pending Dispatch</div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">{{ number_format($pendingOrders) }}</div>
            <div class="text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-2 flex items-center gap-1">
                <span>Confirmed &amp; Picking status</span>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Low Stock Reorders</div>
            <div class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }} mt-2">{{ number_format($lowStockCount) }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1">
                <span>Below safe warehouse buffer</span>
            </div>
        </div>
    </div>

    <!-- Active Portable Add-ons Strip -->
    <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Registered Portable Add-ons &amp; Domain Packs
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Modular extensions booted into the core platform runtime</p>
            </div>
            <a href="{{ route('admin.addons') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">View Registry &rarr;</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @forelse($addons as $addon)
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-700/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-black text-xs shrink-0">
                        {{ substr($addon->name(), 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $addon->name() }}</h3>
                            <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">v{{ $addon->version() }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $addon->description() }}</p>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-xs text-slate-500 text-center py-4">No portable add-ons loaded.</div>
            @endforelse
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs transition-colors">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recent Customer Orders</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Real-time order stream across all sales channels</p>
            </div>
            <a href="{{ route('admin.orders') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">View All Orders &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Order Number</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Items</th>
                        <th class="px-5 py-3">Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $order->user?->name ?? 'Guest Buyer' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $order->user?->email ?? 'N/A' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                {{ $order->items->count() }} line(s)
                            </td>
                            <td class="px-5 py-3.5 font-black text-slate-900 dark:text-white">
                                ₹{{ number_format($order->grand_total / 100, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                                        'confirmed' => 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/50',
                                        'picking' => 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/50',
                                        'shipped' => 'bg-purple-50 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/50',
                                        'delivered' => 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
                                        'cancelled' => 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
                                    ];
                                    $badge = $statusColors[$order->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide border {{ $badge }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400">
                                {{ $order->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1">
                                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold transition">
                                    Manage &rarr;
                                </a>
                                <a href="{{ route('storefront.order_confirmation', $order->order_number) }}" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-semibold transition">
                                    Invoice
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">No orders placed yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
