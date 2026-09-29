@extends('layouts.admin')

@section('title', $promotion ? 'Edit Promotion' : 'New Promotion')
@section('header_title', $promotion ? 'Edit Promotion' : 'Create Promotion')
@section('header_subtitle', $promotion ? "Editing: {$promotion->name}" : 'Configure a new promotion for the engine')

@section('content')
<div class="max-w-3xl space-y-6">

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <div>⚠ {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form
        method="POST"
        action="{{ $promotion ? route('admin.promotions.update', $promotion->id) : route('admin.promotions.store') }}"
        class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl divide-y divide-slate-100 dark:divide-slate-900 shadow-xs transition-colors"
    >
        @csrf
        @if($promotion) @method('PUT') @endif

        {{-- Basic info --}}
        <div class="p-6 space-y-5">
            <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Basic Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $promotion?->name) }}" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Coupon Code <span class="text-slate-400 font-normal">(leave blank for automatic)</span></label>
                    <input type="text" name="code" value="{{ old('code', $promotion?->code) }}" placeholder="e.g. SUMMER20"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono uppercase focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($types as $t)
                            <option value="{{ $t }}" @selected(old('type', $promotion?->type) === $t)>{{ str_replace('_', ' ', ucfirst($t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', $promotion?->status ?? 'draft') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Priority <span class="text-slate-400 font-normal">(lower = higher precedence)</span></label>
                    <input type="number" name="priority" value="{{ old('priority', $promotion?->priority ?? 10) }}" min="0" max="1000"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-3 pt-5">
                    <input type="hidden" name="stackable" value="0">
                    <input type="checkbox" name="stackable" id="stackable" value="1"
                        @checked(old('stackable', $promotion?->stackable))
                        class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-indigo-500">
                    <label for="stackable" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Stackable with other promotions</label>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="p-6 space-y-4">
            <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Schedule</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Starts At</label>
                    <input type="datetime-local" name="starts_at"
                        value="{{ old('starts_at', $promotion?->starts_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Ends At</label>
                    <input type="datetime-local" name="ends_at"
                        value="{{ old('ends_at', $promotion?->ends_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Usage limits --}}
        <div class="p-6 space-y-4">
            <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Usage Limits</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Total Usage Limit</label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', $promotion?->usage_limit) }}" min="1" placeholder="Unlimited"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Per-Customer Limit</label>
                    <input type="number" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $promotion?->usage_limit_per_customer) }}" min="1" placeholder="Unlimited"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Configuration JSON --}}
        <div class="p-6 space-y-3">
            <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Type Configuration (JSON)</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Configure type-specific behaviour as a JSON object. Examples by type:
            </p>
            <div class="text-xs text-slate-600 dark:text-slate-400 font-mono bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-1 leading-relaxed">
                <div>percentage:      { "discount_percentage": 10 }</div>
                <div>fixed:           { "fixed_amount": 50000 }  <span class="text-slate-400">// paise</span></div>
                <div>bogo:            { "buy_quantity": 10, "get_quantity": 1 }</div>
                <div>bundle:          { "discount_percentage": 7.5 }  <span class="text-slate-400">// + attach bundle_items in DB</span></div>
                <div>mix_match:       { "min_quantity": 3, "discount_percentage": 10 }</div>
                <div>buy_x_get_y_bundle: { "qualifying_lines": [{"variant_id":1,"quantity":2}], "reward_variant_id": 5, "reward_quantity": 1 }</div>
                <div>countdown:       { "discount_percentage": 15 }  <span class="text-slate-400">// set ends_at for timer</span></div>
                <div>spending_goal:   { "goal_amount": 500000, "reward_type": "free_shipping" }</div>
                <div>free_shipping:   {}</div>
                <div>stock_scarcity:  { "threshold": 10, "eligible_variant_ids": [1,2,3] }</div>
            </div>
            <textarea name="configuration" rows="8"
                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-slate-100 font-mono focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                placeholder='{"discount_percentage": 10}'>{{ old('configuration', $promotion ? json_encode($promotion->configuration, JSON_PRETTY_PRINT) : '') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="p-6 flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-xs">
                {{ $promotion ? 'Update Promotion' : 'Create Promotion' }}
            </button>
            <a href="{{ route('admin.promotions.index') }}" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-semibold rounded-xl transition-colors">
                Cancel
            </a>
        </div>
    </form>

</div>
@endsection
