<?php

namespace App\Http\Controllers\Web;

use App\Core\Registry\AddonRegistry;
use App\Core\Services\AuditService;
use App\Core\Services\SettingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function __construct(
        protected SettingService $settings,
        protected AddonRegistry $addonRegistry,
        protected AuditService $audit
    ) {}

    /**
     * Display administrative settings console.
     */
    public function index(): View
    {
        $settings = [
            'store_name' => $this->settings->get('store.name', config('app.name', 'Universal Commerce')),
            'store_email' => $this->settings->get('store.email', 'operations@universal-ecom.test'),
            'store_phone' => $this->settings->get('store.phone', '+91 80 4000 8000'),
            'store_address' => $this->settings->get('store.address', 'Industrial Logistics Zone, Depot 4, Bangalore - 560001'),

            'currency' => $this->settings->get('store.currency', 'INR'),
            'currency_symbol' => $this->settings->get('store.currency_symbol', '₹'),
            'default_tax_rate' => $this->settings->get('tax.default_rate', 18),
            'prices_include_tax' => (bool) $this->settings->get('tax.inclusive', true),

            'min_order_amount' => $this->settings->get('order.min_order_amount', 0),
            'free_shipping_threshold' => $this->settings->get('delivery.free_shipping_threshold', 5000),
            'standard_shipping_rate' => $this->settings->get('delivery.standard_rate', 150),
            'express_shipping_rate' => $this->settings->get('delivery.express_rate', 350),

            'reviews_enabled' => (bool) $this->settings->get('features.reviews_enabled', true),
            'wallet_cashback_enabled' => (bool) $this->settings->get('features.wallet_cashback_enabled', true),
            'marketplace_enabled' => (bool) $this->settings->get('features.marketplace_enabled', true),
            'guest_checkout_enabled' => (bool) $this->settings->get('features.guest_checkout_enabled', true),
        ];

        $addons = $this->addonRegistry->all();

        return view('admin.settings', compact('settings', 'addons'));
    }

    /**
     * Save administrative settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'store_email' => ['required', 'email', 'max:150'],
            'store_phone' => ['required', 'string', 'max:30'],
            'store_address' => ['required', 'string', 'max:255'],

            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'prices_include_tax' => ['nullable', 'boolean'],

            'min_order_amount' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'standard_shipping_rate' => ['required', 'numeric', 'min:0'],
            'express_shipping_rate' => ['required', 'numeric', 'min:0'],

            'reviews_enabled' => ['nullable', 'boolean'],
            'wallet_cashback_enabled' => ['nullable', 'boolean'],
            'marketplace_enabled' => ['nullable', 'boolean'],
            'guest_checkout_enabled' => ['nullable', 'boolean'],
        ]);

        $this->settings->set('store.name', $validated['store_name'], 'store');
        $this->settings->set('store.email', $validated['store_email'], 'store');
        $this->settings->set('store.phone', $validated['store_phone'], 'store');
        $this->settings->set('store.address', $validated['store_address'], 'store');

        $this->settings->set('store.currency', $validated['currency'], 'currency');
        $this->settings->set('store.currency_symbol', $validated['currency_symbol'], 'currency');
        $this->settings->set('tax.default_rate', $validated['default_tax_rate'], 'tax');
        $this->settings->set('tax.inclusive', $request->boolean('prices_include_tax'), 'tax');

        $this->settings->set('order.min_order_amount', $validated['min_order_amount'], 'orders');
        $this->settings->set('delivery.free_shipping_threshold', $validated['free_shipping_threshold'], 'delivery');
        $this->settings->set('delivery.standard_rate', $validated['standard_shipping_rate'], 'delivery');
        $this->settings->set('delivery.express_rate', $validated['express_shipping_rate'], 'delivery');

        $this->settings->set('features.reviews_enabled', $request->boolean('reviews_enabled'), 'features');
        $this->settings->set('features.wallet_cashback_enabled', $request->boolean('wallet_cashback_enabled'), 'features');
        $this->settings->set('features.marketplace_enabled', $request->boolean('marketplace_enabled'), 'features');
        $this->settings->set('features.guest_checkout_enabled', $request->boolean('guest_checkout_enabled'), 'features');

        $this->audit->settingChanged([], $validated);

        return back()->with('success', 'Platform settings and feature flags updated successfully.');
    }

    /**
     * Toggle individual add-on status.
     */
    public function toggleAddon(string $id): RedirectResponse
    {
        if (! $this->addonRegistry->has($id)) {
            return back()->with('error', "Addon [{$id}] is not registered in the system.");
        }

        $newState = $this->settings->toggleAddon($id);
        $statusStr = $newState ? 'enabled' : 'disabled';

        return back()->with('success', "Add-on [{$id}] successfully {$statusStr}.");
    }
}
