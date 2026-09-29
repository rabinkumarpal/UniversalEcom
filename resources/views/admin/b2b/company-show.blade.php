@extends('layouts.admin')

@section('title', $company->name . ' — B2B Company Details')
@section('header_title', $company->name)
@section('header_subtitle', 'Corporate Account Profile &bull; Code: ' . $company->company_code)

@section('content')
<div class="space-y-6">
    <!-- Back button & Quick Info Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.b2b.companies') }}" class="text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center gap-1">
            &larr; Back to Companies
        </a>
        <div class="flex items-center gap-2">
            <form action="{{ route('admin.b2b.companies.status', $company->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="status" value="{{ $company->status === 'active' ? 'suspended' : 'active' }}">
                <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition {{ $company->status === 'active' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50' }}">
                    {{ $company->status === 'active' ? 'Suspend Account' : 'Activate Account' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Company Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
            <span class="text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Credit Line</span>
            <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">₹{{ number_format($company->credit_limit / 100, 2) }}</div>
            <div class="text-slate-500 text-[11px] mt-1">Available: <span class="font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($company->credit_balance / 100, 2) }}</span></div>
        </div>

        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
            <span class="text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Payment Terms</span>
            <div class="text-xl font-bold text-slate-900 dark:text-white mt-1">Net {{ $company->payment_terms_days }} Days</div>
            <div class="text-slate-500 text-[11px] mt-1">Invoice settlement cycle</div>
        </div>

        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
            <span class="text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Customer Group</span>
            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                {{ $company->customerGroup ? $company->customerGroup->name : 'Standard' }}
            </div>
            <div class="text-slate-500 text-[11px] mt-1">
                {{ $company->customerGroup ? $company->customerGroup->discount_percentage . '% default discount' : 'Catalog prices' }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
            <span class="text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Tax ID / GSTIN</span>
            <div class="text-base font-bold font-mono text-slate-900 dark:text-white mt-1">
                {{ $company->tax_id ?? 'N/A' }}
            </div>
            <div class="text-slate-500 text-[11px] mt-1">Status: <span class="capitalize font-bold">{{ $company->status }}</span></div>
        </div>
    </div>

    <!-- Credit Adjustment & Users Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Credit Limit Adjustment -->
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Adjust Corporate Credit Line</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Increase or decrease the maximum account credit limit available to this organization for PO financing.</p>

            <form action="{{ route('admin.b2b.companies.credit_limit', $company->id) }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">New Credit Limit (₹)</label>
                    <input type="number" step="0.01" min="0" name="credit_limit_in_rupees" value="{{ $company->credit_limit / 100 }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-xs transition">
                    Update Credit Line
                </button>
            </form>
        </div>

        <!-- Corporate Users & Roles -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors space-y-4" x-data="{ showAssignForm: false }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Company Users &amp; Roles</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Buyers, approvers, and corporate administrators associated with this entity.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showAssignForm = !showAssignForm" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span x-text="showAssignForm ? 'Close Form' : 'Assign User'"></span>
                    </button>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                        {{ $company->companyUsers->count() }} Users
                    </span>
                </div>
            </div>

            <!-- Assign User Inline Form -->
            <div x-show="showAssignForm" x-cloak class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Assign Existing User to Corporate Account</h4>
                <form action="{{ route('admin.b2b.companies.users.assign', $company->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                    @csrf
                    <div class="sm:col-span-5">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Select User or enter Email *</label>
                        @if(!empty($availableUsers) && $availableUsers->isNotEmpty())
                            <select name="user_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500">
                                <option value="">-- Choose User --</option>
                                @foreach($availableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        @else
                            <input type="email" name="email" required placeholder="contractor@acmebuild.test" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500">
                        @endif
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
                        <select name="role" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500">
                            <option value="buyer">Buyer</option>
                            <option value="approver">Approver</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Limit (₹)</label>
                        <input type="number" step="0.01" min="0" name="spending_limit_in_rupees" placeholder="Unlimited" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2 flex items-end">
                        <button type="submit" class="w-full py-1.5 px-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg transition shadow-xs">
                            Assign
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">User</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">Spending Limit</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($company->companyUsers as $cu)
                            <tr>
                                <td class="p-3">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $cu->user?->name ?? 'Unknown' }}</p>
                                    <p class="text-slate-400 text-[11px]">{{ $cu->user?->email }}</p>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $cu->role === 'admin' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300' : ($cu->role === 'approver' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300') }}">
                                        {{ $cu->role }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono">
                                    @if($cu->spending_limit !== null)
                                        <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($cu->spending_limit / 100, 2) }}</span>
                                        <span class="text-slate-400 text-[10px]">/ order</span>
                                    @else
                                        <span class="text-slate-400 italic">Unlimited</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold {{ $cu->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $cu->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $cu->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <form action="{{ route('admin.b2b.companies.users.remove', [$company->id, $cu->user_id]) }}" method="POST" onsubmit="return confirm('Remove user from company account?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Remove user" class="p-1 text-slate-400 hover:text-rose-600 rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-400">No corporate users assigned yet. Use the "Assign User" button above to link buyers or managers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Purchase Orders History -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Corporate Purchase Orders</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">PO #</th>
                        <th class="p-3">Order Ref</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Requester</th>
                        <th class="p-3">Approver</th>
                        <th class="p-3">Due Date</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($company->purchaseOrders as $po)
                        <tr>
                            <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $po->po_number }}
                            </td>
                            <td class="p-3 font-mono">
                                @if($po->order)
                                    <a href="{{ route('admin.orders.show', $po->order_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Order #{{ $po->order->order_number }} &rarr;
                                    </a>
                                @else
                                    <span class="text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                ₹{{ number_format($po->amount / 100, 2) }}
                            </td>
                            <td class="p-3">
                                {{ $po->requester?->name ?? 'N/A' }}
                            </td>
                            <td class="p-3">
                                {{ $po->approver?->name ?? 'Awaiting Sign-off' }}
                            </td>
                            <td class="p-3 font-mono text-slate-500">
                                {{ $po->due_date ? $po->due_date->format('M d, Y') : 'Net 30' }}
                            </td>
                            <td class="p-3">
                                @if($po->status === 'approved')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">Approved</span>
                                @elseif($po->status === 'pending_approval')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">Pending Approval</span>
                                @elseif($po->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">Rejected</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ ucfirst($po->status) }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @if($po->status === 'pending_approval')
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('admin.b2b.purchase_orders.approve', $po->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] shadow-xs transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.b2b.purchase_orders.reject', $po->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50 font-bold text-[10px] transition">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400">No purchase orders placed by this organization yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
