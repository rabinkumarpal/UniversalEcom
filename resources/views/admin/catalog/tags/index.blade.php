@extends('layouts.admin')

@section('title', 'Tags')
@section('header_title', 'Product Tags')
@section('header_subtitle', 'Create and manage product tags for filtering and grouping')

@section('content')
<div class="space-y-6">

    {{-- Create Tag Inline Form --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
        <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-4">Add New Tag</h3>
        <form method="POST" action="{{ route('admin.catalog.tags.store') }}" class="flex gap-3">
            @csrf
            <input type="text" name="name" placeholder="Tag name e.g. New Arrival, On Sale…" required
                class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Tag
            </button>
        </form>
    </div>

    {{-- Search + Tags Table --}}
    <div class="space-y-4">
        <form method="GET" action="{{ route('admin.catalog.tags.index') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tags…"
                class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none w-52">
            <button type="submit" class="px-3 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-lg hover:bg-slate-700 transition">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.catalog.tags.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-lg hover:bg-slate-200 transition">Reset</a>
            @endif
        </form>

        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Tag Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Slug</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Products</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($tags as $tag)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors" id="tag-row-{{ $tag->id }}">
                        <td class="px-4 py-3">
                            <span id="tag-name-{{ $tag->id }}" class="font-semibold text-slate-900 dark:text-white text-sm">{{ $tag->name }}</span>
                            <form id="edit-form-{{ $tag->id }}" method="POST" action="{{ route('admin.catalog.tags.update', $tag->id) }}" class="hidden">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $tag->name }}" required
                                    class="bg-white dark:bg-slate-800 border border-indigo-400 rounded px-2 py-1 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500 focus:outline-none w-40">
                                <button type="submit" class="ml-1 text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Save</button>
                                <button type="button" onclick="cancelEdit({{ $tag->id }})" class="ml-1 text-xs text-slate-500 hover:underline">Cancel</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 font-mono">{{ $tag->slug }}</td>
                        <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300 font-semibold">{{ $tag->products_count }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $tag->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3" id="tag-actions-{{ $tag->id }}">
                                <button type="button" onclick="editTag({{ $tag->id }})" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">Edit</button>
                                <form method="POST" action="{{ route('admin.catalog.tags.destroy', $tag->id) }}" onsubmit="return confirm('Delete tag {{ addslashes($tag->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 dark:text-rose-400 hover:underline text-xs font-medium">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-500 text-sm">No tags found. Create one above.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tags->hasPages())
            <div class="flex justify-center">{{ $tags->links() }}</div>
        @endif
    </div>
</div>

<script>
function editTag(id) {
    document.getElementById('tag-name-' + id).classList.add('hidden');
    document.getElementById('edit-form-' + id).classList.remove('hidden');
    document.getElementById('tag-actions-' + id).classList.add('hidden');
}
function cancelEdit(id) {
    document.getElementById('tag-name-' + id).classList.remove('hidden');
    document.getElementById('edit-form-' + id).classList.add('hidden');
    document.getElementById('tag-actions-' + id).classList.remove('hidden');
}
</script>
@endsection
