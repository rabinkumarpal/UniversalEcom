@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('header_title', 'Role Manager')
@section('header_subtitle', 'Create roles, assign permissions, and manage user access control')

@section('content')
<div class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Role
        </a>
    </div>

    <div class="space-y-4">
        @forelse($roles as $role)
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs transition-colors">
            <div class="p-5 flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">{{ $role->name }}</h3>
                        <span class="font-mono text-[10px] px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded">{{ $role->slug }}</span>
                        <span class="text-[11px] px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/50 rounded font-semibold">{{ $role->users_count }} user{{ $role->users_count !== 1 ? 's' : '' }}</span>
                    </div>
                    @if($role->description)
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $role->description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-1 mt-3">
                        @forelse($role->permissions as $perm)
                            <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50 rounded">{{ $perm->name }}</span>
                        @empty
                            <span class="text-[11px] text-slate-400 italic">No permissions assigned</span>
                        @endforelse
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">Edit</a>
                    <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" onsubmit="return confirm('Delete role {{ addslashes($role->name) }}? Users assigned this role will lose it.')">
                        @csrf @method('DELETE')
                        <button class="text-rose-600 dark:text-rose-400 hover:underline text-xs font-medium">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center shadow-xs">
            <p class="text-slate-500 text-sm">No roles found. <a href="{{ route('admin.roles.create') }}" class="text-indigo-600 dark:text-indigo-400 underline">Create the first role →</a></p>
        </div>
        @endforelse
    </div>

</div>
@endsection
