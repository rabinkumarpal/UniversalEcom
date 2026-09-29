@extends('layouts.admin')

@section('title', 'Platform Settings & Feature Flags')
@section('header_title', 'Platform Settings & Policy Matrix')
@section('header_subtitle', 'Manage global store configurations, tax policies, delivery thresholds, and runtime feature flags')

@section('content')
<div class="max-w-5xl space-y-8">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between">
            <span>✓ {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs flex items-center justify-between">
            <span>⚠ {{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
        @csrf

        <!-- Store & Business Profile -->
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6 transition-colors">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-900 pb-4">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                    🏬
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Store Identity &amp; Legal Entity</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Public trade name, support desk contact, and corporate registration details</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Name</label>
                    <input type="text" name="store_name" value="{{ old('store_name', $settings['store_name']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Official Operations Email</label>
                    <input type="email" name="store_email" value="{{ old('store_email', $settings['store_email']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Customer Care &amp; Dispatch Phone</label>
                    <input type="text" name="store_phone" value="{{ old('store_phone', $settings['store_phone']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Registered Depot / Hub Address</label>
                    <input type="text" name="store_address" value="{{ old('store_address', $settings['store_address']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>
            </div>
        </div>

        <!-- Currency & Tax Matrix -->
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6 transition-colors">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-900 pb-4">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                    🪙
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Currency, Pricing &amp; GST Tax Policies</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Server-authoritative fiscal accounting and invoice rules</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Base Currency ISO</label>
                    <input type="text" name="currency" value="{{ old('currency', $settings['currency']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Default Tax Rate (%)</label>
                    <input type="number" step="0.01" name="default_tax_rate" value="{{ old('default_tax_rate', $settings['default_tax_rate']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                        <input type="checkbox" name="prices_include_tax" value="1" {{ $settings['prices_include_tax'] ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-0">
                        <span>Prices inclusive of Tax</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Orders & Fulfillment Rates -->
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6 transition-colors">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-900 pb-4">
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-600/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">
                    🚚
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Order Minimums &amp; Delivery Tariffs</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Checkout qualification criteria and logistics fee calculation boundaries</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Minimum Order Value (₹)</label>
                    <input type="number" step="1" name="min_order_amount" value="{{ old('min_order_amount', $settings['min_order_amount']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Free Delivery Threshold (₹)</label>
                    <input type="number" step="1" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Standard Delivery (₹)</label>
                    <input type="number" step="1" name="standard_shipping_rate" value="{{ old('standard_shipping_rate', $settings['standard_shipping_rate']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Express Delivery (₹)</label>
                    <input type="number" step="1" name="express_shipping_rate" value="{{ old('express_shipping_rate', $settings['express_shipping_rate']) }}" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900">
                </div>
            </div>
        </div>

        <!-- Runtime Feature Flags -->
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-6 transition-colors">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-900 pb-4">
                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-600/20 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-sm">
                    🚩
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Runtime Feature Flags</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Toggle customer-facing capabilities without modifying application code</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 transition">
                    <input type="checkbox" name="reviews_enabled" value="1" {{ $settings['reviews_enabled'] ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-0">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Verified Customer Reviews</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Allow verified buyers to post star ratings and product feedback</span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 transition">
                    <input type="checkbox" name="wallet_cashback_enabled" value="1" {{ $settings['wallet_cashback_enabled'] ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-0">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Loyalty Cashback &amp; Wallet Ledger</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Credit automatic 2% cashback into customer digital wallet upon order completion</span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 transition">
                    <input type="checkbox" name="marketplace_enabled" value="1" {{ $settings['marketplace_enabled'] ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-0">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Vendor Marketplace Registration</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Permit external merchants and suppliers to register for marketplace portals</span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 transition">
                    <input type="checkbox" name="guest_checkout_enabled" value="1" {{ $settings['guest_checkout_enabled'] ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-0">
                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block">Express Guest Checkout</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Allow customers to complete checkout before creating an authenticated account</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white transition shadow-sm">
                Save Platform Settings
            </button>
        </div>
    </form>
</div>
@endsection
