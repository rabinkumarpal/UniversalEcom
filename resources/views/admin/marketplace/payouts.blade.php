@extends('layouts.admin')

@section('title', 'Marketplace Payouts — Admin')
@section('header_title', 'Vendor Payouts & Settlement Reconciliation')
@section('header_subtitle', 'Review settlement disbursements, authorize batches, and record electronic bank transaction references')

@section('content')
<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs transition-colors">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Merchant Settlement Statements</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Authorize and record electronic bank/NEFT/RTGS/UPI disbursements to approved marketplace sellers.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.marketplace.vendors') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition">
                &larr; Manage Merchants &amp; Commission
            </a>
        </div>
    </div>

    <!-- Financial KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Total Statements -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Settlement Statements</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">{{ number_format($kpis['total_statements']) }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1 font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Statements in current scope
            </div>
        </div>

        <!-- Total Disbursed -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Disbursed</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">₹{{ number_format($kpis['total_disbursed'] / 100, 2) }}</div>
            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1 font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Settled bank disbursements
            </div>
        </div>

        <!-- Pending Settlement -->
        <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs relative overflow-hidden transition-colors">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pending Settlement</div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">₹{{ number_format($kpis['pending_settlement'] / 100, 2) }}</div>
            <div class="text-[10px] text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1 font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Awaiting approval or bank transfer
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors space-y-4">
        <form method="GET" action="{{ route('admin.marketplace.payouts') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="status" value="{{ $currentStatus }}">

            <!-- Search Field -->
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search statement #, UTR ref, vendor..."
                       class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Merchant / Vendor Selector -->
            <div class="w-full sm:w-64">
                <select name="vendor_id" class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <option value="">All Merchants / Vendors</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ (string)$selectedVendorId === (string)$vendor->id ? 'selected' : '' }}>
                            {{ $vendor->display_name }} ({{ $vendor->legal_name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-xs transition">
                Filter Statements
            </button>

            @if(!empty($search) || !empty($selectedVendorId))
                <a href="{{ route('admin.marketplace.payouts', ['status' => $currentStatus]) }}" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold underline whitespace-nowrap">
                    Clear Filters
                </a>
            @endif
        </form>

        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('admin.marketplace.payouts', array_filter(['status' => 'all', 'search' => $search, 'vendor_id' => $selectedVendorId])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>All Statements</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'all' ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['all'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.marketplace.payouts', array_filter(['status' => 'pending', 'search' => $search, 'vendor_id' => $selectedVendorId])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Pending Review</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'pending' ? 'bg-amber-500 text-white' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">{{ $statusCounts['pending'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.marketplace.payouts', array_filter(['status' => 'approved', 'search' => $search, 'vendor_id' => $selectedVendorId])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'approved' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Approved</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'approved' ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['approved'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.marketplace.payouts', array_filter(['status' => 'paid', 'search' => $search, 'vendor_id' => $selectedVendorId])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'paid' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Settled &amp; Paid</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'paid' ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['paid'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <!-- Payouts Table -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Statement # &amp; Cycle</th>
                        <th class="p-4">Merchant</th>
                        <th class="p-4">Disbursement Amount</th>
                        <th class="p-4">Requested At</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Settled Details</th>
                        <th class="p-4 text-right">Settlement Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 font-medium">
                    @forelse($payouts as $payout)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="p-4">
                                <p class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $payout->payout_number }}</p>
                                @if($payout->period_start && $payout->period_end)
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                        {{ $payout->period_start->format('M d') }} &ndash; {{ $payout->period_end->format('M d, Y') }}
                                    </p>
                                @endif
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $payout->vendor->display_name ?? 'Unknown Merchant' }}</p>
                                <p class="text-slate-500 dark:text-slate-400 text-[11px]">{{ $payout->vendor->legal_name ?? '' }}</p>
                                <p class="text-slate-400 dark:text-slate-500 text-[10px]">{{ $payout->vendor->email ?? '' }}</p>
                            </td>
                            <td class="p-4 font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                ₹{{ number_format($payout->amount / 100, 2) }}
                            </td>
                            <td class="p-4 text-slate-500 dark:text-slate-400">
                                {{ $payout->created_at->format('M d, Y h:i A') }}
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                    {{ $payout->status === 'paid' ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : '' }}
                                    {{ $payout->status === 'approved' ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800' : '' }}
                                    {{ $payout->status === 'pending' ? 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : '' }}">
                                    {{ $payout->status }}
                                </span>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-300">
                                @if($payout->status === 'paid')
                                    <p class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $payout->paid_at ? $payout->paid_at->format('M d, Y') : 'Settled' }}</p>
                                    <p class="font-mono text-[11px] text-slate-400 dark:text-slate-500">Ref: {{ $payout->payment_reference }}</p>
                                @elseif($payout->status === 'approved')
                                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-xs flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Approved for Transfer
                                    </span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 italic">Pending review</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                @if($payout->status === 'pending')
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- 1-Click Approve Action -->
                                        <form method="POST" action="{{ route('admin.marketplace.payouts.approve', $payout->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded font-bold text-xs shadow-xs transition">
                                                Approve
                                            </button>
                                        </form>

                                        <!-- Direct Settle Form -->
                                        <form method="POST" action="{{ route('admin.marketplace.payouts.settle', $payout->id) }}" class="inline-flex items-center gap-1.5">
                                            @csrf
                                            <input type="text" name="payment_reference" placeholder="UTR / Ref #" required
                                                   class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-slate-900 dark:text-white text-xs font-mono w-28 focus:bg-white dark:focus:bg-slate-900">
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded font-bold text-xs shadow-xs transition whitespace-nowrap">
                                                Mark Paid
                                            </button>
                                        </form>
                                    </div>
                                @elseif($payout->status === 'approved')
                                    <form method="POST" action="{{ route('admin.marketplace.payouts.settle', $payout->id) }}" class="inline-flex items-center gap-2">
                                        @csrf
                                        <input type="text" name="payment_reference" placeholder="UTR / Bank Ref #" required
                                               class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1 text-slate-900 dark:text-white text-xs font-mono w-36 focus:bg-white dark:focus:bg-slate-900">
                                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded font-bold text-xs shadow-xs transition">
                                            Mark Paid
                                        </button>
                                    </form>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs flex items-center justify-end gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Disbursed
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No vendor payout statements match your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
