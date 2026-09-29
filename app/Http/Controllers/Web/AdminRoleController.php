<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('users')->with('permissions')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $role = null;
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('admin.roles.form', compact('role', 'allPermissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'slug' => 'required|string|max:100|unique:roles,slug|alpha_dash',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$role->name}] created successfully.");
    }

    public function edit(int $id): View
    {
        $role = Role::with('permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('admin.roles.form', compact('role', 'allPermissions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,'.$id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$role->name}] updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->with('error', "Cannot delete [{$role->name}] — it is assigned to active users.");
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$name}] deleted.");
    }

    public function assignUsers(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        /** @phpstan-ignore-next-line */
        $role->users()->sync($validated['user_ids'] ?? []);

        return back()->with('success', "Users updated for role [{$role->name}].");
    }
}
