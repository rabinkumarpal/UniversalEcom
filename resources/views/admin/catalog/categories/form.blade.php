@extends('layouts.admin')

@section('title', $category ? 'Edit Category' : 'New Category')
@section('header_title', $category ? 'Edit Category' : 'Add New Category')
@section('header_subtitle', $category ? "Editing: {$category->name}" : 'Create a new product category')

@section('content')
<div class="max-w-2xl">
    <form method="POST"
        action="{{ $category ? route('admin.catalog.categories.update', $category->id) : route('admin.catalog.categories.store') }}"
        class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl divide-y divide-slate-100 dark:divide-slate-900 shadow-xs transition-colors">
        @csrf
        @if($category) @method('PUT') @endif

        <div class="p-6 space-y-5">
            <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Category Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $category?->name) }}" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Parent Category</label>
                    <select name="parent_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">None (Root Category)</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $category?->parent_id) == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="active" @selected(old('status', $category?->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $category?->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}" min="0"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('description', $category?->description) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">SEO Title</label>
                    <input type="text" name="seo_title" value="{{ old('seo_title', $category?->seo_title) }}"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">SEO Description</label>
                    <textarea name="seo_description" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('seo_description', $category?->seo_description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="p-6 flex items-center justify-between gap-4 bg-slate-50 dark:bg-slate-900/40">
            <a href="{{ route('admin.catalog.categories.index') }}" class="text-xs text-slate-500 dark:text-slate-400 hover:underline">← Back to Categories</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                {{ $category ? 'Save Changes' : 'Create Category' }}
            </button>
        </div>
    </form>
</div>
@endsection
