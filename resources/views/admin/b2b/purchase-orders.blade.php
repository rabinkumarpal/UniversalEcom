@extends('layouts.admin')

@section('title', 'B2B Purchase Orders — Admin')
@section('header_title', 'Corporate Purchase Orders & Invoicing')
@section('header_subtitle', 'Review purchase orders, authorize credit clearances, and track Net 30/60 invoice cycles')

@section('content')
<div class="space-y-6">
    <!-- Top Filter Bar -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Corporate Purchase Orders</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Commercial transactions authorized via corporate credit accounts requiring supervisor validation.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.b2b.companies') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition">
                    &larr; Companies &amp; Credit
                </a>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('admin.b2b.purchase_orders.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>All Orders</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'all' ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['all'] ?? $purchaseOrders->total() }}</span>
            </a>
            <a href="{{ route('admin.b2b.purchase_orders.index', ['status' => 'pending_approval']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'pending_approval' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Pending Approval</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'pending_approval' ? 'bg-amber-500 text-white' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">{{ $statusCounts['pending_approval'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.b2b.purchase_orders.index', ['status' => 'approved']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Approved</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'approved' ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['approved'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.b2b.purchase_orders.index', ['status' => 'rejected']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Rejected</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentStatus === 'rejected' ? 'bg-rose-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['rejected'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">PO Reference &amp; Order</th>
                        <th class="p-4">Company</th>
                        <th class="p-4">Total Amount</th>
                        <th class="p-4">Requester / Buyer</th>
                        <th class="p-4">Due Date</th>
                        <th class="p-4">Status &amp; Notes</th>
                        <th class="p-4 text-right">Review Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 font-medium">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="p-4">
                                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 text-sm">{{ $po->po_number }}</span>
                                @if($po->order)
                                    <div>
                                        <a href="{{ route('admin.orders.show', $po->order_id) }}" class="font-mono text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Order #{{ $po->order->order_number }} &rarr;
                                        </a>
                                    </div>
                                @else
                                    <div class="font-mono text-[11px] text-slate-400 dark:text-slate-500">Ref: N/A</div>
                                @endif
                            </td>
                            <td class="p-4">
                                <a href="{{ route('admin.b2b.companies.show', $po->company_id) }}" class="font-bold text-slate-900 dark:text-white hover:underline">
                                    {{ $po->company?->name }}
                                </a>
                                <div class="font-mono text-[11px] text-slate-400">
                                    {{ $po->company?->company_code }}
                                </div>
                            </td>
                            <td class="p-4 font-bold text-slate-900 dark:text-white text-sm">
                                ₹{{ number_format($po->amount / 100, 2) }}
                            </td>
                            <td class="p-4">
                                <p class="text-slate-800 dark:text-slate-200">{{ $po->requester?->name ?? 'Unknown User' }}</p>
                                <p class="text-slate-400 text-[11px]">{{ $po->requester?->email }}</p>
                            </td>
                            <td class="p-4 font-mono text-slate-600 dark:text-slate-300">
                                {{ $po->due_date ? $po->due_date->format('M d, Y') : 'Net ' . $po->payment_terms_days . ' Days' }}
                            </td>
                            <td class="p-4">
                                @if($po->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Approved
                                    </span>
                                @elseif($po->status === 'pending_approval')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Approval
                                    </span>
                                @elseif($po->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ ucfirst($po->status) }}
                                    </span>
                                @endif

                                @if($po->approval_notes)
                                    <p class="text-[10px] text-slate-400 mt-1 italic max-w-xs truncate" title="{{ $po->approval_notes }}">
                                        {{ $po->approval_notes }}
                                    </p>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                @if($po->status === 'pending_approval')
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('admin.b2b.purchase_orders.approve', $po->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.b2b.purchase_orders.reject', $po->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50 font-bold text-xs transition">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px]">
                                        {{ $po->approver ? 'By: ' . $po->approver->name : 'Completed' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                No purchase orders matching the current filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchaseOrders->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
