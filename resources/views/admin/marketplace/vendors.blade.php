@extends('layouts.admin')

@section('title', 'Marketplace Vendors — Admin')
@section('header_title', 'Marketplace Vendors & Onboarding')
@section('header_subtitle', 'Approve merchant applications, configure commissions, and manage suspensions')

@section('content')
<div class="space-y-6">
    <!-- Search and Filter Bar -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Registered Multi-Tenant Merchants</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control vendor authorization and commission splits across the universal platform.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.marketplace.payouts') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition">
                    View Payouts Ledger &rarr;
                </a>
            </div>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('admin.marketplace.vendors') }}" class="flex flex-col sm:flex-row items-center gap-3 pt-2">
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search vendor name, email, slug..."
                       class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-xs transition">
                Search
            </button>
            @if(!empty($search))
                <a href="{{ route('admin.marketplace.vendors', ['tab' => $currentTab]) }}" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold underline">
                    Clear Search
                </a>
            @endif
        </form>

        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('admin.marketplace.vendors', array_filter(['tab' => 'all', 'search' => $search])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentTab === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>All Merchants</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentTab === 'all' ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['all'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.marketplace.vendors', array_filter(['tab' => 'pending_approval', 'search' => $search])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentTab === 'pending_approval' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Pending Review</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentTab === 'pending_approval' ? 'bg-amber-500 text-white' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">{{ $statusCounts['pending_approval'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.marketplace.vendors', array_filter(['tab' => 'approved', 'search' => $search])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentTab === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Active &amp; Approved</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentTab === 'approved' ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['approved'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.marketplace.vendors', array_filter(['tab' => 'suspended', 'search' => $search])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $currentTab === 'suspended' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400' }}">
                <span>Suspended</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $currentTab === 'suspended' ? 'bg-rose-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">{{ $statusCounts['suspended'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider">
                    <tr>
                        <th class="p-4">Vendor &amp; Legal Name</th>
                        <th class="p-4">Contact</th>
                        <th class="p-4">Offers</th>
                        <th class="p-4">Orders</th>
                        <th class="p-4">Commission %</th>
                        <th class="p-4">Account Status</th>
                        <th class="p-4 text-right">Approval &amp; Policy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 font-medium">
                    @forelse($vendors as $vendor)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                            <td class="p-4">
                                <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $vendor->display_name }}</p>
                                <p class="text-slate-500 dark:text-slate-400 text-[11px]">{{ $vendor->legal_name }}</p>
                                <span class="font-mono text-[10px] text-slate-400 dark:text-slate-500">slug: {{ $vendor->slug }}</span>
                            </td>
                            <td class="p-4">
                                <p class="text-slate-700 dark:text-slate-300">{{ $vendor->email }}</p>
                                <p class="text-slate-500 text-[11px]">{{ $vendor->phone ?? 'No phone' }}</p>
                            </td>
                            <td class="p-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $vendor->offers_count }}
                            </td>
                            <td class="p-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $vendor->vendor_orders_count }}
                            </td>
                            <td class="p-4">
                                <span class="font-bold font-mono text-amber-600 dark:text-amber-400 text-sm">{{ $vendor->commission_rate_percentage }}%</span>
                            </td>
                            <td class="p-4">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        {{ $vendor->approval_status === 'approved' ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : '' }}
                                        {{ $vendor->approval_status === 'pending' ? 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : '' }}
                                        {{ $vendor->approval_status === 'rejected' ? 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : '' }}">
                                        {{ $vendor->approval_status }}
                                    </span>
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                            {{ $vendor->status === 'active' ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800' : '' }}
                                            {{ $vendor->status === 'suspended' ? 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : '' }}
                                            {{ $vendor->status === 'pending' ? 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' : '' }}">
                                            {{ $vendor->status }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    @if($vendor->approval_status === 'pending')
                                        <div class="flex items-center gap-1.5">
                                            <form method="POST" action="{{ route('admin.marketplace.vendors.status', $vendor->id) }}">
                                                @csrf
                                                <input type="hidden" name="quick_action" value="approve">
                                                <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-xs shadow-xs transition">
                                                    1-Click Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.marketplace.vendors.status', $vendor->id) }}">
                                                @csrf
                                                <input type="hidden" name="quick_action" value="reject">
                                                <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800 rounded-lg font-bold text-xs transition">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('admin.marketplace.vendors.status', $vendor->id) }}" class="inline-flex flex-col sm:flex-row items-end sm:items-center gap-2">
                                        @csrf
                                        <div class="flex items-center gap-1.5">
                                            <input type="number" step="0.5" min="0" max="100" name="commission_rate_percentage" value="{{ $vendor->commission_rate_percentage }}"
                                                   title="Commission Rate %" class="w-16 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-slate-900 dark:text-white text-xs font-mono text-right focus:bg-white dark:focus:bg-slate-900">
                                            <span class="text-slate-500 dark:text-slate-400 text-xs">%</span>
                                        </div>

                                        <select name="approval_status" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900">
                                            <option value="pending" {{ $vendor->approval_status === 'pending' ? 'selected' : '' }}>Pending Appr</option>
                                            <option value="approved" {{ $vendor->approval_status === 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ $vendor->approval_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>

                                        <select name="status" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-slate-900 dark:text-white text-xs focus:bg-white dark:focus:bg-slate-900">
                                            <option value="pending" {{ $vendor->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="active" {{ $vendor->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="suspended" {{ $vendor->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                        </select>

                                        <button type="submit" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded font-bold text-xs shadow-xs transition">
                                            Update
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No vendors currently onboarded in the system.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vendors->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
