<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhere('phone', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $request->role));
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function show(int $id): View
    {
        $user = User::with(['roles', 'orders', 'addresses'])->findOrFail($id);
        $allRoles = Role::orderBy('name')->get();

        return view('admin.users.show', compact('user', 'allRoles'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,suspended,banned',
        ]);

        $user->update(['status' => $validated['status']]);

        return back()->with('success', "User [{$user->name}] status updated to {$validated['status']}.");
    }

    public function assignRole(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $user->roles()->sync($validated['role_ids'] ?? []);

        return back()->with('success', "Roles updated for [{$user->name}].");
    }
}
