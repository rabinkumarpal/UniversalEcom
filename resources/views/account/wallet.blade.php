@extends('account.layout')

@section('title', 'Digital Wallet & Loyalty Cashback')

@section('account_content')
<div class="space-y-6" x-data="{ showTopup: false, customAmount: '1000' }">
    <!-- Header with Top-Up Trigger -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Customer Digital Wallet</h1>
            <p class="text-xs text-slate-500">Earn automatic 2% loyalty cashback on every order. Use wallet balance for instant, zero-friction checkouts.</p>
        </div>
        <button type="button" @click="showTopup = !showTopup"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            <span x-text="showTopup ? 'Close Top-Up Form' : 'Add Funds to Wallet'">Add Funds to Wallet</span>
        </button>
    </div>

    <!-- Balance & Metric Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Available Wallet Balance -->
        <div class="bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-950 text-white p-5 rounded-2xl shadow-sm space-y-2 col-span-1 sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-200">Available Balance</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    {{ $wallet->status }}
                </span>
            </div>
            <div class="text-3xl font-black tracking-tight font-mono">
                ₹{{ number_format($wallet->balance / 100, 2) }}
            </div>
            <div class="text-[10px] text-slate-300 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Instant settlement at checkout</span>
            </div>
        </div>

        <!-- Total Cashback Earned -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Cashback Earned</span>
            <div class="text-2xl font-black text-emerald-600 font-mono">
                ₹{{ number_format($totalCashbackEarned / 100, 2) }}
            </div>
            <p class="text-[10px] text-slate-500">2% automated rewards accrued</p>
        </div>

        <!-- Total Prepaid Top-Ups -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Prepaid Top-Ups</span>
            <div class="text-2xl font-black text-indigo-600 font-mono">
                ₹{{ number_format($totalTopups / 100, 2) }}
            </div>
            <p class="text-[10px] text-slate-500">Self-funded balance loaded</p>
        </div>

        <!-- Total Spent From Wallet -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Wallet Spent</span>
            <div class="text-2xl font-black text-slate-800 font-mono">
                ₹{{ number_format($totalSpent / 100, 2) }}
            </div>
            <p class="text-[10px] text-slate-500">Zero-fee purchases completed</p>
        </div>
    </div>

    <!-- Interactive Top-Up Form Drawer -->
    <div x-show="showTopup" x-cloak class="bg-white border border-indigo-200 rounded-2xl p-6 shadow-sm space-y-4 transition">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Add Prepaid Balance to Digital Wallet</h3>
                <p class="text-xs text-slate-500 mt-0.5">Preload funds for frictionless 1-click purchases and project supplies without card entry delays.</p>
            </div>
            <button type="button" @click="showTopup = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">
                &times; Close
            </button>
        </div>

        <form action="{{ route('account.wallet.topup') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Quick Amount Presets -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Select Top-Up Amount</label>
                <div class="flex flex-wrap gap-2">
                    @foreach([500, 1000, 2500, 5000, 10000] as $preset)
                        <button type="button" @click="customAmount = '{{ $preset }}'"
                                :class="customAmount == '{{ $preset }}' ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                class="px-3.5 py-1.5 rounded-xl border text-xs font-mono transition">
                            ₹{{ number_format($preset) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Custom Amount Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Custom Amount (₹) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold text-sm">₹</span>
                        <input type="number" name="amount_in_rupees" x-model="customAmount" min="10" max="100000" step="1" required
                               class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Minimum top-up: ₹10 &bull; Maximum: ₹1,00,000</span>
                </div>

                <!-- Payment Reference Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Bank / UPI Transaction Reference (optional)</label>
                    <input type="text" name="payment_reference" placeholder="e.g. UPI-9988776655 or NEFT-REF"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="text-[10px] text-slate-400 mt-1 block">For your personal bookkeeping and audit reference</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="showTopup = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Confirm &amp; Load Balance</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Immutable Transaction Ledger -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs space-y-0">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Wallet Financial Ledger</h2>
                <p class="text-xs text-slate-400">Chronological immutable transaction record with append-only audit trail</p>
            </div>

            <!-- Ledger Filter Tabs -->
            <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 text-xs">
                <a href="{{ route('account.wallet', ['type' => 'all']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ ($currentType ?? 'all') === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    All
                </a>
                <a href="{{ route('account.wallet', ['type' => 'credit']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ ($currentType ?? '') === 'credit' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Credits &amp; Cashback
                </a>
                <a href="{{ route('account.wallet', ['type' => 'debit']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ ($currentType ?? '') === 'debit' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Debits &amp; Purchases
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3">Date &amp; Time</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Balance After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    @forelse($entries as $entry)
                        @php
                            $refBadge = match($entry->reference_type) {
                                'cashback' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'topup' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'order_payment' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'refund' => 'bg-teal-50 text-teal-700 border-teal-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                {{ $entry->created_at->format('M d, Y • h:i A') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $refBadge }}">
                                    {{ str_replace('_', ' ', $entry->reference_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-900">
                                {{ $entry->description }}
                            </td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-slate-500">
                                {{ $entry->reference_id ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-sm {{ $entry->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $entry->type === 'credit' ? '+' : '-' }}₹{{ number_format($entry->amount / 100, 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 text-sm">
                                ₹{{ number_format($entry->balance_after / 100, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                                No wallet transactions found for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
