<?php

namespace Packages\VendorMarketplace\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorInventory;
use Packages\VendorMarketplace\Models\VendorOffer;

class VendorOfferController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $offers = VendorOffer::where('vendor_id', $vendor->id)
            ->with(['variant.product'])
            ->latest()
            ->paginate(15);

        return view('vendor-marketplace::offers.index', [
            'vendor' => $vendor,
            'offers' => $offers,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        // Exclude variants that this vendor has already created an offer for
        $existingVariantIds = VendorOffer::where('vendor_id', $vendor->id)->pluck('product_variant_id');

        $variants = ProductVariant::with('product')
            ->whereNotIn('id', $existingVariantIds)
            ->where('status', 'active')
            ->get();

        return view('vendor-marketplace::offers.create', [
            'vendor' => $vendor,
            'variants' => $variants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $validated = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'vendor_price' => ['required', 'numeric', 'min:0.01'],
            'vendor_mrp' => ['nullable', 'numeric', 'min:0.01'],
            'vendor_sku' => ['nullable', 'string', 'max:100'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $variant = ProductVariant::findOrFail($validated['product_variant_id']);

        $priceFloat = (float) $validated['vendor_price'];
        $catalogMrp = $variant->mrp ? $variant->mrp / 100 : null;
        $vendorMrpFloat = ! empty($validated['vendor_mrp']) ? (float) $validated['vendor_mrp'] : null;

        if ($catalogMrp !== null && $vendorMrpFloat !== null && $vendorMrpFloat > $catalogMrp) {
            return back()->withInput()->withErrors([
                'vendor_mrp' => 'Vendor MRP (₹'.number_format($vendorMrpFloat, 2).') cannot exceed manufacturer catalog MRP of ₹'.number_format($catalogMrp, 2).'.',
            ]);
        }

        $effectiveMrp = $vendorMrpFloat ?? $catalogMrp;
        if ($effectiveMrp !== null && $priceFloat > $effectiveMrp) {
            return back()->withInput()->withErrors([
                'vendor_price' => 'Your selling price (₹'.number_format($priceFloat, 2).') cannot exceed the Maximum Retail Price (MRP) of ₹'.number_format($effectiveMrp, 2).'.',
            ]);
        }

        $priceMinor = (int) round(((float) $validated['vendor_price']) * 100);
        $mrpMinor = ! empty($validated['vendor_mrp']) ? (int) round(((float) $validated['vendor_mrp']) * 100) : null;
        $initialStock = (int) ($validated['initial_stock'] ?? 0);

        DB::transaction(function () use ($vendor, $validated, $priceMinor, $mrpMinor, $initialStock) {
            $offer = VendorOffer::updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'product_variant_id' => $validated['product_variant_id'],
                ],
                [
                    'vendor_sku' => $validated['vendor_sku'] ?? null,
                    'vendor_price' => $priceMinor,
                    'vendor_mrp' => $mrpMinor,
                    'status' => 'approved',
                ]
            );

            if ($initialStock > 0) {
                VendorInventory::updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'product_variant_id' => $validated['product_variant_id'],
                    ],
                    [
                        'on_hand' => $initialStock,
                        'available' => $initialStock,
                        'reserved' => 0,
                    ]
                );
            }
        });

        return redirect()->route('vendor.offers.index')->with('success', 'Catalog offer published successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('vendor');

        $offer = VendorOffer::where('vendor_id', $vendor->id)->with('variant')->findOrFail($id);

        $validated = $request->validate([
            'vendor_price' => ['required', 'numeric', 'min:0.01'],
            'vendor_mrp' => ['nullable', 'numeric', 'min:0.01'],
            'status' => ['required', 'in:approved,inactive'],
        ]);

        $priceFloat = (float) $validated['vendor_price'];
        $catalogMrp = $offer->variant?->mrp ? $offer->variant->mrp / 100 : null;
        $vendorMrpFloat = ! empty($validated['vendor_mrp']) ? (float) $validated['vendor_mrp'] : null;

        if ($catalogMrp !== null && $vendorMrpFloat !== null && $vendorMrpFloat > $catalogMrp) {
            return back()->withInput()->withErrors([
                'vendor_mrp' => 'Vendor MRP (₹'.number_format($vendorMrpFloat, 2).') cannot exceed manufacturer catalog MRP of ₹'.number_format($catalogMrp, 2).'.',
            ]);
        }

        $effectiveMrp = $vendorMrpFloat ?? $catalogMrp;
        if ($effectiveMrp !== null && $priceFloat > $effectiveMrp) {
            return back()->withInput()->withErrors([
                'vendor_price' => 'Your selling price (₹'.number_format($priceFloat, 2).') cannot exceed the Maximum Retail Price (MRP) of ₹'.number_format($effectiveMrp, 2).'.',
            ]);
        }

        $offer->update([
            'vendor_price' => (int) round(((float) $validated['vendor_price']) * 100),
            'vendor_mrp' => ! empty($validated['vendor_mrp']) ? (int) round(((float) $validated['vendor_mrp']) * 100) : null,
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Offer updated successfully.');
    }
}
