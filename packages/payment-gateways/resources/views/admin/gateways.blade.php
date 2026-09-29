@extends('layouts.admin')

@section('title', 'Gateway Configurations')
@section('header_title', 'Payment Gateway Credentials & Rules')
@section('header_subtitle', 'Configure Razorpay API keys, webhooks, and Cash on Delivery advance deposit rules')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.payments.reconciliation.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
            &larr; Back to Payment Reconciliation Ledger
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Razorpay Configuration Card -->
        <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        Razorpay Payment Gateway
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">UPI, Netbanking, Rupay &amp; International Cards</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $razorpay->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $razorpay->is_active ? 'Active' : 'Disabled' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.payments.gateways.update', 'razorpay') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Razorpay Key ID</label>
                    <input type="text" name="key_id" value="{{ old('key_id', $razorpay->credentials['key_id'] ?? '') }}" required class="w-full px-3 py-2 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Razorpay Key Secret</label>
                    <input type="password" name="key_secret" value="{{ old('key_secret', $razorpay->credentials['key_secret'] ?? '') }}" required class="w-full px-3 py-2 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Webhook Secret (HMAC SHA-256)</label>
                    <input type="password" name="webhook_secret" value="{{ old('webhook_secret', $razorpay->credentials['webhook_secret'] ?? '') }}" required class="w-full px-3 py-2 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1 font-mono">Webhook URL: {{ url('/webhooks/razorpay') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_test_mode" value="1" {{ $razorpay->is_test_mode ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Test / Sandbox Mode</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $razorpay->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Enable Gateway</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">
                        Save Razorpay Credentials
                    </button>
                </div>
            </form>
        </div>

        <!-- Cash on Delivery Policy Card -->
        <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        Cash on Delivery &amp; Advance Policy
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Risk mitigation for high-value orders &amp; fleet consignments</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $cod->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $cod->is_active ? 'Active' : 'Disabled' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.payments.gateways.update', 'advanced_cod') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">High-Value Order Threshold (₹)</label>
                    <input type="number" step="100" name="advance_deposit_threshold" value="{{ old('advance_deposit_threshold', ($cod->settings['advance_deposit_threshold'] ?? 1000000) / 100) }}" required class="w-full px-3 py-2 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="text-[10px] text-slate-400 mt-1">Orders exceeding this value require an upfront commitment deposit before dispatch.</p>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Advance Commitment Deposit (%)</label>
                    <input type="number" min="1" max="100" name="advance_deposit_percentage" value="{{ old('advance_deposit_percentage', $cod->settings['advance_deposit_percentage'] ?? 15) }}" required class="w-full px-3 py-2 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <p class="text-[10px] text-slate-400 mt-1">Percentage of order total payable online upon placing order (balance paid on site).</p>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $cod->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Enable Cash on Delivery Channel</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold">
                        Save COD Policy Rules
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
