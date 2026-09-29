@extends('layouts.storefront')

@section('title', 'Checkout — Universal Ecommerce Platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-8">Secure Checkout</h1>

    <form action="{{ route('storefront.order.place') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left: Address & Delivery & Payment Form -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Delivery Address Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>1. Delivery Destination Address</span>
                        </h2>
                        @if($isServiceable)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Serviceable Pincode ({{ $pincode }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Standard Regional Transit ({{ $pincode }})
                            </span>
                        @endif
                    </div>

                    @if(isset($savedAddresses) && $savedAddresses->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Saved Addresses &amp; Project Sites (1-Click Fill)</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                @foreach($savedAddresses as $sAddr)
                                    <div onclick="selectSavedAddress({{ json_encode($sAddr) }})"
                                         class="p-3.5 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/30 cursor-pointer transition text-xs space-y-1 group">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-slate-900 group-hover:text-indigo-600">{{ $sAddr->label ?: ($sAddr->recipient_name) }}</span>
                                            @if($sAddr->is_default)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">Default</span>
                                            @endif
                                        </div>
                                        <div class="text-slate-600 truncate">{{ $sAddr->address_line_1 }}</div>
                                        <div class="text-slate-500 text-[11px]">{{ $sAddr->city }}, {{ $sAddr->state }} ({{ $sAddr->pincode }})</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient Name / Site Engineer</label>
                            <input type="text" id="input_recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()?->name ?? '') }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number (For Gate Access & Delivery)</label>
                            <input type="text" id="input_phone" name="phone" value="{{ old('phone', auth()->user()?->phone ?? '') }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address / Project Site Gate</label>
                        <input type="text" id="input_address_line_1" name="address_line_1" value="{{ old('address_line_1', '') }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">City</label>
                            <input type="text" id="input_city" name="city" value="{{ old('city', '') }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">State</label>
                            <input type="text" id="input_state" name="state" value="{{ old('state', '') }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pincode</label>
                            <input type="text" id="input_pincode" name="pincode" value="{{ $pincode }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-slate-50 font-bold" required>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <input type="checkbox" name="is_site_address" id="is_site" value="1" {{ old('is_site_address', '1') ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_site" class="text-xs text-slate-700 font-medium">This is an active construction / project site (Requires heavy vehicle permit)</label>
                    </div>
                </div>

                <script>
                    function selectSavedAddress(addr) {
                        if (!addr) return;
                        if (document.getElementById('input_recipient_name')) document.getElementById('input_recipient_name').value = addr.recipient_name || '';
                        if (document.getElementById('input_phone')) document.getElementById('input_phone').value = addr.phone || '';
                        if (document.getElementById('input_address_line_1')) document.getElementById('input_address_line_1').value = addr.address_line_1 || '';
                        if (document.getElementById('input_city')) document.getElementById('input_city').value = addr.city || '';
                        if (document.getElementById('input_state')) document.getElementById('input_state').value = addr.state || '';
                        if (document.getElementById('input_pincode') && addr.pincode) document.getElementById('input_pincode').value = addr.pincode;
                        if (document.getElementById('is_site')) document.getElementById('is_site').checked = !!addr.is_site_address;
                    }
                </script>

                <!-- Delivery Slot Selection -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">
                        2. Scheduled Delivery Slot
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($slots as $idx => $slot)
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-indigo-500 bg-slate-50/50 cursor-pointer">
                                <input type="radio" name="delivery_slot_id" value="{{ $slot->id }}" {{ $idx == 0 ? 'checked' : '' }} class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="text-xs font-bold text-slate-900">{{ $slot->name }}</div>
                                    <div class="text-[11px] text-slate-500">Scheduled Dispatch from Central Depot</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">
                        3. Payment Method
                    </h2>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-4 rounded-xl border border-indigo-300 bg-indigo-50/40 cursor-pointer">
                            <input type="radio" name="payment_gateway" value="cod" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Cash / Pay upon Delivery (POD)</div>
                                <div class="text-[11px] text-slate-500">Verify material unloading at your construction site or warehouse before payment.</div>
                            </div>
                        </label>

                        @if(isset($wallet))
                            @if($wallet->balance >= $totals['grand_total'])
                                <label class="flex items-start gap-3 p-4 rounded-xl border border-emerald-300 bg-emerald-50/40 cursor-pointer">
                                    <input type="radio" name="payment_gateway" value="wallet" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-900">Customer Digital Wallet</span>
                                            <span class="text-[10px] font-bold bg-emerald-600 text-white px-2 py-0.5 rounded-full">
                                                Balance: ₹{{ number_format($wallet->balance / 100, 2) }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Instant atomic settlement directly from your accumulated wallet & cashback funds.</div>
                                    </div>
                                </label>
                            @else
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 opacity-60 flex items-start gap-3">
                                    <input type="radio" disabled class="mt-0.5 text-slate-300">
                                    <div>
                                        <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                            <span>Customer Digital Wallet</span>
                                            <span class="text-[10px] text-rose-600 font-semibold">(Insufficient Balance: ₹{{ number_format($wallet->balance / 100, 2) }})</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">Add funds or complete orders with COD to accumulate more loyalty cashback.</div>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if(isset($company))
                            @if($company->credit_balance >= $totals['grand_total'])
                                <label class="flex items-start gap-3 p-4 rounded-xl border border-indigo-300 bg-indigo-50/50 cursor-pointer">
                                    <input type="radio" name="payment_gateway" value="purchase_order" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-900">Corporate Purchase Order</span>
                                                <span class="text-[10px] font-bold bg-indigo-600 text-white px-2 py-0.5 rounded-full">
                                                    Net {{ $company->payment_terms_days }} Days
                                                </span>
                                            </div>
                                            <span class="text-[11px] font-bold text-emerald-600">
                                                Available Credit: ₹{{ number_format($company->credit_balance / 100, 2) }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-1">
                                            Financed by <strong>{{ $company->name }}</strong> ({{ $company->company_code }}).
                                            @if($companyUser && $companyUser->spending_limit !== null)
                                                <span>Your individual approval limit: ₹{{ number_format($companyUser->spending_limit / 100, 2) }}.</span>
                                            @endif
                                        </div>

                                        @php
                                            $requiresSupervisorSignoff = $companyUser && $companyUser->requiresApproval($totals['grand_total']);
                                        @endphp

                                        @if($requiresSupervisorSignoff)
                                            <div class="mt-2.5 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-900 flex items-start gap-2">
                                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <div>
                                                    <span class="font-bold">Supervisor Sign-Off Required:</span>
                                                    This requisition (₹{{ number_format($totals['grand_total'] / 100, 2) }}) exceeds your personal spending limit of ₹{{ number_format($companyUser->spending_limit / 100, 2) }}. It will be placed as <strong>Pending Approval</strong> and routed to your company supervisor before warehouse dispatch.
                                                </div>
                                            </div>
                                        @elseif($companyUser && $companyUser->spending_limit !== null)
                                            <div class="mt-2 p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Within your personal spending limit (₹{{ number_format($companyUser->spending_limit / 100, 2) }}). Order will be confirmed immediately upon placement.</span>
                                            </div>
                                        @endif

                                        <div class="mt-2.5">
                                            <input type="text" name="po_number" placeholder="Customer PO Reference # (e.g. PO-2026-001)" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </label>
                            @else
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 opacity-60 flex items-start gap-3">
                                    <input type="radio" disabled class="mt-0.5 text-slate-300">
                                    <div>
                                        <div class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                            <span>Corporate Purchase Order ({{ $company->name }})</span>
                                            <span class="text-[10px] text-rose-600 font-semibold">(Insufficient Credit: ₹{{ number_format($company->credit_balance / 100, 2) }})</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">Your organization's available credit line is less than the order grand total.</div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Site Delivery Instructions / Notes</label>
                        <textarea name="notes" rows="2" placeholder="e.g. Unload at Gate 2 near the cement mixer..." class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Right: Order Review & Confirm -->
            <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4 sticky top-24">
                <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100">Review & Place Order</h3>

                <div class="divide-y divide-slate-100 max-h-56 overflow-y-auto pr-1 text-xs">
                    @foreach($totals['lines'] as $line)
                        <div class="py-2.5 flex justify-between">
                            <div>
                                <div class="font-bold text-slate-900">{{ $line['product_name'] }}</div>
                                <div class="text-[11px] text-slate-500">{{ $line['quantity'] }} × ₹{{ number_format($line['unit_price'] / 100, 2) }}</div>
                            </div>
                            <div class="font-bold text-slate-900">₹{{ number_format($line['line_total'] / 100, 2) }}</div>
                        </div>
                    @endforeach
                </div>

                <!-- Coupon / Promo Code in Checkout -->
                @if(session('applied_coupon'))
                    <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span>🏷️</span>
                            <span class="font-mono font-bold text-emerald-900">{{ session('applied_coupon') }}</span>
                            <span class="text-[10px] bg-emerald-200 text-emerald-800 font-extrabold px-1.5 py-0.5 rounded">Applied</span>
                        </div>
                        <button type="button" onclick="event.preventDefault(); document.getElementById('checkout-coupon-remove-form').submit();" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition hover:underline">
                            Remove
                        </button>
                    </div>
                @else
                    <div class="pt-1">
                        <div class="flex gap-1.5">
                            <input type="text" id="checkout_coupon_input" placeholder="Promo / Coupon code" class="flex-1 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg uppercase font-mono focus:ring-2 focus:ring-indigo-500">
                            <button type="button" onclick="applyCheckoutCoupon()" class="px-3 py-1.5 bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold rounded-lg transition">Apply</button>
                        </div>
                    </div>
                @endif

                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-bold text-slate-900">₹{{ number_format($totals['subtotal'] / 100, 2) }}</span>
                    </div>
                    @if($totals['discount_total'] > 0)
                        <div class="flex justify-between text-emerald-600 font-semibold">
                            <span>Promotional Discounts</span>
                            <span>-₹{{ number_format($totals['discount_total'] / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span>Taxes & Cess</span>
                        <span class="font-bold text-slate-900">₹{{ number_format($totals['tax_total'] / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Transport & Delivery</span>
                        @if($totals['free_shipping'] || $totals['delivery_fee'] === 0)
                            <span class="font-bold text-emerald-600">FREE</span>
                        @else
                            <span class="font-bold text-slate-900">₹{{ number_format($totals['delivery_fee'] / 100, 2) }}</span>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline">
                    <span class="text-sm font-bold text-slate-900">Payable Total</span>
                    <span class="text-2xl font-black text-slate-900">₹{{ number_format($totals['grand_total'] / 100, 2) }}</span>
                </div>

                <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                    Confirm & Place Order &rarr;
                </button>
            </div>
        </div>
    </form>

    <form id="checkout-coupon-remove-form" action="{{ route('storefront.cart.coupon.remove') }}" method="POST" class="hidden">
        @csrf
    </form>
    <form id="checkout-coupon-apply-form" action="{{ route('storefront.cart.coupon') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="coupon_code" id="checkout_coupon_code_hidden">
    </form>
    <script>
        function applyCheckoutCoupon() {
            const input = document.getElementById('checkout_coupon_input');
            const code = input ? input.value.trim() : '';
            if (!code) return;
            document.getElementById('checkout_coupon_code_hidden').value = code;
            document.getElementById('checkout-coupon-apply-form').submit();
        }
    </script>
</div>
@endsection
