@extends('layouts.admin')

@section('title', 'Bulk Catalog Import & Export')
@section('header_title', 'Catalog Data Import & Export')
@section('header_subtitle', 'Transactional, preview-first CSV catalog uploads and real-time inventory exports')

@section('content')
<div class="space-y-6">
    <!-- Success & Error Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Download & Export Header Banner -->
    <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs transition-colors">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Standard Catalog Data Templates</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Download the standardized 18-column CSV template with sample data or export active products and variant balances.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.catalog.bulk.sample') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Sample CSV &rarr;
            </a>
            <a href="{{ route('admin.catalog.bulk.export') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Active Catalog CSV &rarr;
            </a>
        </div>
    </div>

    <!-- Upload & Preview Form -->
    <div class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs transition-colors" x-data="{ showSchema: false }">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Step 1: Upload CSV for Validation Preview</h3>
            <button type="button" @click="showSchema = !showSchema" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="showSchema ? 'Hide Column Reference' : 'View 18-Column Specifications'"></span>
            </button>
        </div>

        <!-- Collapsible Column Reference Drawer -->
        <div x-show="showSchema" x-cloak class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3 text-xs">
            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                <span>Standardized 18-Column CSV Schema Reference</span>
                <span class="text-[10px] text-slate-500 font-normal">Excel UTF-8 &amp; UTF-8 BOM compatible</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 text-[11px]">
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">sku <span class="text-rose-500 font-sans">*Required</span></div>
                    <p class="text-slate-500 mt-0.5">Unique item code (e.g. SKU-OPC-53-BAG).</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">product_name <span class="text-rose-500 font-sans">*Required</span></div>
                    <p class="text-slate-500 mt-0.5">Parent product name (e.g. UltraTech Premium Cement).</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">variant_name <span class="text-rose-500 font-sans">*Required</span></div>
                    <p class="text-slate-500 mt-0.5">Packaging or size description (e.g. 50kg HDPE Bag).</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200">brand <span class="text-slate-400 font-sans">(Optional)</span></div>
                    <p class="text-slate-500 mt-0.5">Auto-created if new (defaults to 'Universal').</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200">category &amp; subcategory <span class="text-slate-400 font-sans">(Optional)</span></div>
                    <p class="text-slate-500 mt-0.5">Parent and child category hierarchy.</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">selling_price <span class="text-rose-500 font-sans">*Required</span></div>
                    <p class="text-slate-500 mt-0.5">Price in INR (e.g. 385.00). MRP is optional.</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200">stock &amp; warehouse <span class="text-slate-400 font-sans">(Optional)</span></div>
                    <p class="text-slate-500 mt-0.5">Initial depot stock &amp; location (e.g. 500, South Depot).</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200">tax_class <span class="text-slate-400 font-sans">(Optional)</span></div>
                    <p class="text-slate-500 mt-0.5">GST rate bracket (e.g. GST 18%, GST 12%, GST 28%).</p>
                </div>
                <div class="p-2.5 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                    <div class="font-mono font-bold text-slate-800 dark:text-slate-200">grade, size, color, status <span class="text-slate-400 font-sans">(Optional)</span></div>
                    <p class="text-slate-500 mt-0.5">Specifications &amp; publication state (active/inactive).</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.catalog.bulk.preview') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Select CSV File (Max 5MB)</label>
                <input type="file" name="csv_file" required accept=".csv,text/csv,text/plain"
                       class="block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs shadow-xs transition">
                Validate &amp; Preview Rows &rarr;
            </button>
        </form>
    </div>

    <!-- Preview Results Section (Only when previewed) -->
    @if(isset($preview))
        <div class="space-y-6">
            <!-- Preview KPI Statistics -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">Total Rows</span>
                    <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $preview['total_rows'] }}</div>
                </div>
                <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">Valid Rows</span>
                    <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $preview['valid_count'] }}</div>
                    <span class="text-[10px] text-slate-500">{{ $preview['new_count'] }} New • {{ $preview['update_count'] }} Updates</span>
                </div>
                <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <span class="text-[10px] font-bold uppercase text-rose-600 dark:text-rose-400">Invalid Rows</span>
                    <div class="text-xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $preview['invalid_count'] }}</div>
                </div>
                <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center shadow-xs">
                    @if($preview['valid_count'] > 0)
                        <form method="POST" action="{{ route('admin.catalog.bulk.commit') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-md transition">
                                Commit {{ $preview['valid_count'] }} Valid Rows &rarr;
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-slate-500 italic">No valid rows to commit</span>
                    @endif
                </div>
            </div>

            <!-- Invalid Rows Error Report (if any) -->
            @if(!empty($preview['invalid_rows']))
                <div class="bg-white dark:bg-slate-950 rounded-2xl border border-rose-200 dark:border-rose-900/60 overflow-hidden shadow-xs">
                    <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border-b border-rose-200 dark:border-rose-900/40 text-rose-800 dark:text-rose-300 font-bold text-xs flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Validation Errors Detected in {{ count($preview['invalid_rows']) }} Row(s) (These will be skipped):</span>
                    </div>
                    <div class="divide-y divide-rose-100 dark:divide-rose-900/30 text-xs">
                        @foreach($preview['invalid_rows'] as $err)
                            <div class="p-3.5 flex items-center justify-between gap-4">
                                <div>
                                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400">Row {{ $err['row'] }}</span>
                                    <span class="text-slate-500 ml-2 font-mono">SKU: {{ $err['sku'] }}</span>
                                </div>
                                <div class="text-rose-700 dark:text-rose-300 font-medium">{{ $err['error'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Valid Rows Preview Table -->
            @if(!empty($preview['valid_rows']))
                <div class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs transition-colors">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Valid Rows Ready for Ingestion</h4>
                        <span class="text-[11px] text-slate-500">Atomic database transaction</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">Mode</th>
                                    <th class="px-4 py-3">SKU</th>
                                    <th class="px-4 py-3">Product Name</th>
                                    <th class="px-4 py-3">Variant</th>
                                    <th class="px-4 py-3">Brand &amp; Category</th>
                                    <th class="px-4 py-3">Warehouse &amp; Tax</th>
                                    <th class="px-4 py-3">Price</th>
                                    <th class="px-4 py-3">Initial Stock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                                @foreach($preview['valid_rows'] as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                        <td class="px-4 py-3">
                                            @if($row['is_update'])
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">UPDATE</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">NEW</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">{{ $row['sku'] }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-800 dark:text-slate-200">{{ $row['product_name'] }}</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $row['variant_name'] }} ({{ $row['unit'] ?? 'unit' }})</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                            <div>{{ $row['brand'] ?: 'Universal' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $row['category'] ?: 'General' }}{{ !empty($row['subcategory']) ? ' > ' . $row['subcategory'] : '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                            <div class="font-mono text-[11px] text-slate-700 dark:text-slate-300">{{ $row['warehouse'] ?: 'Central Yard' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $row['tax_class'] ?: 'GST 18%' }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format((float)$row['selling_price'], 2) }}</td>
                                        <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">{{ $row['stock'] ?: '0' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
