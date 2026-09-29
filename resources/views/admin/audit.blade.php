@extends('layouts.admin')

@section('title', 'Audit Log')
@section('header_title', 'Security Audit Log')
@section('header_subtitle', 'Complete record of security-sensitive actions — refunds, stock adjustments, order transitions, settings changes')

@section('content')
<div class="space-y-6">

    <!-- Audit Stat Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Audit Events</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ number_format($stats['total_events']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Authoritative audit ledger movements</div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Today's Activity</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1">{{ number_format($stats['today_events']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Events logged today</div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Unique Actors</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">{{ number_format($stats['unique_actors']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Active operators &amp; admins</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.audit') }}" class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Search Actor / IP</label>
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Search name, email, IP..."
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Action</label>
                <select name="action" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" @selected(request('action') === $act)>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Entity Type</label>
                <input type="text" name="entity_type" value="{{ request('entity_type') }}"
                    placeholder="e.g. Order, InventoryItem"
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors shadow-xs">
                Apply Filters
            </button>
            <a href="{{ route('admin.audit') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-semibold rounded-lg transition-colors">
                Reset
            </a>
        </div>
    </form>

    {{-- Stats --}}
    <div class="text-xs text-slate-500">
        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} entries
    </div>

    {{-- Log Table --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Timestamp</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Actor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Entity</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">IP</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Changes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60" x-data>
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs whitespace-nowrap font-mono">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-xs">
                        @if($log->user)
                            <div class="font-medium text-slate-900 dark:text-white">{{ $log->user->name }}</div>
                            <div class="text-slate-500 text-[11px]">{{ $log->user->email }}</div>
                        @else
                            <span class="text-slate-400 italic">System</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $badge = match(true) {
                                str_starts_with($log->action, 'refund')   => 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-700/50',
                                str_starts_with($log->action, 'order')    => 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700/50',
                                str_starts_with($log->action, 'stock')    => 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700/50',
                                str_starts_with($log->action, 'setting')  => 'bg-purple-50 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700/50',
                                str_starts_with($log->action, 'product')  => 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700/50',
                                str_starts_with($log->action, 'role')     => 'bg-orange-50 dark:bg-orange-950 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700/50',
                                default                                    => 'bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-gray-300 border-slate-200 dark:border-gray-700',
                            };
                        @endphp
                        <span class="inline-block px-2 py-0.5 rounded text-xs border font-mono {{ $badge }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs">
                        <div class="font-mono font-medium text-slate-900 dark:text-slate-300">{{ class_basename($log->entity_type) }}</div>
                        <div class="text-slate-400 dark:text-slate-600 text-[11px]">#{{ $log->entity_id }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs font-mono">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if($log->old_values || $log->new_values)
                        <button
                            @click="$el.closest('tr').nextElementSibling.classList.toggle('hidden')"
                            class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium transition-colors">
                            View diff
                        </button>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
                @if($log->old_values || $log->new_values)
                <tr class="hidden bg-slate-50 dark:bg-slate-900/40">
                    <td colspan="6" class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($log->old_values)
                            <div>
                                <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold mb-1">Before</p>
                                <pre class="text-xs text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-800 overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                            @if($log->new_values)
                            <div>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mb-1">After</p>
                                <pre class="text-xs text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 rounded-lg p-3 border border-slate-200 dark:border-slate-800 overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-500 text-sm">
                        No audit log entries match your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="flex justify-center">
        {{ $logs->links() }}
    </div>
    @endif

</div>
@endsection
