<?php

namespace Packages\B2BCommerce\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\ContractPriceList;
use Packages\B2BCommerce\Models\ContractVariantPrice;
use Packages\B2BCommerce\Models\CustomerGroup;

class AdminB2BPriceListController extends Controller
{
    public function index(Request $request): View
    {
        $priceLists = ContractPriceList::with(['company', 'customerGroup'])
            ->withCount('variantPrices')
            ->latest()
            ->paginate(15);

        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $customerGroups = CustomerGroup::orderBy('name')->get();
        $variants = ProductVariant::with('product')->orderBy('sku')->get();

        return view('admin.b2b.price-lists', [
            'priceLists' => $priceLists,
            'companies' => $companies,
            'customerGroups' => $customerGroups,
            'variants' => $variants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $priceList = ContractPriceList::create([
            'name' => $validated['name'],
            'company_id' => $validated['company_id'] ?? null,
            'customer_group_id' => $validated['customer_group_id'] ?? null,
            'currency' => 'INR',
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Contract Price List [{$priceList->name}] created.");
    }

    public function addVariantPrice(Request $request, int $priceListId): RedirectResponse
    {
        $priceList = ContractPriceList::findOrFail($priceListId);

        $validated = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'custom_price_in_rupees' => ['required', 'numeric', 'min:0'],
            'min_quantity' => ['required', 'integer', 'min:1'],
        ]);

        $customPricePaise = (int) round($validated['custom_price_in_rupees'] * 100);

        ContractVariantPrice::updateOrCreate(
            [
                'price_list_id' => $priceList->id,
                'product_variant_id' => $validated['product_variant_id'],
                'min_quantity' => $validated['min_quantity'],
            ],
            [
                'custom_price' => $customPricePaise,
            ]
        );

        return back()->with('success', "Negotiated variant price updated in price list [{$priceList->name}].");
    }
}
