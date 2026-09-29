@extends('layouts.admin')

@section('title', 'Brands')
@section('header_title', 'Brand Management')
@section('header_subtitle', 'Create and manage product brands')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        <form method="GET" action="{{ route('admin.catalog.brands.index') }}" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search brands…"
                class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none w-52">
            <select name="status" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            @if(request('search') || request('status'))
                <a href="{{ route('admin.catalog.brands.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-lg hover:bg-slate-200 transition">Reset</a>
            @endif
            <button type="submit" class="px-3 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-lg hover:bg-slate-700 transition">Search</button>
        </form>
        <a href="{{ route('admin.catalog.brands.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Brand
        </a>
    </div>

    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Brand</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Products</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                @forelse($brands as $brand)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if($brand->logo_url)
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="w-8 h-8 rounded object-contain bg-white border border-slate-200">
                            @else
                                <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">{{ substr($brand->name, 0, 2) }}</div>
                            @endif
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-white text-sm">{{ $brand->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $brand->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300 font-semibold">{{ $brand->products_count }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border
                            {{ $brand->status === 'active'
                                ? 'bg-emerald-50 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' }}">
                            {{ $brand->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.catalog.brands.edit', $brand->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('admin.catalog.brands.destroy', $brand->id) }}" onsubmit="return confirm('Delete brand {{ addslashes($brand->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 dark:text-rose-400 hover:underline text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-slate-500 text-sm">
                        No brands found. <a href="{{ route('admin.catalog.brands.create') }}" class="text-indigo-600 underline ml-1">Create one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($brands->hasPages())
        <div class="flex justify-center">{{ $brands->links() }}</div>
    @endif
</div>
@endsection
