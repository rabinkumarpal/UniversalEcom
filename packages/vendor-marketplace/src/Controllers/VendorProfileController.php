<?php

namespace Packages\VendorMarketplace\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;

class VendorProfileController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        return view('vendor-marketplace::profile.index', [
            'vendor' => $vendor,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $vendor->update($validated);

        return back()->with('success', 'Vendor profile updated successfully.');
    }
}
