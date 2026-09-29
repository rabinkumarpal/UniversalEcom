@extends('layouts.admin')

@section('title', 'GST Invoices & Billing')
@section('header_title', 'GST Tax Invoices & Billing Register')
@section('header_subtitle', 'Statutory GST compliance, E-Invoicing records, and fiscal tax ledger')

@section('content')
<div class="space-y-6">
    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Financial Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Tax Invoices</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ $totalInvoices }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Statutory FY Series</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Taxable Turnover</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">₹{{ number_format($totalTaxable / 100, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Excluding GST</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total GST Accrued</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1">₹{{ number_format($totalTax / 100, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">CGST+SGST: ₹{{ number_format(($totalCgst + $totalSgst) / 100, 2) }} | IGST: ₹{{ number_format($totalIgst / 100, 2) }}</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Gross Billed Value</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">₹{{ number_format($totalRevenue / 100, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Total Invoiced Amount</div>
        </div>
    </div>

    <!-- Invoices Register Table -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
        <!-- Search & Filters -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800">
            <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search invoice #, buyer, GSTIN, order #..."
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs w-64 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >

                <select name="supply_type" class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Supply Types</option>
                    <option value="INTRA_STATE" {{ request('supply_type') === 'INTRA_STATE' ? 'selected' : '' }}>Intra-State (CGST+SGST)</option>
                    <option value="INTER_STATE" {{ request('supply_type') === 'INTER_STATE' ? 'selected' : '' }}>Inter-State (IGST)</option>
                </select>

                <select name="is_b2b" class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Buyer Types</option>
                    <option value="1" {{ request('is_b2b') === '1' ? 'selected' : '' }}>B2B Corporate (GSTIN)</option>
                    <option value="0" {{ request('is_b2b') === '0' ? 'selected' : '' }}>B2C Consumer</option>
                </select>

                <select name="status" class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled / Credit Note</option>
                </select>

                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3 px-4">Invoice No &amp; Date</th>
                        <th class="py-3 px-4">Order Ref</th>
                        <th class="py-3 px-4">Buyer Details</th>
                        <th class="py-3 px-4">Supply &amp; State</th>
                        <th class="py-3 px-4 text-right">Taxable (₹)</th>
                        <th class="py-3 px-4 text-right">Total Tax (₹)</th>
                        <th class="py-3 px-4 text-right">Grand Total (₹)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-slate-900 dark:text-white">{{ $invoice->invoice_number }}</div>
                                <div class="text-[11px] text-slate-500">{{ $invoice->invoice_date->format('M d, Y') }}</div>
                            </td>

                            <td class="py-3 px-4">
                                <a href="{{ route('admin.orders', ['search' => $invoice->order->order_number]) }}" class="text-indigo-600 dark:text-indigo-400 font-mono font-bold hover:underline">
                                    {{ $invoice->order->order_number }}
                                </a>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $invoice->buyer_name }}</div>
                                @if($invoice->buyer_gstin)
                                    <div class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400">GSTIN: {{ $invoice->buyer_gstin }}</div>
                                @else
                                    <div class="text-[10px] text-slate-400">B2C Retail Buyer</div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $invoice->isIntraState() ? 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300' : 'bg-purple-50 dark:bg-purple-950 text-purple-700 dark:text-purple-300' }}">
                                    {{ $invoice->isIntraState() ? 'Intra-State (29)' : 'Inter-State ('.$invoice->place_of_supply_state_code.')' }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format($invoice->taxable_amount / 100, 2) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                {{ number_format(($invoice->cgst_amount + $invoice->sgst_amount + $invoice->igst_amount) / 100, 2) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900 dark:text-white">
                                {{ number_format($invoice->total_amount / 100, 2) }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($invoice->isCancelled())
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        Cancelled
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        Issued
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('account.invoices.show', $invoice->invoice_number) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 font-bold text-xs inline-flex items-center gap-1 transition">
                                    <span>Print / View</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500">
                                No tax invoices found matching current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
