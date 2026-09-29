@extends('layouts.admin')

@section('title', 'Attribute Definitions')
@section('header_title', 'Attribute Definitions')
@section('header_subtitle', 'Define technical specification fields used across products and variants')

@section('content')
<div class="space-y-6">

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        <form method="GET" action="{{ route('admin.catalog.attributes.index') }}" class="flex flex-wrap gap-2 items-center">
            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search attributes…"
                    class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg pl-8 pr-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none w-48 placeholder:text-slate-400">
            </div>
            <select name="type" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Types</option>
                @foreach(['text','number','select','multi_select','boolean','measurement'] as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                @endforeach
            </select>
            @if(request('search') || request('type'))
                <a href="{{ route('admin.catalog.attributes.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-lg hover:bg-slate-200 transition">Reset</a>
            @endif
            <button type="submit" class="px-3 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-lg hover:bg-slate-700 transition">Search</button>
        </form>
        <a href="{{ route('admin.catalog.attributes.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Attribute
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Info Banner --}}
    <div class="bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800/50 rounded-xl px-4 py-3 text-xs text-violet-700 dark:text-violet-300 flex items-start gap-2.5">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><strong>Attribute Definitions</strong> are the technical specification fields (e.g. <em>Compressive Strength</em>, <em>Thickness</em>, <em>Color</em>) that appear in product spec sheets and power catalog filtering. Assign values per-product in the product edit form.</span>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Attribute</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Type</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Values</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Filterable</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Variant Attr.</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Order</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                @forelse($attributes as $attr)
                @php
                    $typeColors = [
                        'text'         => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
                        'number'       => 'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                        'select'       => 'bg-violet-50 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
                        'multi_select' => 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
                        'boolean'      => 'bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                        'measurement'  => 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
                    ];
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors group">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-900 dark:text-white text-sm">{{ $attr->name }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <code class="text-[11px] font-mono bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-slate-600 dark:text-slate-400">{{ $attr->code }}</code>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $typeColors[$attr->type] ?? $typeColors['text'] }}">
                            {{ str_replace('_', ' ', $attr->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if(in_array($attr->type, ['select', 'multi_select']))
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ $attr->values_count }}</span>
                        @else
                            <span class="text-slate-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($attr->is_filterable)
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        @else
                            <span class="text-slate-300 dark:text-slate-600 text-sm">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($attr->is_variant_attribute)
                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 uppercase tracking-wide border border-indigo-200 dark:border-indigo-800/50">Variant</span>
                        @else
                            <span class="text-slate-300 dark:text-slate-600 text-sm">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $attr->sort_order }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('admin.catalog.attributes.edit', $attr->id) }}" title="Edit"
                                class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.catalog.attributes.destroy', $attr->id) }}" onsubmit="return confirm('Delete attribute {{ addslashes($attr->name) }} and all its values? This will also remove it from all products.')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <p class="text-sm text-slate-500 dark:text-slate-400">No attribute definitions yet.</p>
                            <a href="{{ route('admin.catalog.attributes.create') }}" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Create the first attribute →</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attributes->hasPages())
        <div class="flex justify-center">{{ $attributes->links() }}</div>
    @endif
</div>
@endsection
