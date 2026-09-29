<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttributeDefinition;
use App\Models\AttributeValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttributeController extends Controller
{
    public function index(Request $request): View
    {
        $query = AttributeDefinition::withCount('values');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $attributes = $query->orderBy('sort_order')->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.catalog.attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        $attribute = null;

        return view('admin.catalog.attributes.form', compact('attribute'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|regex:/^[a-z0-9_]+$/|unique:attribute_definitions,code',
            'type' => 'required|in:text,number,select,multi_select,boolean,measurement',
            'is_filterable' => 'nullable|boolean',
            'is_variant_attribute' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'values' => 'nullable|array',
            'values.*.value' => 'required_with:values|string|max:255',
            'values.*.label' => 'nullable|string|max:255',
            'values.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $attribute = AttributeDefinition::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'is_filterable' => ! empty($validated['is_filterable']),
            'is_variant_attribute' => ! empty($validated['is_variant_attribute']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (! empty($validated['values'])) {
            foreach ($validated['values'] as $idx => $val) {
                $attribute->values()->create([
                    'value' => $val['value'],
                    'label' => $val['label'] ?? null,
                    'sort_order' => $val['sort_order'] ?? $idx,
                ]);
            }
        }

        return redirect()->route('admin.catalog.attributes.index')
            ->with('success', "Attribute [{$attribute->name}] created.");
    }

    public function edit(int $id): View
    {
        $attribute = AttributeDefinition::with('values')->findOrFail($id);

        return view('admin.catalog.attributes.form', compact('attribute'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $attribute = AttributeDefinition::with('values')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|regex:/^[a-z0-9_]+$/|unique:attribute_definitions,code,'.$id,
            'type' => 'required|in:text,number,select,multi_select,boolean,measurement',
            'is_filterable' => 'nullable|boolean',
            'is_variant_attribute' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'values' => 'nullable|array',
            'values.*.id' => 'nullable|integer|exists:attribute_values,id',
            'values.*.value' => 'required_with:values|string|max:255',
            'values.*.label' => 'nullable|string|max:255',
            'values.*.sort_order' => 'nullable|integer|min:0',
            'delete_value_ids' => 'nullable|array',
            'delete_value_ids.*' => 'integer|exists:attribute_values,id',
        ]);

        $attribute->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'is_filterable' => ! empty($validated['is_filterable']),
            'is_variant_attribute' => ! empty($validated['is_variant_attribute']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        // Delete removed values
        if (! empty($validated['delete_value_ids'])) {
            AttributeValue::where('attribute_definition_id', $attribute->id)
                ->whereIn('id', $validated['delete_value_ids'])
                ->delete();
        }

        // Upsert values
        if (! empty($validated['values'])) {
            foreach ($validated['values'] as $idx => $val) {
                if (! empty($val['id'])) {
                    AttributeValue::where('attribute_definition_id', $attribute->id)
                        ->where('id', $val['id'])
                        ->update([
                            'value' => $val['value'],
                            'label' => $val['label'] ?? null,
                            'sort_order' => $val['sort_order'] ?? $idx,
                        ]);
                } else {
                    $attribute->values()->create([
                        'value' => $val['value'],
                        'label' => $val['label'] ?? null,
                        'sort_order' => $val['sort_order'] ?? $idx,
                    ]);
                }
            }
        }

        return redirect()->route('admin.catalog.attributes.index')
            ->with('success', "Attribute [{$attribute->name}] updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $attribute = AttributeDefinition::findOrFail($id);
        $name = $attribute->name;
        $attribute->delete();

        return redirect()->route('admin.catalog.attributes.index')
            ->with('success', "Attribute [{$name}] deleted.");
    }
}
