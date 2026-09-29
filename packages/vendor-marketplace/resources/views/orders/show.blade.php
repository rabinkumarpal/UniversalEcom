@extends('vendor-marketplace::layouts.vendor')

@section('title', 'Vendor Order #' . $vendorOrder->vendor_order_number)
@section('subtitle', 'Platform Order Reference: ' . ($vendorOrder->order->order_number ?? 'N/A'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back button -->
    <a href="{{ route('vendor.orders.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
        &larr; Back to all orders
    </a>

    <!-- Order Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-xl font-black text-slate-900 font-mono">{{ $vendorOrder->vendor_order_number }}</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold
                    {{ $vendorOrder->status === 'confirmed' ? 'bg-amber-100 text-amber-800' : '' }}
                    {{ $vendorOrder->status === 'picking' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $vendorOrder->status === 'packed' ? 'bg-purple-100 text-purple-800' : '' }}
                    {{ $vendorOrder->status === 'dispatched' ? 'bg-indigo-100 text-indigo-800' : '' }}
                    {{ $vendorOrder->status === 'delivered' ? 'bg-emerald-100 text-emerald-800' : '' }}
                    {{ $vendorOrder->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : '' }}">
                    {{ ucfirst($vendorOrder->status) }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Placed on {{ $vendorOrder->created_at->format('M d, Y h:i A') }} • Platform Order: <strong class="text-slate-700 font-mono">{{ $vendorOrder->order->order_number }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('vendor.orders.packing-slip', $vendorOrder->id) }}" target="_blank"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Packing Slip</span>
            </a>

            <div class="text-right bg-slate-50 p-3 rounded-xl border border-slate-100">
                <p class="text-[11px] text-slate-400 font-semibold uppercase">Net Vendor Payout</p>
                <p class="text-xl font-black text-emerald-600 font-mono">₹{{ number_format($vendorOrder->vendor_payout / 100, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Fulfillment Status Transition Box -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 shadow-md">
        <h4 class="text-sm font-bold flex items-center gap-2 mb-3 text-amber-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            Fulfillment Workflow Action
        </h4>
        <form method="POST" action="{{ route('vendor.orders.status', $vendorOrder->id) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Advance Status</label>
                <select name="status" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white text-xs focus:ring-2 focus:ring-amber-500">
                    <option value="confirmed" {{ $vendorOrder->status === 'confirmed' ? 'selected' : '' }}>Confirmed (Awaiting Picking)</option>
                    <option value="picking" {{ $vendorOrder->status === 'picking' ? 'selected' : '' }}>Picking from Depot</option>
                    <option value="packed" {{ $vendorOrder->status === 'packed' ? 'selected' : '' }}>Packed & Ready for Dispatch</option>
                    <option value="dispatched" {{ $vendorOrder->status === 'dispatched' ? 'selected' : '' }}>Dispatched (In Transit)</option>
                    <option value="delivered" {{ $vendorOrder->status === 'delivered' ? 'selected' : '' }}>Delivered to Customer</option>
                    <option value="cancelled" {{ $vendorOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Tracking Notes / Waybill</label>
                <input type="text" name="tracking_notes" placeholder="e.g. Vehicle #KA-01-AB-1234"
                       class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white text-xs placeholder-slate-500 focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-lg shadow transition-colors">
                    Update Fulfillment Status
                </button>
            </div>
        </form>
    </div>

    <!-- Items & Destination Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Order Items -->
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h4 class="font-bold text-slate-900 text-sm mb-4">Assigned Line Items</h4>
            <div class="divide-y divide-slate-100">
                @foreach($vendorOrder->items as $item)
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $item->orderItem->product_name_snapshot ?? ($item->orderItem->product_title ?? 'Product Variant') }}</p>
                            <p class="text-xs text-slate-500">
                                Variant: {{ $item->orderItem->variant_name_snapshot ?? ($item->orderItem->variant_title ?? 'Standard') }} • SKU: {{ $item->orderItem->sku_snapshot ?? ($item->orderItem->sku ?? '-') }}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Qty: <strong>{{ $item->quantity }}</strong> × ₹{{ number_format($item->unit_price / 100, 2) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-mono font-bold text-slate-900">₹{{ number_format(($item->quantity * $item->unit_price) / 100, 2) }}</p>
                            <p class="text-[11px] text-rose-500 font-mono">-₹{{ number_format($item->commission_amount / 100, 2) }} comm</p>
                            <p class="text-xs text-emerald-600 font-mono font-bold">₹{{ number_format($item->payout_amount / 100, 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Line Subtotal:</span>
                    <span class="font-mono">₹{{ number_format($vendorOrder->subtotal / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-rose-600 font-semibold">
                    <span>Platform Commission ({{ $vendorOrder->vendor->commission_rate_percentage ?? 10 }}%):</span>
                    <span class="font-mono">-₹{{ number_format($vendorOrder->commission_amount / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-black text-emerald-600 pt-2 border-t border-slate-100">
                    <span>Net Credited to Payout Ledger:</span>
                    <span class="font-mono">₹{{ number_format($vendorOrder->vendor_payout / 100, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Destination Address -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h4 class="font-bold text-slate-900 text-sm">Shipping Destination</h4>
            @if(!empty($vendorOrder->order->shipping_address_snapshot))
                @php $addr = $vendorOrder->order->shipping_address_snapshot; @endphp
                <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1 text-slate-700">
                    <p class="font-bold text-slate-900">{{ $addr['recipient_name'] ?? ($addr['full_name'] ?? 'Customer') }}</p>
                    @if(!empty($addr['phone']))
                        <p>{{ $addr['phone'] }}</p>
                    @endif
                    <p class="text-slate-600">{{ $addr['address_line_1'] ?? ($addr['address_line1'] ?? '') }}</p>
                    @if(!empty($addr['address_line_2']) || !empty($addr['address_line2']))
                        <p class="text-slate-600">{{ $addr['address_line_2'] ?? $addr['address_line2'] }}</p>
                    @endif
                    <p class="font-semibold text-slate-800">{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} - {{ $addr['pincode'] ?? ($addr['postal_code'] ?? '') }}</p>
                </div>
            @else
                <p class="text-xs text-slate-400">Direct warehouse delivery or standard site address.</p>
            @endif

            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-[11px] text-blue-900">
                Ensure packaging complies with platform standards. When order is dispatched, remember to update the status above to keep the customer notified.
            </div>
        </div>
    </div>
</div>
@endsection
