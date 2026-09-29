@extends('layouts.admin')

@section('title', 'Users')
@section('header_title', 'User Management')
@section('header_subtitle', 'Search, manage, and assign roles to all platform users')

@section('content')
<div class="space-y-6">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, phone…"
            class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none w-60">
        <select name="status" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="">All Statuses</option>
            @foreach(['active','suspended','banned'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="role" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="">All Roles</option>
            @foreach($roles as $role)
                <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>
            @endforeach
        </select>
        @if(request('search') || request('status') || request('role'))
            <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-lg hover:bg-slate-200 transition">Reset</a>
        @endif
        <button type="submit" class="px-3 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs rounded-lg hover:bg-slate-700 transition">Search</button>
    </form>

    {{-- Users Table --}}
    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Roles</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Joined</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                @forelse($users as $user)
                @php
                    $statusBadge = match($user->status) {
                        'active'    => 'bg-emerald-50 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/50',
                        'suspended' => 'bg-amber-50 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                        'banned'    => 'bg-rose-50 dark:bg-rose-900/60 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/50',
                        default     => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                    };
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xs uppercase shrink-0">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-white text-sm">{{ $user->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">{{ $user->phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles as $role)
                                <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/50 rounded">{{ $role->name }}</span>
                            @empty
                                <span class="text-[11px] text-slate-400">No role</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border {{ $statusBadge }}">{{ $user->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.users.show', $user->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">Manage</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-500 text-sm">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="flex justify-center">{{ $users->links() }}</div>
    @endif

</div>
@endsection
