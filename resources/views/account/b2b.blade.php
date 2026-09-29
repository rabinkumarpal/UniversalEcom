@extends('account.layout')

@section('account_content')
<div class="space-y-6">
    <!-- Success / Error Flash Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Corporate Account & Credit Line Facility Overview -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-lg font-black text-slate-900">{{ $company->name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                        {{ $company->company_code }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                        {{ ucfirst($company->status) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    GSTIN / Tax ID: <strong class="font-mono text-slate-700">{{ $company->tax_id ?? 'Unregistered' }}</strong> • Commercial Account
                </p>
            </div>

            <!-- Member Role & Authority Badge -->
            <div class="flex items-center gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Your Corporate Role</span>
                    <span class="font-bold text-slate-900 capitalize">{{ $companyUser->role }}</span>
                </div>
                <div class="border-l border-slate-200 pl-3 ml-1">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Individual Spending Limit</span>
                    <span class="font-mono font-bold text-indigo-600">
                        @if($companyUser->spending_limit !== null)
                            ₹{{ number_format($companyUser->spending_limit / 100, 2) }}
                        @else
                            Unlimited / Approver
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Credit Facility Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Credit Facility</span>
                <span class="text-xl font-black text-slate-900 mt-1 block font-mono">₹{{ number_format($creditLimit / 100, 2) }}</span>
                <span class="text-[11px] text-slate-400 mt-0.5 block">Approved corporate credit line</span>
            </div>

            <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-100">
                <span class="text-[10px] uppercase font-bold text-emerald-700 block">Available Credit Balance</span>
                <span class="text-xl font-black text-emerald-700 mt-1 block font-mono">₹{{ number_format($creditBalance / 100, 2) }}</span>
                <span class="text-[11px] text-emerald-600 mt-0.5 block">Available for instant checkout</span>
            </div>

            <div class="p-4 rounded-xl bg-indigo-50/60 border border-indigo-100">
                <span class="text-[10px] uppercase font-bold text-indigo-700 block">Credit Terms</span>
                <span class="text-xl font-black text-indigo-700 mt-1 block">Net {{ $company->payment_terms_days }} Days</span>
                <span class="text-[11px] text-indigo-600 mt-0.5 block">Post-dispatch settlement cycle</span>
            </div>
        </div>

        <!-- Credit Utilization Progress Bar -->
        <div class="space-y-1.5 pt-1">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700">Credit Facility Utilization</span>
                <span class="font-mono text-slate-500">
                    Utilized: <strong>₹{{ number_format($creditUsed / 100, 2) }}</strong> of ₹{{ number_format($creditLimit / 100, 2) }} ({{ $creditUsedPercentage }}%)
                </span>
            </div>
            <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden flex">
                <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $creditUsedPercentage }}%"></div>
            </div>
        </div>
    </div>

    <!-- Supervisor Approval Cockpit (If Approver & Pending Items Exist) -->
    @if($companyUser->isApprover() && $pendingApprovals->isNotEmpty())
        <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-amber-200/80 text-amber-800 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-black text-amber-950 text-sm">Pending Purchase Orders Awaiting Your Sign-Off</h3>
                        <p class="text-xs text-amber-800">Junior buyer orders that exceeded individual spending limits and require supervisor authorization.</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-amber-200 text-amber-900 rounded-full font-black text-xs">
                    {{ $pendingApprovals->count() }} Pending
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @foreach($pendingApprovals as $pendingPo)
                    <div class="bg-white rounded-xl border border-amber-200/80 p-4 shadow-2xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="space-y-1 text-xs">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono font-bold text-slate-900 text-sm">{{ $pendingPo->po_number }}</span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600">Order: <strong class="font-mono text-indigo-600">#{{ $pendingPo->order->order_number ?? 'N/A' }}</strong></span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600">Requested by: <strong>{{ $pendingPo->requester->name ?? 'Buyer' }}</strong></span>
                            </div>
                            <div class="text-slate-500">
                                Total: <strong class="font-mono text-slate-900 text-sm">₹{{ number_format($pendingPo->amount / 100, 2) }}</strong>
                                • Terms: Net {{ $pendingPo->payment_terms_days }} Days
                                • Submitted: {{ $pendingPo->created_at->format('M d, Y H:i') }}
                            </div>
                            @if($pendingPo->approval_notes)
                                <div class="text-[11px] text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-100">
                                    {{ $pendingPo->approval_notes }}
                                </div>
                            @endif
                        </div>

                        <!-- 1-Click Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 w-full md:w-auto">
                            <a href="{{ route('account.b2b.po.show', $pendingPo->po_number) }}" target="_blank"
                               class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition">
                                Review PO
                            </a>

                            <form method="POST" action="{{ route('account.b2b.approve', $pendingPo->id) }}" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Authorize and confirm Purchase Order #{{ $pendingPo->po_number }} for warehouse fulfillment?')"
                                        class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                                    ✓ Approve
                                </button>
                            </form>

                            <form method="POST" action="{{ route('account.b2b.reject', $pendingPo->id) }}" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Reject Purchase Order #{{ $pendingPo->po_number }}? This will release reserved credit back to your company line.')"
                                        class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition cursor-pointer">
                                    ✕ Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Corporate Purchase Order History Ledger -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-sm">Purchase Order &amp; Requisition Ledger</h3>
                <p class="text-xs text-slate-500">Official commercial requisitions issued under your corporate credit facility.</p>
            </div>

            <!-- Filter Status Tabs -->
            <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 text-xs">
                <a href="{{ route('account.b2b.index', ['status' => 'all']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ $currentStatus === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    All
                </a>
                <a href="{{ route('account.b2b.index', ['status' => 'pending_approval']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ $currentStatus === 'pending_approval' ? 'bg-amber-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Pending
                </a>
                <a href="{{ route('account.b2b.index', ['status' => 'approved']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ $currentStatus === 'approved' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Approved
                </a>
                <a href="{{ route('account.b2b.index', ['status' => 'rejected']) }}"
                   class="px-3 py-1 rounded-lg font-bold transition {{ $currentStatus === 'rejected' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">
                    Rejected
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">PO Reference #</th>
                        <th class="p-4">Customer Order</th>
                        <th class="p-4">Requested By</th>
                        <th class="p-4">Issue Date</th>
                        <th class="p-4">Terms &amp; Due Date</th>
                        <th class="p-4">Requisition Total</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="p-4 font-mono font-bold text-slate-900">{{ $po->po_number }}</td>
                            <td class="p-4 font-mono text-indigo-600">
                                @if($po->order)
                                    <a href="{{ route('storefront.order_confirmation', $po->order->order_number) }}" class="hover:underline">
                                        #{{ $po->order->order_number }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="p-4 text-slate-700">{{ $po->requester->name ?? 'Corporate Buyer' }}</td>
                            <td class="p-4 text-slate-500">{{ $po->created_at->format('M d, Y') }}</td>
                            <td class="p-4 text-slate-600">
                                <div>Net {{ $po->payment_terms_days }} Days</div>
                                @if($po->due_date)
                                    <div class="text-[10px] text-slate-400">Due: {{ $po->due_date->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="p-4 font-mono font-bold text-slate-900">
                                ₹{{ number_format($po->amount / 100, 2) }}
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold
                                    {{ $po->status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : '' }}
                                    {{ $po->status === 'pending_approval' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }}
                                    {{ $po->status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' : '' }}">
                                    {{ $po->status === 'pending_approval' ? 'Pending Approval' : ucfirst($po->status) }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('account.b2b.po.show', $po->po_number) }}" target="_blank"
                                   class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs transition inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    <span>Print PO</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                No purchase orders found matching this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseOrders->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
