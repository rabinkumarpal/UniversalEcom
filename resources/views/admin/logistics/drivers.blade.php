@extends('layouts.admin')

@section('title', 'Fleet & Driver Logistics')
@section('header_title', 'Fleet Logistics & Driver Management')
@section('header_subtitle', 'Manage commercial vehicle fleet, driver shift assignments, and route dispatches')

@section('content')
<div class="space-y-6">
    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Fleet Operational Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Fleet Vehicles</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ $totalDrivers }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Registered drivers</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">On Duty / Active</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">{{ $activeDrivers }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Available for dispatch</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Off Duty</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono mt-1">{{ $offDutyDrivers }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Off shift / resting</div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Suspended</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1">{{ $suspendedDrivers }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Restricted access</div>
        </div>
    </div>

    <!-- Onboard New Driver Card -->
    <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2 mb-4">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span>Onboard Fleet Driver &amp; Assign Vehicle</span>
        </h3>

        <form method="POST" action="{{ route('admin.logistics.drivers.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">User Account</label>
                <select name="user_id" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <option value="">Select User Account...</option>
                    @foreach($eligibleUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Commercial License #</label>
                <input type="text" name="license_number" required placeholder="DL-KA-2024-9988" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Vehicle Type</label>
                <select name="vehicle_type" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <option value="Flatbed Truck">Flatbed Truck (16-24T)</option>
                    <option value="Heavy Tipper">Heavy Tipper (Dump Truck)</option>
                    <option value="Pickup Truck">Pickup Truck (1.5-3.5T)</option>
                    <option value="Trailer">Multi-Axle Trailer (30-40T)</option>
                    <option value="Transit Mixer">Concrete Transit Mixer</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Vehicle Number Plate</label>
                <input type="text" name="vehicle_number" required placeholder="KA-01-AB-1234" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-mono uppercase focus:ring-1 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="status" value="active">
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition shadow-xs cursor-pointer">
                    + Onboard Driver
                </button>
            </div>
        </form>
    </div>

    <!-- Drivers Table -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
        <!-- Table Filter / Search -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('admin.logistics.drivers.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by driver name, license, vehicle..."
                    class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs w-64 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >
                <select name="status" class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Duty Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active / On Duty</option>
                    <option value="off_duty" {{ request('status') === 'off_duty' ? 'selected' : '' }}>Off Duty</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition">
                    Filter
                </button>
            </form>

            <a href="{{ route('driver.dashboard') }}" target="_blank" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1">
                <span>Open Driver Web App</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3 px-4">Driver Profile</th>
                        <th class="py-3 px-4">Commercial Vehicle</th>
                        <th class="py-3 px-4">License #</th>
                        <th class="py-3 px-4">Active Drops</th>
                        <th class="py-3 px-4">Completed PODs</th>
                        <th class="py-3 px-4">Duty Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($drivers as $driver)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $driver->user?->name ?? 'Unlinked User' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $driver->user?->email }} • {{ $driver->user?->phone ?? 'No Phone' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $driver->vehicle_number }}</span>
                                <div class="text-[11px] text-slate-500">{{ $driver->vehicle_type }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300">
                                {{ $driver->license_number }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-amber-600 dark:text-amber-400">
                                {{ $driver->active_shipments_count }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $driver->delivered_shipments_count }}
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $badge = [
                                        'active' => 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                        'off_duty' => 'bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                        'suspended' => 'bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $badge[$driver->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ str_replace('_', ' ', $driver->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form method="POST" action="{{ route('admin.logistics.drivers.status', $driver->id) }}" class="inline-flex items-center gap-1">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="text-[11px] py-1 px-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none cursor-pointer">
                                        <option value="active" {{ $driver->status === 'active' ? 'selected' : '' }}>Set Active</option>
                                        <option value="off_duty" {{ $driver->status === 'off_duty' ? 'selected' : '' }}>Set Off Duty</option>
                                        <option value="suspended" {{ $driver->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                No fleet drivers found matching current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($drivers->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $drivers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
