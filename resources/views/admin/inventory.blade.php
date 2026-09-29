@extends('layouts.admin')

@section('title', 'Warehouse Inventory')
@section('header_title', 'Warehouse Inventory & Stock Ledger')
@section('header_subtitle', 'Pessimistic row-locked physical stock tracking, inter-depot transfers, and double-entry audit movements')

@section('content')
<div class="space-y-6">
    <!-- Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold shadow-xs">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold shadow-xs">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Inventory KPI Overview Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Managed SKUs -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Tracked SKUs</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ number_format($totalSkus) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Physical Warehouse SKUs</div>
        </div>

        <!-- Total Units On Hand -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Physical Units</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ number_format($totalOnHand) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">On Hand Across Depots</div>
        </div>

        <!-- Low Stock Alert -->
        <a href="{{ route('admin.inventory', ['status' => 'low_stock']) }}"
           class="p-5 rounded-2xl bg-white dark:bg-slate-950 border {{ $lowStockCount > 0 ? 'border-amber-300 dark:border-amber-800/80 bg-amber-50/20' : 'border-slate-200 dark:border-slate-800' }} shadow-xs hover:border-amber-400 transition block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Low Stock Warnings</span>
                @if($lowStockCount > 0)
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono mt-1">{{ number_format($lowStockCount) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Below Reorder Threshold</div>
        </a>

        <!-- Out of Stock Alert -->
        <a href="{{ route('admin.inventory', ['status' => 'out_of_stock']) }}"
           class="p-5 rounded-2xl bg-white dark:bg-slate-950 border {{ $outOfStockCount > 0 ? 'border-rose-300 dark:border-rose-800/80 bg-rose-50/20' : 'border-slate-200 dark:border-slate-800' }} shadow-xs hover:border-rose-400 transition block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Out of Stock</span>
                @if($outOfStockCount > 0)
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1">{{ number_format($outOfStockCount) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Immediate Replenishment Needed</div>
        </a>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs transition-colors">
        <form method="GET" action="{{ route('admin.inventory') }}" class="flex flex-wrap items-center gap-3">
            <!-- Search Query -->
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by SKU, product name, or variant..."
                       class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
            </div>

            <!-- Warehouse Depot Filter -->
            <div class="w-48">
                <select name="warehouse_id"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none font-medium">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }} ({{ $wh->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="w-44">
                <select name="status"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 focus:outline-none font-medium">
                    <option value="all" {{ ($currentStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Stock Levels</option>
                    <option value="low_stock" {{ ($currentStatus ?? '') === 'low_stock' ? 'selected' : '' }}>Low Stock Alerts</option>
                    <option value="out_of_stock" {{ ($currentStatus ?? '') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    <option value="healthy" {{ ($currentStatus ?? '') === 'healthy' ? 'selected' : '' }}>Healthy Stock</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Filter
                </button>
                @if(request()->hasAny(['q', 'warehouse_id', 'status']))
                    <a href="{{ route('admin.inventory') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs transition-colors">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Warehouse Inventory Items</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Showing <strong class="text-slate-900 dark:text-white">{{ $inventory->total() }}</strong> records matching filters</p>
            </div>
            <span class="text-xs text-indigo-600 dark:text-indigo-400 font-mono">Row-locking concurrency active</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">SKU / Product</th>
                        <th class="px-5 py-3.5">Warehouse Depot</th>
                        <th class="px-5 py-3.5 text-center">On Hand</th>
                        <th class="px-5 py-3.5 text-center">Reserved</th>
                        <th class="px-5 py-3.5 text-center">Available</th>
                        <th class="px-5 py-3.5 text-center">Reorder Threshold</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5">Inventory Operations &amp; Ledger</th>
                    </tr>
                </thead>
                @forelse($inventory as $item)
                    @php
                        $isOut = $item->available <= 0;
                        $isLow = ! $isOut && ($item->available <= $item->reorder_level);
                        $otherWarehouses = $warehouses->where('id', '!=', $item->warehouse_id);
                    @endphp
                    <tbody class="border-b border-slate-200 dark:border-slate-800/60 divide-y divide-slate-100 dark:divide-slate-800/40"
                           x-data="{ mode: 'adjust', showHistory: false }">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-900/40 transition {{ $isOut ? 'bg-rose-50/20 dark:bg-rose-950/10' : ($isLow ? 'bg-amber-50/20 dark:bg-amber-950/10' : '') }}">
                            <!-- SKU / Product -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $item->variant?->sku ?? 'SKU-'.$item->product_variant_id }}</div>
                                <div class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $item->variant?->product?->name ?? 'Product' }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $item->variant?->name }}</div>
                            </td>

                            <!-- Warehouse -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $item->warehouse?->name ?? 'Default Warehouse' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $item->warehouse?->code }} &bull; {{ $item->warehouse?->address ?? 'Main Yard' }}</div>
                            </td>

                            <!-- Physical On Hand -->
                            <td class="px-5 py-4 align-top text-center font-mono font-bold text-slate-900 dark:text-slate-200">
                                {{ number_format($item->on_hand) }}
                            </td>

                            <!-- Reserved -->
                            <td class="px-5 py-4 align-top text-center font-mono text-amber-600 dark:text-amber-400">
                                {{ number_format($item->reserved) }}
                            </td>

                            <!-- Available -->
                            <td class="px-5 py-4 align-top text-center font-mono font-bold {{ $item->available <= $item->reorder_level ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ number_format($item->available) }}
                            </td>

                            <!-- Reorder Level -->
                            <td class="px-5 py-4 align-top text-center font-mono text-slate-500 dark:text-slate-400">
                                {{ number_format($item->reorder_level) }}
                            </td>

                            <!-- Status Badge -->
                            <td class="px-5 py-4 align-top text-center">
                                @if($isOut)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50">Out of Stock</span>
                                @elseif($isLow)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50">Low Stock</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">Healthy</span>
                                @endif
                            </td>

                            <!-- Operations Column -->
                            <td class="px-5 py-4 align-top min-w-72">
                                <!-- Mode Selector Pills -->
                                <div class="flex items-center justify-between gap-1 pb-2 border-b border-slate-100 dark:border-slate-800/60 mb-2">
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="mode = 'adjust'"
                                                :class="mode === 'adjust' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold transition">
                                            Adjust / Threshold
                                        </button>
                                        @if($otherWarehouses->isNotEmpty())
                                            <button type="button" @click="mode = 'transfer'"
                                                    :class="mode === 'transfer' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold transition">
                                                Transfer Depot
                                            </button>
                                        @endif
                                    </div>
                                    <button type="button" @click="showHistory = !showHistory"
                                            class="text-[10px] font-semibold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-0.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="showHistory ? 'Hide Ledger' : 'Movements ({{ $item->movements->count() }})'"></span>
                                    </button>
                                </div>

                                <!-- Form 1: Stock & Threshold Adjustment -->
                                <div x-show="mode === 'adjust'">
                                    <form action="{{ route('admin.inventory.adjust', $item->id) }}" method="POST" class="space-y-1.5">
                                        @csrf
                                        <div class="flex gap-1.5">
                                            <div class="w-20">
                                                <input type="number" name="quantity_change" placeholder="+/- Qty" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] text-slate-900 dark:text-slate-200 px-2 py-1 text-center font-mono focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-indigo-500">
                                            </div>
                                            <div class="w-20">
                                                <input type="number" name="reorder_level" value="{{ $item->reorder_level }}" min="0" placeholder="Min Alert" title="Reorder Threshold" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] text-slate-900 dark:text-slate-200 px-2 py-1 text-center font-mono focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-indigo-500">
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" name="reason" placeholder="Reason (Audit, Inbound)" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-slate-200 px-2 py-1 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-indigo-500">
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-transparent rounded-lg text-[10px] font-bold transition">
                                            Save Adjustment &amp; Threshold
                                        </button>
                                    </form>
                                </div>

                                <!-- Form 2: Inter-Depot Stock Transfer -->
                                @if($otherWarehouses->isNotEmpty())
                                    <div x-show="mode === 'transfer'" x-cloak>
                                        @if($item->available <= 0)
                                            <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-lg text-[10px] text-rose-700 dark:text-rose-300">
                                                Zero available stock to transfer. Replenish or adjust first.
                                            </div>
                                        @else
                                            <form action="{{ route('admin.inventory.transfer', $item->id) }}" method="POST" class="space-y-1.5">
                                                @csrf
                                                <div class="flex gap-1.5">
                                                    <div class="flex-1">
                                                        <select name="destination_warehouse_id" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-slate-200 px-2 py-1 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-indigo-500 font-medium">
                                                            <option value="">Select Destination Depot...</option>
                                                            @foreach($otherWarehouses as $owh)
                                                                <option value="{{ $owh->id }}">{{ $owh->name }} ({{ $owh->code }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="w-20">
                                                        <input type="number" name="quantity" min="1" max="{{ $item->available }}" placeholder="Qty (Max: {{ $item->available }})" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[11px] text-slate-900 dark:text-slate-200 px-2 py-1 text-center font-mono focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-indigo-500">
                                                    </div>
                                                </div>
                                                <div class="flex gap-1.5">
                                                    <input type="text" name="reason" placeholder="Transfer notes (e.g. Site rebalancing)" class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-[10px] text-slate-900 dark:text-slate-200 px-2 py-1 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:border-indigo-500">
                                                    <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-[10px] font-bold transition shadow-xs whitespace-nowrap">
                                                        Transfer
                                                    </button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>

                        <!-- Expandable Recent Movements Ledger Drawer -->
                        <tr x-show="showHistory" x-cloak class="bg-slate-50/80 dark:bg-slate-900/60">
                            <td colspan="8" class="p-4">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            Append-Only Movement Ledger for {{ $item->variant?->sku }} at {{ $item->warehouse?->name }}
                                        </span>
                                        <span class="text-slate-400 font-mono text-[10px]">Pessimistically Locked Movements</span>
                                    </div>

                                    @if($item->movements->isEmpty())
                                        <p class="text-[11px] text-slate-400 italic py-2">No historical movements logged for this SKU depot yet.</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-[11px]">
                                                <thead class="text-slate-400 text-[10px] uppercase border-b border-slate-200 dark:border-slate-800">
                                                    <tr>
                                                        <th class="py-1.5 pr-4">Timestamp</th>
                                                        <th class="py-1.5 px-4">Movement Type</th>
                                                        <th class="py-1.5 px-4 text-center">Quantity Delta</th>
                                                        <th class="py-1.5 px-4">Logged By</th>
                                                        <th class="py-1.5 pl-4">Reason / Reference</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800/40">
                                                    @foreach($item->movements as $m)
                                                        @php
                                                            $badgeStyle = match($m->type) {
                                                                'in' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                                'out' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                                'transfer_in' => 'bg-teal-50 text-teal-700 border-teal-200',
                                                                'transfer_out' => 'bg-violet-50 text-violet-700 border-violet-200',
                                                                'reservation' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                                'release' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                                                default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td class="py-2 pr-4 text-slate-500 font-mono text-[10px]">
                                                                {{ $m->created_at->format('M d, Y h:i A') }}
                                                            </td>
                                                            <td class="py-2 px-4">
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider border {{ $badgeStyle }}">
                                                                    {{ str_replace('_', ' ', $m->type) }}
                                                                </span>
                                                            </td>
                                                            <td class="py-2 px-4 text-center font-mono font-bold {{ $m->quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                                {{ $m->quantity > 0 ? '+'.$m->quantity : $m->quantity }}
                                                            </td>
                                                            <td class="py-2 px-4 text-slate-600 dark:text-slate-300">
                                                                {{ $m->user?->name ?? 'System Process' }}
                                                            </td>
                                                            <td class="py-2 pl-4 text-slate-500 dark:text-slate-400">
                                                                {{ $m->reason ?? 'Inventory adjustment' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                No warehouse inventory records match the current filters.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        @if($inventory->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $inventory->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
