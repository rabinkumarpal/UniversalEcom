@extends('layouts.admin')

@section('title', $attribute ? 'Edit Attribute' : 'New Attribute')
@section('header_title', $attribute ? 'Edit Attribute Definition' : 'New Attribute Definition')
@section('header_subtitle', 'Define a technical specification field for use across products')

@section('content')
<div x-data="attributeForm()" class="max-w-3xl mx-auto space-y-6">

    {{-- Success / Error messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl px-4 py-3 text-xs text-rose-700 dark:text-rose-300">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
        action="{{ $attribute ? route('admin.catalog.attributes.update', $attribute->id) : route('admin.catalog.attributes.store') }}">
        @csrf
        @if($attribute) @method('PUT') @endif

        {{-- Core Definition --}}
        <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="border-b border-slate-100 dark:border-slate-900 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Attribute Definition</h3>
                <p class="text-xs text-slate-500 mt-0.5">Core identity of this specification field.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Display Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $attribute?->name) }}" required
                        placeholder="e.g. Compressive Strength, Color, Grade"
                        @input="autoCode($event.target.value)"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </div>

                {{-- Code --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Machine Code <span class="text-rose-500">*</span>
                        <span class="text-[10px] font-normal text-slate-400 ml-1">(lowercase, underscores only, unique)</span>
                    </label>
                    <input type="text" name="code" id="attr-code" value="{{ old('code', $attribute?->code) }}" required
                        pattern="[a-z0-9_]+" placeholder="e.g. compressive_strength"
                        @if($attribute) readonly class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-500 dark:text-slate-400 font-mono cursor-not-allowed" @else class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-slate-100 font-mono focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition" @endif>
                    @if($attribute)
                        <p class="text-[10px] text-slate-400 mt-1">Code cannot be changed after creation to preserve data integrity.</p>
                    @endif
                </div>
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                    Field Type <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach([
                        ['text', 'Text', 'Free-form text value', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['number', 'Number', 'Numeric measurement', 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
                        ['measurement', 'Measurement', 'Number with unit suffix', 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
                        ['select', 'Select', 'Single choice from list', 'M8 9l4-4 4 4m0 6l-4 4-4-4'],
                        ['multi_select', 'Multi-select', 'Multiple choices from list', 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                        ['boolean', 'Boolean', 'Yes / No flag', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ] as [$val, $label, $desc, $icon])
                    <label class="relative cursor-pointer">
                        <input type="radio" name="type" value="{{ $val }}" @checked(old('type', $attribute?->type ?? 'text') === $val)
                            x-model="attrType"
                            class="sr-only peer">
                        <div class="border-2 border-slate-200 dark:border-slate-700 rounded-xl p-3 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-950/50 hover:border-slate-300 transition flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 peer-checked:text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $label }}</span>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ $desc }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Flags --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <label class="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition">
                    <input type="hidden" name="is_filterable" value="0">
                    <input type="checkbox" name="is_filterable" value="1" @checked(old('is_filterable', $attribute?->is_filterable ?? true))
                        class="mt-0.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
                    <div>
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Filterable in Catalog</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Show this attribute as a filter option on the storefront catalog page.</div>
                    </div>
                </label>
                <label class="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition">
                    <input type="hidden" name="is_variant_attribute" value="0">
                    <input type="checkbox" name="is_variant_attribute" value="1" @checked(old('is_variant_attribute', $attribute?->is_variant_attribute ?? false))
                        class="mt-0.5 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
                    <div>
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Variant-level Attribute</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">This attribute varies per variant (e.g. Size, Color) rather than being product-wide.</div>
                    </div>
                </label>
            </div>

            {{-- Sort Order --}}
            <div class="w-32">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $attribute?->sort_order ?? 0) }}" min="0" max="999"
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                <p class="text-[10px] text-slate-400 mt-1">Lower number = appears first.</p>
            </div>
        </div>

        {{-- Predefined Values (select / multi_select types only) --}}
        <div x-show="attrType === 'select' || attrType === 'multi_select'" x-cloak
            class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-900 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Predefined Values</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Options shown in dropdowns when assigning this attribute to a product.</p>
                </div>
                <button type="button" @click="addValue()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Value
                </button>
            </div>

            {{-- Existing values (on edit) --}}
            @if($attribute && $attribute->values->isNotEmpty())
                <div class="space-y-2">
                    @foreach($attribute->values as $idx => $val)
                        <div class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-800">
                            <input type="hidden" name="values[{{ $idx }}][id]" value="{{ $val->id }}">
                            <div class="flex-1 grid grid-cols-2 gap-2">
                                <input type="text" name="values[{{ $idx }}][value]" value="{{ old("values.{$idx}.value", $val->value) }}"
                                    placeholder="Raw value (e.g. OPC43)"
                                    class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <input type="text" name="values[{{ $idx }}][label]" value="{{ old("values.{$idx}.label", $val->label) }}"
                                    placeholder="Display label (e.g. OPC Grade 43)"
                                    class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>
                            <input type="number" name="values[{{ $idx }}][sort_order]" value="{{ old("values.{$idx}.sort_order", $val->sort_order) }}"
                                placeholder="Order" min="0" class="w-16 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1.5 text-xs text-center focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <label class="flex items-center gap-1 text-[10px] text-rose-600 cursor-pointer hover:text-rose-700">
                                <input type="checkbox" name="delete_value_ids[]" value="{{ $val->id }}"
                                    class="rounded border-rose-300 text-rose-500 w-3 h-3">
                                Delete
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- New values (Alpine) --}}
            <div class="space-y-2">
                <template x-for="(val, i) in newValues" :key="i">
                    <div class="flex items-center gap-2 p-2 bg-indigo-50/50 dark:bg-indigo-950/30 rounded-lg border border-indigo-200 dark:border-indigo-800/50">
                        <div class="flex-1 grid grid-cols-2 gap-2">
                            <input type="text" :name="`values[new_${i}][value]`" x-model="val.value"
                                placeholder="Raw value (e.g. OPC43)"
                                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <input type="text" :name="`values[new_${i}][label]`" x-model="val.label"
                                placeholder="Display label (optional)"
                                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <input type="number" :name="`values[new_${i}][sort_order]`" x-model="val.sort_order"
                            placeholder="Order" min="0" class="w-16 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1.5 text-xs text-center focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <button type="button" @click="newValues.splice(i, 1)"
                            class="p-1 text-slate-400 hover:text-rose-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <p x-show="!newValues.length && {{ $attribute && $attribute->values->isNotEmpty() ? 'false' : 'true' }}" class="text-xs text-slate-400 text-center py-2">
                No values yet. Click "Add Value" to define the allowed options.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('admin.catalog.attributes.index') }}" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition">
                ← Back to Attributes
            </a>
            <div class="flex items-center gap-3">
                @if($attribute)
                    <form method="POST" action="{{ route('admin.catalog.attributes.destroy', $attribute->id) }}"
                        onsubmit="return confirm('Delete this attribute? This will remove it from all products.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-rose-600 hover:text-rose-700 border border-rose-200 dark:border-rose-800 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition">
                            Delete Attribute
                        </button>
                    </form>
                @endif
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                    {{ $attribute ? 'Save Changes' : 'Create Attribute' }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function attributeForm() {
    return {
        attrType: '{{ old('type', $attribute?->type ?? 'text') }}',
        newValues: [],
        addValue() {
            this.newValues.push({ value: '', label: '', sort_order: this.newValues.length });
        },
        autoCode(name) {
            @if(!$attribute)
            const codeEl = document.getElementById('attr-code');
            if (codeEl && !codeEl.dataset.edited) {
                codeEl.value = name.toLowerCase()
                    .replace(/[^a-z0-9\s_]/g, '')
                    .replace(/\s+/g, '_')
                    .replace(/_+/g, '_')
                    .slice(0, 100);
            }
            @endif
        }
    };
}
</script>
@endsection
