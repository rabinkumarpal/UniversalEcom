@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Vendor Dashboard')
@section('subtitle', 'Real-time commerce overview for ' . $vendor->display_name)

@section('content')
<div class="space-y-6">
    <!-- Live Financial & Operations KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Active Variant Offers -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Variant Offers</span>
                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-slate-900">{{ $activeOffersCount }}</p>
                <div class="flex items-center justify-between mt-1 text-xs text-slate-400">
                    <span>Catalog items live</span>
                    <a href="{{ route('vendor.offers.index') }}" class="font-bold text-indigo-600 hover:text-indigo-700">View Offers &rarr;</a>
                </div>
            </div>
        </div>

        <!-- 2. Pending Vendor Orders -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending Orders</span>
                <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-amber-600">{{ $pendingFulfillment }}</p>
                <div class="flex items-center justify-between mt-1 text-xs text-slate-400">
                    <span>Awaiting dispatch</span>
                    <a href="{{ route('vendor.orders.index') }}" class="font-bold text-amber-600 hover:text-amber-700">Fulfill Orders &rarr;</a>
                </div>
            </div>
        </div>

        <!-- 3. Net Earnings (Sales) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Net Earnings</span>
                <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-emerald-600">₹{{ number_format($netPayout / 100, 2) }}</p>
                <p class="text-[11px] text-slate-400 mt-1 truncate" title="Gross Sales: ₹{{ number_format($totalSales / 100, 2) }} • Fees: ₹{{ number_format($commissionPaid / 100, 2) }}">
                    Gross Sales: ₹{{ number_format($totalSales / 100, 2) }} • Comm: -₹{{ number_format($commissionPaid / 100, 2) }}
                </p>
            </div>
        </div>

        <!-- 4. Available Payout Balance -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Available Payout</span>
                <div class="p-2.5 bg-teal-50 text-teal-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-teal-700">₹{{ number_format($availablePayoutBalance / 100, 2) }}</p>
                <div class="flex items-center justify-between mt-1 text-xs">
                    <span class="text-slate-400">Settlement ready</span>
                    <a href="{{ route('vendor.payouts.index') }}" class="font-bold text-teal-600 hover:text-teal-700">Request Payout &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                    Commission: {{ $vendor->commission_rate_percentage }}%
                </span>
                <span class="text-xs text-slate-400">Fixed Merchant Tier</span>
            </div>
            <h3 class="text-base font-black text-white mt-1">Expand Your Catalog Presence</h3>
            <p class="text-xs text-slate-300 mt-0.5">List offers for platform catalog items, preview real-time net margins, and fulfill incoming orders.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('vendor.offers.create') }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow transition-colors flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Add Catalog Offer</span>
            </a>
            <a href="{{ route('vendor.orders.index') }}" class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white font-semibold text-xs rounded-xl transition-colors cursor-pointer">
                View Orders
            </a>
        </div>
    </div>

    <!-- Recent Sub-Orders -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-sm">Recent Split Sub-Orders</h3>
                <p class="text-xs text-slate-500">Orders split automatically for your fulfillment</p>
            </div>
            <a href="{{ route('vendor.orders.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700">
                View All &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Vendor Order #</th>
                        <th class="p-4">Customer Order</th>
                        <th class="p-4">Destination</th>
                        <th class="p-4">Subtotal</th>
                        <th class="p-4">Commission</th>
                        <th class="p-4">Your Payout</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 font-mono font-bold text-slate-900">{{ $order->vendor_order_number }}</td>
                            <td class="p-4 font-mono text-slate-600">{{ $order->order->order_number ?? 'N/A' }}</td>
                            <td class="p-4 text-slate-600">
                                {{ $order->order->shipping_address_snapshot['city'] ?? 'Local' }}
                                @if(!empty($order->order->shipping_address_snapshot['pincode']) || !empty($order->order->shipping_address_snapshot['postal_code']))
                                    ({{ $order->order->shipping_address_snapshot['pincode'] ?? $order->order->shipping_address_snapshot['postal_code'] }})
                                @endif
                            </td>
                            <td class="p-4 text-slate-900">₹{{ number_format($order->subtotal / 100, 2) }}</td>
                            <td class="p-4 text-rose-600 font-mono">-₹{{ number_format($order->commission_amount / 100, 2) }}</td>
                            <td class="p-4 text-emerald-600 font-bold font-mono">₹{{ number_format($order->vendor_payout / 100, 2) }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                    {{ $order->status === 'confirmed' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $order->status === 'dispatched' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $order->status === 'delivered' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : '' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('vendor.orders.show', $order->id) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                                    Fulfill &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                No sub-orders received yet. Once customers purchase your catalog items, split orders will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
