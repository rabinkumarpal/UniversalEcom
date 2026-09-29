@extends('layouts.admin')

@section('title', $brand ? 'Edit Brand' : 'New Brand')
@section('header_title', $brand ? 'Edit Brand' : 'Add New Brand')
@section('header_subtitle', $brand ? "Editing: {$brand->name}" : 'Create a new product brand')

@section('content')
<div class="max-w-2xl">
    <form method="POST"
        action="{{ $brand ? route('admin.catalog.brands.update', $brand->id) : route('admin.catalog.brands.store') }}"
        class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl divide-y divide-slate-100 dark:divide-slate-900 shadow-xs transition-colors">
        @csrf
        @if($brand) @method('PUT') @endif

        <div class="p-6 space-y-5">
            <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Brand Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Brand Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $brand?->name) }}" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="active" @selected(old('status', $brand?->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $brand?->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Logo URL</label>
                    <input type="url" name="logo_url" value="{{ old('logo_url', $brand?->logo_url) }}" placeholder="https://…"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('description', $brand?->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="p-6 flex items-center justify-between gap-4 bg-slate-50 dark:bg-slate-900/40">
            <a href="{{ route('admin.catalog.brands.index') }}" class="text-xs text-slate-500 dark:text-slate-400 hover:underline">← Back to Brands</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                {{ $brand ? 'Save Changes' : 'Create Brand' }}
            </button>
        </div>
    </form>
</div>
@endsection
