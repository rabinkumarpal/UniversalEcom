<?php

namespace Packages\VendorMarketplace\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorUser;

class VendorAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('vendor-marketplace::auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('vendor.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('vendor-marketplace::auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:vendors,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'status' => 'active',
            ]);

            $vendor = Vendor::create([
                'legal_name' => $validated['company_name'],
                'display_name' => $validated['display_name'],
                'slug' => Str::slug($validated['display_name']).'-'.Str::lower(Str::random(4)),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => 'pending',
                'approval_status' => 'pending',
                'commission_rate_percentage' => 10.00,
            ]);

            VendorUser::create([
                'vendor_id' => $vendor->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('vendor.dashboard')->with('status', 'Your vendor application has been received and is pending administrator review.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login')->with('status', 'You have been successfully logged out.');
    }
}
