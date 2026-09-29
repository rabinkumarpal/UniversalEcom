<?php

namespace Packages\DriverLogistics\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Packages\DriverLogistics\Models\Driver;

class DriverAuthController extends Controller
{
    public function loginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $isDriver = Driver::where('user_id', Auth::id())->exists();
            if ($isDriver) {
                return redirect()->route('driver.dashboard');
            }
        }

        return view('driver-logistics::auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $fieldType = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$fieldType => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withInput()->withErrors([
                'email' => 'Invalid credentials provided for driver access.',
            ]);
        }

        $request->session()->regenerate();

        $driver = Driver::where('user_id', Auth::id())->first();

        if (! $driver && ! Auth::user()->hasRole('admin')) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your user account is not registered as an active fleet driver.',
            ]);
        }

        if ($driver && $driver->status === 'suspended') {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your driver profile is currently suspended. Please contact dispatch central.',
            ]);
        }

        $driver?->update(['last_active_at' => now()]);

        return redirect()->route('driver.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('driver.login');
    }
}
