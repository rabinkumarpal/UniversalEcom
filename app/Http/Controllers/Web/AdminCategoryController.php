<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::withCount('primaryProducts');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.catalog.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents = Category::whereNull('parent_id')->orderBy('name')->get();
        $category = null;

        return view('admin.catalog.categories.form', compact('category', 'parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        return redirect()->route('admin.catalog.categories.index')
            ->with('success', "Category [{$validated['name']}] created.");
    }

    public function edit(int $id): View
    {
        $category = Category::findOrFail($id);
        $parents = Category::whereNull('parent_id')->where('id', '!=', $id)->orderBy('name')->get();

        return view('admin.catalog.categories.form', compact('category', 'parents'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        return redirect()->route('admin.catalog.categories.index')
            ->with('success', "Category [{$category->name}] updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);
        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.catalog.categories.index')
            ->with('success', "Category [{$name}] deleted.");
    }
}
