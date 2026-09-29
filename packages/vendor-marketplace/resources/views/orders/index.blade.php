@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Orders & Fulfillment')
@section('subtitle', 'Manage and dispatch customer orders assigned to your fulfillment team')

@section('content')
<div class="space-y-6">
    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        @php
            $statuses = [
                'all' => 'All Orders',
                'confirmed' => 'Confirmed',
                'picking' => 'Picking',
                'packed' => 'Packed',
                'dispatched' => 'Dispatched',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled',
            ];
        @endphp

        @foreach($statuses as $key => $label)
            @php
                $count = $key === 'all' ? ($totalOrdersCount ?? $orders->total()) : ($statusCounts[$key] ?? 0);
            @endphp
            <a href="{{ route('vendor.orders.index', $key !== 'all' ? ['status' => $key] : []) }}"
               class="px-3 py-1.5 rounded-lg font-bold whitespace-nowrap transition-colors flex items-center gap-1.5
               {{ $currentStatus === $key ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                <span>{{ $label }}</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $currentStatus === $key ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Vendor Order #</th>
                        <th class="p-4">Platform Order</th>
                        <th class="p-4">Customer Destination</th>
                        <th class="p-4">Gross Subtotal</th>
                        <th class="p-4">Commission</th>
                        <th class="p-4">Net Payout</th>
                        <th class="p-4">Fulfillment Status</th>
                        <th class="p-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 font-mono font-bold text-slate-900">{{ $order->vendor_order_number }}</td>
                            <td class="p-4 font-mono text-slate-600">{{ $order->order->order_number ?? 'N/A' }}</td>
                            <td class="p-4 text-slate-600">
                                {{ $order->order->shipping_address_snapshot['city'] ?? 'Local' }}
                                @if(!empty($order->order->shipping_address_snapshot['pincode']) || !empty($order->order->shipping_address_snapshot['postal_code']))
                                    ({{ $order->order->shipping_address_snapshot['pincode'] ?? $order->order->shipping_address_snapshot['postal_code'] }})
                                @endif
                            </td>
                            <td class="p-4 text-slate-900 font-mono">₹{{ number_format($order->subtotal / 100, 2) }}</td>
                            <td class="p-4 text-rose-600 font-mono">-₹{{ number_format($order->commission_amount / 100, 2) }}</td>
                            <td class="p-4 text-emerald-600 font-bold font-mono">₹{{ number_format($order->vendor_payout / 100, 2) }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold
                                    {{ $order->status === 'confirmed' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $order->status === 'picking' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $order->status === 'packed' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $order->status === 'dispatched' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $order->status === 'delivered' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : '' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('vendor.orders.show', $order->id) }}" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs shadow-sm transition-colors">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                No orders match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
