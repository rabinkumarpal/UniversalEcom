@extends('layouts.storefront')

@section('title', 'Shopping Cart — Universal Ecommerce Platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-8">Shopping Cart</h1>

    @if($cart->items->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
            <span class="text-4xl block">🛒</span>
            <h3 class="text-lg font-bold text-slate-900">Your cart is currently empty</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Explore our industrial supplies and building materials catalog to add items.</p>
            <a href="{{ route('storefront.catalog') }}" class="inline-block px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition">
                Start Shopping &rarr;
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Items Table -->
            <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="p-4 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Order Items ({{ $cart->total_quantity }})</h2>
                        <p class="text-xs text-slate-500">Server-authoritative pricing and live volume tiers applied</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form action="{{ route('storefront.set_location') }}" method="POST" class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1">
                            @csrf
                            <span class="text-[11px] font-semibold text-slate-500">Pin:</span>
                            <input type="text" name="pincode" value="{{ $pincode }}" maxlength="10" placeholder="560001" class="w-20 bg-transparent text-xs font-bold text-slate-800 border-0 p-0 focus:ring-0">
                            <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition">Update</button>
                        </form>
                        @if($isServiceable)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Serviceable
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Regional Transit
                            </span>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($totals['lines'] as $line)
                        <div class="p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <h3 class="text-sm font-bold text-slate-900">{{ $line['product_name'] }}</h3>
                                <p class="text-xs text-slate-500">{{ $line['variant_name'] }} • <span class="font-mono">{{ $line['sku'] }}</span></p>
                                <div class="text-xs font-semibold text-indigo-600">
                                    ₹{{ number_format($line['unit_price'] / 100, 2) }} / unit
                                </div>
                            </div>

                            <!-- Quantity Controls -->
                            <div class="flex items-center gap-4">
                                <form action="{{ route('storefront.cart.update', $line['cart_item_id']) }}" method="POST" class="flex items-center border border-slate-200 rounded-lg overflow-hidden">
                                    @csrf
                                    <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" class="w-16 text-center text-xs font-bold border-0 focus:ring-0 py-1.5">
                                    <button type="submit" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-xs font-bold border-l border-slate-200">Update</button>
                                </form>

                                <div class="text-right min-w-24">
                                    <div class="text-sm font-black text-slate-900">₹{{ number_format($line['line_total'] / 100, 2) }}</div>
                                    @if($line['discount'] > 0)
                                        <div class="text-[10px] text-emerald-600 font-semibold">-₹{{ number_format($line['discount'] / 100, 2) }} off</div>
                                    @endif
                                </div>

                                <form action="{{ route('storefront.cart.remove', $line['cart_item_id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-slate-400 hover:text-rose-600 p-1" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Summary & Checkout Sidebar -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Coupon Box -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Promotions &amp; Coupons</h3>
                        @if(session('applied_coupon'))
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                COUPON ACTIVE
                            </span>
                        @endif
                    </div>

                    @if(session('applied_coupon'))
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">🏷️</span>
                                    <span class="font-mono text-xs font-black text-slate-900 tracking-wider">{{ session('applied_coupon') }}</span>
                                </div>
                                <form action="{{ route('storefront.cart.coupon.remove') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition hover:underline">
                                        Remove
                                    </button>
                                </form>
                            </div>
                            @if($totals['discount_total'] > 0)
                                <div class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Discount applied: ₹{{ number_format($totals['discount_total'] / 100, 2) }} saved
                                </div>
                            @elseif($totals['free_shipping'])
                                <div class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Free delivery promotion applied!
                                </div>
                            @else
                                <div class="text-[11px] text-slate-500">
                                    Coupon registered for qualifying items in cart.
                                </div>
                            @endif
                        </div>
                    @else
                        <form action="{{ route('storefront.cart.coupon') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="text" name="coupon_code" placeholder="e.g. DRILL500" class="flex-1 px-3 py-2 text-xs border border-slate-300 rounded-lg uppercase font-mono focus:ring-2 focus:ring-indigo-500">
                            <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-lg hover:bg-indigo-600 transition">Apply</button>
                        </form>
                    @endif

                    @if(!empty($totals['applied_promotions']))
                        <div class="space-y-1.5 pt-1">
                            @foreach($totals['applied_promotions'] as $p)
                                <div class="text-[11px] bg-emerald-50 text-emerald-800 p-2.5 rounded-lg border border-emerald-200 flex justify-between items-center">
                                    <span class="font-medium flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $p['name'] }}
                                    </span>
                                    <span class="font-bold">-₹{{ number_format(($p['discount_amount'] ?? 0) / 100, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Spending Goal Progress Widget -->
                @if(!empty($totals['spending_goal']))
                @php $goal = $totals['spending_goal']; @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Spending Goal</h3>
                    @if($goal['unlocked'])
                        <div class="text-xs text-emerald-700 font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $goal['reward_label'] }} unlocked!
                        </div>
                    @else
                        <div class="text-xs text-slate-600">
                            Add <span class="font-bold text-slate-900">₹{{ number_format($goal['remaining'] / 100, 2) }}</span>
                            more to unlock <span class="font-bold text-indigo-700">{{ $goal['reward_label'] }}</span>
                        </div>
                    @endif
                    @php $pct = min(100, $goal['goal_amount'] > 0 ? round($goal['current_amount'] / $goal['goal_amount'] * 100) : 0); @endphp
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400">
                        <span>₹{{ number_format($goal['current_amount'] / 100, 2) }}</span>
                        <span>₹{{ number_format($goal['goal_amount'] / 100, 2) }}</span>
                    </div>
                </div>
                @endif

                <!-- Order Summary Box -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">Order Summary</h3>

                    <div class="space-y-2.5 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Subtotal (Authoritative)</span>
                            <span class="font-bold text-slate-900">₹{{ number_format($totals['subtotal'] / 100, 2) }}</span>
                        </div>
                        @if($totals['discount_total'] > 0)
                            <div class="flex justify-between text-emerald-600 font-semibold">
                                <span>Promotional Discounts</span>
                                <span>-₹{{ number_format($totals['discount_total'] / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span>Estimated GST / Taxes</span>
                            <span class="font-bold text-slate-900">₹{{ number_format($totals['tax_total'] / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Delivery / Transport</span>
                            @if($totals['free_shipping'] || $totals['delivery_fee'] === 0)
                                <span class="font-bold text-emerald-600 uppercase">FREE DELIVERY</span>
                            @else
                                <span class="font-bold text-slate-900">₹{{ number_format($totals['delivery_fee'] / 100, 2) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200 flex justify-between items-baseline">
                        <span class="text-sm font-bold text-slate-900">Grand Total</span>
                        <span class="text-2xl font-black text-slate-900">₹{{ number_format($totals['grand_total'] / 100, 2) }}</span>
                    </div>

                    <a href="{{ route('storefront.checkout') }}" class="block w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-center font-bold text-sm rounded-xl shadow-md transition">
                        Proceed to Checkout &rarr;
                    </a>

                    <div class="text-center">
                        <span class="text-[10px] text-slate-400 block">🔒 Server-authoritative checkout • Zero-tampering guarantee</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
