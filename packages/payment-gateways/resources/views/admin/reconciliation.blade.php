@extends('layouts.admin')

@section('title', 'Payment Reconciliation')
@section('header_title', 'Financial Payment Reconciliation Ledger')
@section('header_subtitle', 'Audit driver cash collections, advance deposits, and digital gateway settlements')

@section('content')
<div class="space-y-6">
    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Financial KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Invoiced Volume</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">₹{{ number_format($totalExpected / 100, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Across All Orders</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Collected &amp; Remitted</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">₹{{ number_format($totalCollected / 100, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Verified In Bank / Vault</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Pending Remittance</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono mt-1">₹{{ number_format($totalBalance / 100, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">In Transit with Fleet Drivers</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Audit Discrepancies</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1">{{ number_format($discrepancyCount) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Shortage or Mismatches</div>
        </div>
    </div>

    <!-- Toolbar & Filter Bar -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <form method="GET" action="{{ route('admin.payments.reconciliation.index') }}" class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search rec #, order #, reference..."
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs w-64 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >

                <select
                    name="method"
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >
                    <option value="all">All Methods</option>
                    <option value="razorpay" {{ request('method') === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                    <option value="advanced_cod" {{ request('method') === 'advanced_cod' ? 'selected' : '' }}>Advanced COD</option>
                    <option value="cod" {{ request('method') === 'cod' ? 'selected' : '' }}>Standard COD</option>
                </select>

                <select
                    name="status"
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >
                    <option value="all">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="settled" {{ request('status') === 'settled' ? 'selected' : '' }}>Settled</option>
                    <option value="discrepancy" {{ request('status') === 'discrepancy' ? 'selected' : '' }}>Discrepancy</option>
                </select>

                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white dark:bg-slate-200 dark:text-slate-900 text-xs font-bold hover:opacity-90">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'method', 'status']))
                    <a href="{{ route('admin.payments.reconciliation.index') }}" class="px-2 py-1.5 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                        Reset
                    </a>
                @endif
            </form>

            <a href="{{ route('admin.payments.gateways.index') }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-900">
                Gateway Configurations &rarr;
            </a>
        </div>

        <!-- Reconciliation Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 font-bold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3">Rec # / Date</th>
                        <th class="p-3">Order</th>
                        <th class="p-3">Method</th>
                        <th class="p-3 text-right">Expected</th>
                        <th class="p-3 text-right">Collected</th>
                        <th class="p-3 text-right">Balance</th>
                        <th class="p-3">Settlement Status</th>
                        <th class="p-3">Remittance Ref / Agent</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                            <td class="p-3 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900 dark:text-white">{{ $rec->reconciliation_number }}</div>
                                <div class="text-[10px] text-slate-400">{{ $rec->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($rec->order)
                                    <a href="{{ route('admin.orders.show', $rec->order->id) }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $rec->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($rec->method === 'razorpay')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800">
                                        Razorpay
                                    </span>
                                @elseif($rec->method === 'advanced_cod')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                        Adv. COD ({{ $rec->advance_amount > 0 ? 'Split' : 'Full' }})
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                                        {{ strtoupper($rec->method) }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                ₹{{ number_format($rec->expected_amount / 100, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                ₹{{ number_format($rec->collected_amount / 100, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                ₹{{ number_format($rec->balance_amount / 100, 2) }}
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($rec->settlement_status === 'settled')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Settled
                                    </span>
                                @elseif($rec->settlement_status === 'partial')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950/80 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800">
                                        Partial
                                    </span>
                                @elseif($rec->settlement_status === 'pending')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                        Pending
                                    </span>
                                @elseif($rec->settlement_status === 'discrepancy')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800" title="{{ $rec->discrepancy_reason }}">
                                        Discrepancy
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                @if($rec->reference_number)
                                    <div class="font-mono text-[11px] text-slate-700 dark:text-slate-300">{{ $rec->reference_number }}</div>
                                @endif
                                @if($rec->collectedBy)
                                    <div class="text-[10px] text-slate-400">Driver: {{ $rec->collectedBy->name }}</div>
                                @endif
                                @if($rec->verifiedBy)
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400">Verified by: {{ $rec->verifiedBy->name }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($rec->settlement_status !== 'settled')
                                        <button onclick="openSettleModal('{{ $rec->id }}', '{{ $rec->reconciliation_number }}', '{{ $rec->balance_amount / 100 }}')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white">
                                            Settle
                                        </button>
                                        <button onclick="openDiscrepancyModal('{{ $rec->id }}', '{{ $rec->reconciliation_number }}')" class="px-2 py-1 text-[11px] font-bold rounded-lg border border-rose-300 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50">
                                            Flag
                                        </button>
                                    @else
                                        <span class="text-slate-400 text-[11px]">Reconciled</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                No financial reconciliation records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Settle Modal -->
<div id="settle-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Confirm Financial Settlement</h3>
        <p class="text-xs text-slate-500" id="settle-modal-subtitle"></p>

        <form method="POST" id="settle-form" action="" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Settlement Amount (₹)</label>
                <input type="number" step="0.01" name="amount" id="settle-amount-input" required class="w-full px-3 py-2 text-xs font-mono rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Reference / UTR / Deposit Slip #</label>
                <input type="text" name="reference_number" placeholder="e.g. UTR-998822001" required class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-emerald-500 font-mono">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('settle-modal').classList.add('hidden')" class="px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400">Cancel</button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg">Confirm Settlement</button>
            </div>
        </form>
    </div>
</div>

<!-- Discrepancy Modal -->
<div id="discrepancy-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white text-rose-600">Flag Collection Discrepancy</h3>
        <p class="text-xs text-slate-500" id="discrepancy-modal-subtitle"></p>

        <form method="POST" id="discrepancy-form" action="" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Discrepancy Audit Reason</label>
                <textarea name="reason" rows="3" required placeholder="e.g. Driver remitted ₹500 short cash or recipient refused payment." class="w-full px-3 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('discrepancy-modal').classList.add('hidden')" class="px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400">Cancel</button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white rounded-lg">Flag Discrepancy</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSettleModal(id, recNumber, balance) {
    document.getElementById('settle-modal-subtitle').innerText = 'Reconciliation: #' + recNumber;
    document.getElementById('settle-amount-input').value = balance;
    document.getElementById('settle-form').action = '/admin/payments/reconciliation/' + id + '/settle';
    document.getElementById('settle-modal').classList.remove('hidden');
}

function openDiscrepancyModal(id, recNumber) {
    document.getElementById('discrepancy-modal-subtitle').innerText = 'Reconciliation: #' + recNumber;
    document.getElementById('discrepancy-form').action = '/admin/payments/reconciliation/' + id + '/discrepancy';
    document.getElementById('discrepancy-modal').classList.remove('hidden');
}
</script>
@endsection
