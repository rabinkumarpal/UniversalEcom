@extends('layouts.admin')

@section('title', 'B2B Companies & Accounts — Admin')
@section('header_title', 'Corporate Accounts & Credit Lines')
@section('header_subtitle', 'Manage multi-tier corporate organizations, credit limits, and payment terms')

@section('content')
<div class="space-y-6">
    <!-- Top Bar with Quick Stats & Action -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Corporate Organizations</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isolated B2B commercial entities with credit terms, roles, and contract price lists.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.b2b.price_lists.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition">
                    Contract Price Lists &rarr;
                </a>
                <a href="{{ route('admin.b2b.purchase_orders.index') }}" class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-700 dark:text-indigo-300 font-bold text-xs border border-indigo-200 dark:border-indigo-800/60 shadow-xs transition">
                    Purchase Orders &rarr;
                </a>
            </div>
        </div>

        <!-- Inline New Company Registration Accordion / Form -->
        <div x-data="{ open: false }" class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
            <button @click="open = !open" class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">
                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span>+ Register New Corporate Account</span>
            </button>

            <form x-show="open" x-cloak action="{{ route('admin.b2b.companies.store') }}" method="POST" class="mt-4 p-5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Company Legal Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Apex Infrastructure Ltd." class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Unique Company Code *</label>
                    <input type="text" name="company_code" required placeholder="e.g. APEX-INFRA" class="w-full px-3 py-2 uppercase rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tax ID / GST Number</label>
                    <input type="text" name="tax_id" placeholder="e.g. 27AAAAA0000A1Z5" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Customer Group Tier</label>
                    <select name="customer_group_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Direct Account (No Group) --</option>
                        @foreach($customerGroups as $cg)
                            <option value="{{ $cg->id }}">{{ $cg->name }} ({{ $cg->discount_percentage }}% off)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Initial Credit Limit (₹) *</label>
                    <input type="number" step="0.01" min="0" name="credit_limit_in_rupees" required value="100000" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Terms (Days) *</label>
                    <input type="number" min="0" max="180" name="payment_terms_days" required value="30" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-3 flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-xs">Register Corporate Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters & Status Navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.b2b.companies') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $currentStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800' }}">
                All Companies ({{ $companies->total() }})
            </a>
            <a href="{{ route('admin.b2b.companies', ['status' => 'active']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $currentStatus === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800' }}">
                Active
            </a>
            <a href="{{ route('admin.b2b.companies', ['status' => 'suspended']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $currentStatus === 'suspended' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800' }}">
                Suspended
            </a>
        </div>

        <form method="GET" action="{{ route('admin.b2b.companies') }}" class="flex items-center gap-2 text-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, code, tax ID..." class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold">Filter</button>
        </form>
    </div>

    <!-- Companies Table -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Company &amp; Code</th>
                        <th class="p-4">Customer Group</th>
                        <th class="p-4">Credit Line &amp; Available</th>
                        <th class="p-4">Payment Terms</th>
                        <th class="p-4">Users / POs</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 font-medium">
                    @forelse($companies as $company)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="p-4">
                                <a href="{{ route('admin.b2b.companies.show', $company->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 text-sm">
                                    {{ $company->name }}
                                </a>
                                <div class="flex items-center gap-2 mt-0.5 font-mono text-[11px] text-slate-400 dark:text-slate-500">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold">{{ $company->company_code }}</span>
                                    @if($company->tax_id)
                                        <span>GST/Tax: {{ $company->tax_id }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4">
                                @if($company->customerGroup)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">
                                        {{ $company->customerGroup->name }} ({{ $company->customerGroup->discount_percentage }}%)
                                    </span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 font-italic">Standard Tier</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($company->credit_balance / 100, 2) }}</span>
                                    <span class="text-slate-400 dark:text-slate-500 text-[11px]">/ ₹{{ number_format($company->credit_limit / 100, 2) }}</span>
                                </div>
                                <div class="w-32 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                    @php
                                        $percent = $company->credit_limit > 0 ? min(100, max(0, round(($company->credit_balance / $company->credit_limit) * 100))) : 0;
                                    @endphp
                                    <div class="h-full {{ $percent < 20 ? 'bg-rose-500' : ($percent < 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $percent }}%"></div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Net {{ $company->payment_terms_days }} Days</span>
                            </td>
                            <td class="p-4 font-mono text-slate-600 dark:text-slate-400">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $company->company_users_count }}</span> users &bull;
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $company->purchase_orders_count }}</span> POs
                            </td>
                            <td class="p-4">
                                @if($company->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @elseif($company->status === 'suspended')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> {{ ucfirst($company->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <a href="{{ route('admin.b2b.companies.show', $company->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                No corporate accounts registered yet. Use the button above to onboard a B2B company.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $companies->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
