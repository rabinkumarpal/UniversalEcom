<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the administrative console login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('super_admin') ||
                $user->hasRole('catalog-manager') || $user->hasRole('order-manager') ||
                $user->hasRole('inventory-manager') || $user->hasRole('delivery-manager')) {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('admin.auth.login');
    }

    /**
     * Authenticate administrative user credentials.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            $hasAdminAccess = $user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('super_admin') ||
                $user->hasRole('catalog-manager') || $user->hasRole('order-manager') ||
                $user->hasRole('inventory-manager') || $user->hasRole('delivery-manager');

            if (! $hasAdminAccess) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Access denied. You do not have administrative privileges.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log out of the administrative console.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been successfully logged out.');
    }
}
