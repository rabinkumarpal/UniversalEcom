<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminBrandController extends Controller
{
    public function index(Request $request): View
    {
        $query = Brand::withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $brands = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.catalog.brands.index', compact('brands'));
    }

    public function create(): View
    {
        $brand = null;

        return view('admin.catalog.brands.form', compact('brand'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        Brand::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'description' => $validated['description'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.catalog.brands.index')
            ->with('success', "Brand [{$validated['name']}] created.");
    }

    public function edit(int $id): View
    {
        $brand = Brand::findOrFail($id);

        return view('admin.catalog.brands.form', compact('brand'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,'.$id,
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $brand->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.catalog.brands.index')
            ->with('success', "Brand [{$brand->name}] updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $brand = Brand::findOrFail($id);
        $name = $brand->name;
        $brand->delete();

        return redirect()->route('admin.catalog.brands.index')
            ->with('success', "Brand [{$name}] deleted.");
    }
}
