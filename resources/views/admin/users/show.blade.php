@extends('layouts.admin')

@section('title', "User: {$user->name}")
@section('header_title', $user->name)
@section('header_subtitle', $user->email)

@section('content')
<div class="max-w-4xl space-y-6">

    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:underline">
        ← Back to Users
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Profile Card --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
                <div class="flex flex-col items-center text-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-2xl uppercase">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                        @if($user->phone)
                            <div class="text-xs text-slate-500">{{ $user->phone }}</div>
                        @endif
                    </div>
                    @php
                        $statusBadge = match($user->status) {
                            'active'    => 'bg-emerald-50 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border-emerald-200',
                            'suspended' => 'bg-amber-50 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 border-amber-200',
                            'banned'    => 'bg-rose-50 dark:bg-rose-900/60 text-rose-700 dark:text-rose-400 border-rose-200',
                            default     => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200',
                        };
                    @endphp
                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide border {{ $statusBadge }}">{{ $user->status }}</span>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-900 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Joined</span><span>{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Email Verified</span>
                        <span>{{ $user->email_verified_at ? '✓ Yes' : '✗ No' }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Total Orders</span><span class="font-semibold text-slate-900 dark:text-white">{{ $user->orders->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Status Update --}}
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Update Status</h3>
                <form method="POST" action="{{ route('admin.users.status', $user->id) }}" class="flex gap-2">
                    @csrf
                    <select name="status" class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach(['active','suspended','banned'] as $s)
                            <option value="{{ $s }}" @selected($user->status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-colors">Update</button>
                </form>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Assign Roles --}}
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs transition-colors">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-4">Assigned Roles</h3>
                <form method="POST" action="{{ route('admin.users.roles', $user->id) }}">
                    @csrf
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-4">
                        @foreach($allRoles as $role)
                            <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900/60 cursor-pointer transition">
                                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                    @checked($user->roles->contains('id', $role->id))
                                    class="rounded border-slate-400 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="text-xs font-semibold text-slate-900 dark:text-white">{{ $role->name }}</div>
                                    @if($role->description)
                                        <div class="text-[10px] text-slate-400 leading-tight">{{ Str::limit($role->description, 40) }}</div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                        Save Role Assignments
                    </button>
                </form>
            </div>

            {{-- Recent Orders --}}
            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Recent Orders</h3>
                </div>
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Order #</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        @forelse($user->orders->take(5) as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">₹{{ number_format($order->grand_total / 100, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="capitalize font-semibold text-slate-600 dark:text-slate-300">{{ $order->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No orders yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
