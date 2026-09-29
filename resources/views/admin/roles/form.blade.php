@extends('layouts.admin')

@section('title', $role ? 'Edit Role' : 'New Role')
@section('header_title', $role ? 'Edit Role' : 'Create New Role')
@section('header_subtitle', $role ? "Editing: {$role->name}" : 'Define a new role and assign permissions')

@section('content')
<div class="max-w-3xl space-y-6">

    <form method="POST"
        action="{{ $role ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}"
        class="space-y-6">
        @csrf
        @if($role) @method('PUT') @endif

        {{-- Role Info --}}
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl divide-y divide-slate-100 dark:divide-slate-900 shadow-xs transition-colors">
            <div class="p-6 space-y-5">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Role Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $role?->name) }}" required
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    @if(!$role)
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slug <span class="text-rose-500">*</span> <span class="text-slate-400 font-normal">(unique, dash-separated)</span></label>
                        <input type="text" name="slug" value="{{ old('slug', $role?->slug) }}" required placeholder="e.g. content-manager"
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    @else
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slug</label>
                        <div class="px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-mono text-slate-500 dark:text-slate-400">{{ $role->slug }}</div>
                    </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ old('description', $role?->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Permission Matrix --}}
            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Permission Matrix</h3>
                    <button type="button" onclick="toggleAll()" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline" id="toggle-btn">Select All</button>
                </div>
                @foreach($allPermissions as $group => $perms)
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">{{ ucfirst($group) }}</div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($perms as $perm)
                        <label class="flex items-start gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900/60 cursor-pointer transition permission-label">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                @checked(in_array($perm->id, old('permissions', $role?->permissions->pluck('id')->toArray() ?? [])))
                                class="permission-check rounded border-slate-400 text-indigo-600 focus:ring-indigo-500 mt-0.5 shrink-0">
                            <div>
                                <div class="text-xs font-semibold text-slate-900 dark:text-white">{{ $perm->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $perm->slug }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Actions --}}
            <div class="p-6 flex items-center justify-between gap-4 bg-slate-50 dark:bg-slate-900/40">
                <a href="{{ route('admin.roles.index') }}" class="text-xs text-slate-500 dark:text-slate-400 hover:underline">← Back to Roles</a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                    {{ $role ? 'Save Role' : 'Create Role' }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let allSelected = false;
function toggleAll() {
    allSelected = !allSelected;
    document.querySelectorAll('.permission-check').forEach(cb => cb.checked = allSelected);
    document.getElementById('toggle-btn').textContent = allSelected ? 'Deselect All' : 'Select All';
}
</script>
@endsection
