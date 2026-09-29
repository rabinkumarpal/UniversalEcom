@extends('layouts.admin')

@section('title', 'Promotions')
@section('header_title', 'Promotion Engine')
@section('header_subtitle', 'Create and manage all discount types — percentage, bundle, mix & match, buy-X-get-Y, spending goals, free shipping and more')

@section('content')
<div class="space-y-6">

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Status Quick Filter Tabs --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <a href="{{ route('admin.promotions.index', request()->except('status')) }}"
           class="px-3.5 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 {{ !request('status') ? 'bg-slate-900 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
            <span>All Promotions</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ !request('status') ? 'bg-slate-800 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $totalPromotionsCount ?? $promotions->total() }}</span>
        </a>
        @foreach(['active' => 'Active', 'paused' => 'Paused', 'draft' => 'Draft', 'archived' => 'Archived'] as $sKey => $sLabel)
            @php $count = $statusCounts[$sKey] ?? 0; @endphp
            <a href="{{ route('admin.promotions.index', array_merge(request()->except('status'), ['status' => $sKey])) }}"
               class="px-3.5 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 {{ request('status') === $sKey ? 'bg-slate-900 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
                <span>{{ $sLabel }}</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ request('status') === $sKey ? 'bg-slate-800 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        <form method="GET" action="{{ route('admin.promotions.index') }}" class="flex gap-2 flex-wrap flex-1 max-w-2xl">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, slug, or coupon code..."
                   class="flex-1 min-w-[200px] bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">

            <select name="type" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All discount types</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ str_replace('_', ' ', ucfirst($t)) }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs">
                Filter
            </button>

            @if(request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route('admin.promotions.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    Reset
                </a>
            @endif
        </form>

        <a href="{{ route('admin.promotions.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Promotion
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Promotion Name &amp; Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Active Window</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Redemptions</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Stackable</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                @forelse($promotions as $promo)
                @php
                    $typeBadge = match($promo->type) {
                        'percentage'        => 'bg-indigo-50 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700/50',
                        'fixed'             => 'bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700/50',
                        'bogo'              => 'bg-violet-50 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-700/50',
                        'bundle'            => 'bg-amber-50 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700/50',
                        'mix_match'         => 'bg-pink-50 dark:bg-pink-900/60 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-700/50',
                        'buy_x_get_y_bundle'=> 'bg-rose-50 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-700/50',
                        'countdown'         => 'bg-orange-50 dark:bg-orange-900/60 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700/50',
                        'spending_goal'     => 'bg-teal-50 dark:bg-teal-900/60 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-700/50',
                        'free_shipping'     => 'bg-emerald-50 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700/50',
                        'stock_scarcity'    => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                        default             => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    };
                    $statusBadge = match($promo->status) {
                        'active'   => 'bg-emerald-50 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50',
                        'draft'    => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                        'paused'   => 'bg-amber-50 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50',
                        'archived' => 'bg-rose-50 dark:bg-rose-900/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50',
                        default    => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                    };
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900 dark:text-white text-sm">{{ $promo->name }}</div>
                        @if($promo->code)
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-xs text-indigo-700 dark:text-indigo-400 font-mono font-bold bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800">{{ $promo->code }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $promo->code }}'); alert('Coupon code {{ $promo->code }} copied!');" class="text-[10px] text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer">
                                    Copy
                                </button>
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded text-xs border font-mono {{ $typeBadge }}">
                            {{ str_replace('_', ' ', $promo->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge }}">
                            {{ ucfirst($promo->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs font-mono">{{ $promo->priority }}</td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs">
                        @if($promo->starts_at || $promo->ends_at)
                            <div>{{ $promo->starts_at?->format('d M Y') ?? 'Immediate' }}</div>
                            <div class="text-slate-400 dark:text-slate-600">→ {{ $promo->ends_at?->format('d M Y') ?? 'Ongoing' }}</div>
                        @else
                            <span class="text-slate-400 dark:text-slate-600">Always Active</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-xs font-mono">
                        {{ $promo->usages_count }}
                        @if($promo->usage_limit)
                            <span class="text-slate-400 dark:text-slate-600">/ {{ $promo->usage_limit }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if($promo->stackable)
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Yes</span>
                        @else
                            <span class="text-slate-400 dark:text-slate-600">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <!-- Quick Status Toggle -->
                            @if($promo->status === 'active')
                                <form method="POST" action="{{ route('admin.promotions.toggle-status', $promo->id) }}">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-bold border border-amber-200 transition">
                                        Pause
                                    </button>
                                </form>
                            @elseif(in_array($promo->status, ['paused', 'draft']))
                                <form method="POST" action="{{ route('admin.promotions.toggle-status', $promo->id) }}">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold border border-emerald-200 transition">
                                        Activate
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('admin.promotions.edit', $promo->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold">Edit</a>

                            @if($promo->status !== 'archived')
                                <form method="POST" action="{{ route('admin.promotions.destroy', $promo->id) }}" onsubmit="return confirm('Archive this promotion?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 dark:text-rose-400 hover:underline text-xs font-medium">Archive</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-slate-500 text-sm">
                        No promotions found matching the selected filters.
                        <a href="{{ route('admin.promotions.create') }}" class="text-indigo-600 dark:text-indigo-400 underline ml-1">Create the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($promotions->hasPages())
        <div class="flex justify-center">{{ $promotions->links() }}</div>
    @endif

</div>
@endsection
