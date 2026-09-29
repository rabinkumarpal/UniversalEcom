@extends('layouts.storefront')

@section('title', 'Order Confirmed: ' . $order->order_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
    <!-- Success Banner -->
    <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-8 text-center space-y-3">
        <div class="w-16 h-16 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-lg">
            ✓
        </div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Order Confirmed!</h1>
        <p class="text-sm text-slate-600">
            Thank you for your purchase. Your order number is <strong class="font-mono text-indigo-600">{{ $order->order_number }}</strong>.
        </p>
        <div class="pt-2 flex justify-center gap-3">
            <span class="inline-flex items-center gap-1.5 bg-white px-3 py-1 rounded-full text-xs font-semibold text-emerald-800 border border-emerald-200 shadow-2xs">
                Status: {{ ucfirst($order->status) }}
            </span>
            <span class="inline-flex items-center gap-1.5 bg-white px-3 py-1 rounded-full text-xs font-semibold text-slate-700 border border-slate-200 shadow-2xs">
                Invoice: {{ $order->invoice?->invoice_number ?? 'Generating...' }}
            </span>
        </div>
    </div>

    <!-- Fulfillment Status Timeline -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Fulfillment & Delivery Progress</h3>
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-900 border border-emerald-200 font-bold">
                1. Confirmed
            </div>
            <div class="p-3 rounded-xl bg-slate-50 text-slate-500 border border-slate-200 font-medium">
                2. Yard Picking
            </div>
            <div class="p-3 rounded-xl bg-slate-50 text-slate-500 border border-slate-200 font-medium">
                3. Dispatched
            </div>
            <div class="p-3 rounded-xl bg-slate-50 text-slate-500 border border-slate-200 font-medium">
                4. Delivered
            </div>
        </div>
    </div>

    <!-- Order Items Snapshot Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">Order Lines (Immutable Historical Snapshot)</h3>
        <div class="divide-y divide-slate-100 text-xs">
            @foreach($order->items as $item)
                <div class="py-3 flex justify-between items-center">
                    <div>
                        <div class="font-bold text-slate-900">{{ $item->product_name_snapshot }}</div>
                        <div class="text-[11px] text-slate-500">{{ $item->variant_name_snapshot }} • SKU: {{ $item->sku_snapshot }}</div>
                        <div class="text-[11px] text-slate-400">Qty: {{ $item->quantity }} × ₹{{ number_format($item->unit_price / 100, 2) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-slate-900 text-sm">₹{{ number_format($item->line_total / 100, 2) }}</div>
                        @if($item->discount > 0)
                            <div class="text-emerald-600 text-[10px]">-₹{{ number_format($item->discount / 100, 2) }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-4 border-t border-slate-200 space-y-1.5 text-xs text-slate-600">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span class="font-bold text-slate-900">₹{{ number_format($order->subtotal / 100, 2) }}</span>
            </div>
            @if($order->discount_total > 0)
                <div class="flex justify-between text-emerald-600 font-semibold">
                    <span>Discount</span>
                    <span>-₹{{ number_format($order->discount_total / 100, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span>Tax</span>
                <span class="font-bold text-slate-900">₹{{ number_format($order->tax_total / 100, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Delivery Fee</span>
                <span class="font-bold text-slate-900">₹{{ number_format($order->delivery_fee / 100, 2) }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-slate-100 text-sm font-bold text-slate-900">
                <span>Grand Total</span>
                <span class="text-lg font-black">₹{{ number_format($order->grand_total / 100, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap justify-between items-center gap-3 pt-4">
        <a href="{{ route('storefront.catalog') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
            &larr; Return to Materials Catalog
        </a>
        <div class="flex items-center gap-2">
            @if($order->gstInvoice)
                <a href="{{ route('account.invoices.show', $order->gstInvoice->invoice_number) }}" target="_blank" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-xs inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Download Tax Invoice</span>
                </a>
            @endif
            <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 bg-slate-900 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition">
                View in Admin Operations &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
