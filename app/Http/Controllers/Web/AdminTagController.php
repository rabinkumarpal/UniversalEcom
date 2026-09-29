<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTagController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tag::withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $tags = $query->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.catalog.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
        ]);

        Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('admin.catalog.tags.index')
            ->with('success', "Tag [{$validated['name']}] created.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name,'.$id,
        ]);

        $tag->update(['name' => $validated['name']]);

        return redirect()->route('admin.catalog.tags.index')
            ->with('success', "Tag [{$tag->name}] updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $tag = Tag::findOrFail($id);
        $name = $tag->name;
        $tag->delete();

        return redirect()->route('admin.catalog.tags.index')
            ->with('success', "Tag [{$name}] deleted.");
    }
}
