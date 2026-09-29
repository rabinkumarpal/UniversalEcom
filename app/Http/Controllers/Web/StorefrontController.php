<?php

namespace App\Http\Controllers\Web;

use App\Core\Services\SettingService;
use App\Domain\Cart\CartService;
use App\Domain\Catalog\CatalogService;
use App\Domain\Checkout\CheckoutService;
use App\Domain\Delivery\DeliveryService;
use App\Domain\Pricing\PricingPipeline;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\DeliveryZonePincode;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\LoyaltyWallet\Services\WalletService;
use Packages\PromotionEngine\Models\Coupon;
use Packages\PromotionEngine\Models\Promotion;

class StorefrontController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService,
        protected CartService $cartService,
        protected PricingPipeline $pricingPipeline,
        protected CheckoutService $checkoutService
    ) {}

    public function home(): View
    {
        $categories = Category::whereNull('parent_id')
            ->where('status', 'active')
            ->with(['children' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::where('status', 'published')
            ->with(['brand', 'primaryCategory', 'variants.quantityTiers', 'primaryMedia'])
            ->take(8)
            ->get();

        return view('storefront.home', compact('categories', 'featuredProducts'));
    }

    public function catalog(Request $request): View
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
        $brands = Brand::where('status', 'active')->get();
        $products = $this->catalogService->search($request->all(), 12);

        return view('storefront.catalog', compact('categories', 'brands', 'products'));
    }

    public function product(string $slug): View
    {
        $product = $this->catalogService->findBySlug($slug);

        if (! $product) {
            abort(404, 'Product not found');
        }

        $relatedProducts = Product::where('primary_category_id', $product->primary_category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'published')
            ->with(['brand', 'primaryCategory', 'variants.quantityTiers', 'primaryMedia'])
            ->take(4)
            ->get();

        return view('storefront.product', compact('product', 'relatedProducts'));
    }

    public function cart(Request $request): View
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), session()->getId());

        if (! session()->has('delivery_pincode') && $request->user()) {
            $defaultAddress = Address::where('user_id', $request->user()->id)
                ->orderByDesc('is_default')
                ->first();
            if ($defaultAddress && ! empty($defaultAddress->pincode)) {
                session(['delivery_pincode' => $defaultAddress->pincode]);
            }
        }

        $pincode = session('delivery_pincode', '560001');
        $address = new Address(['pincode' => $pincode]);

        $totals = $this->pricingPipeline->calculate($cart, $address, [
            'coupon_code' => session('applied_coupon'),
        ]);

        $isServiceable = ! DeliveryZonePincode::exists() || app(DeliveryService::class)->isServiceable($pincode);

        return view('storefront.cart', compact('cart', 'totals', 'pincode', 'isServiceable'));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user(), session()->getId());
        $this->cartService->addItem($cart, $validated['variant_id'], $validated['quantity']);

        return back()->with('success', 'Item added to your cart successfully!');
    }

    public function updateCartItem(Request $request, int $itemId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user(), session()->getId());
        $item = $cart->items()->findOrFail($itemId);

        $this->cartService->updateItem($item, $validated['quantity']);

        return back()->with('success', 'Cart updated successfully.');
    }

    public function removeCartItem(Request $request, int $itemId): RedirectResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), session()->getId());
        $item = $cart->items()->findOrFail($itemId);

        $this->cartService->removeItem($item);

        return back()->with('success', 'Item removed from cart.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $code = strtoupper(trim($request->input('coupon_code', '')));
        if ($code === '') {
            session()->forget('applied_coupon');

            return back()->with('error', 'Please enter a valid coupon code.');
        }

        if (class_exists(Promotion::class)) {
            $hasAnyCodedPromos = Promotion::whereNotNull('code')->where('status', 'active')->exists()
                || (class_exists(Coupon::class) && Coupon::where('is_active', true)->exists());

            if ($hasAnyCodedPromos) {
                $promoExists = Promotion::where('code', $code)
                    ->where('status', 'active')
                    ->get()
                    ->filter(fn ($p) => $p->isValidNow())
                    ->isNotEmpty();

                $couponExists = class_exists(Coupon::class)
                    ? Coupon::where('code', $code)
                        ->where('is_active', true)
                        ->get()
                        ->filter(fn ($c) => $c->isValid($request->user()))
                        ->isNotEmpty()
                    : false;

                if (! $promoExists && ! $couponExists) {
                    session()->forget('applied_coupon');

                    return back()->with('error', "Coupon code [{$code}] is invalid or expired.");
                }
            }
        }

        session(['applied_coupon' => $code]);

        return back()->with('success', "Coupon code [{$code}] applied.");
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        session()->forget('applied_coupon');

        return back()->with('success', 'Coupon code removed.');
    }

    public function setLocation(Request $request): RedirectResponse
    {
        $pincode = trim($request->input('pincode', '560001'));
        session(['delivery_pincode' => $pincode]);

        return back()->with('success', "Delivery location set to pincode {$pincode}.");
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            $guestAllowed = (bool) app(SettingService::class)->get('features.guest_checkout_enabled', true);
            if (! $guestAllowed) {
                return redirect()->route('admin.login')->with('error', 'Guest checkout is currently disabled. Please sign in to complete your order.');
            }
        }

        $cart = $this->cartService->getOrCreateCart($request->user(), session()->getId());

        if ($cart->items->isEmpty()) {
            return redirect()->route('storefront.cart')->with('error', 'Your cart is empty.');
        }

        if (! session()->has('delivery_pincode') && $request->user()) {
            $defaultAddress = Address::where('user_id', $request->user()->id)
                ->orderByDesc('is_default')
                ->first();
            if ($defaultAddress && ! empty($defaultAddress->pincode)) {
                session(['delivery_pincode' => $defaultAddress->pincode]);
            }
        }

        $pincode = session('delivery_pincode', '560001');
        $address = new Address(['pincode' => $pincode]);

        $totals = $this->pricingPipeline->calculate($cart, $address, [
            'coupon_code' => session('applied_coupon'),
        ]);

        $minOrderAmount = (float) app(SettingService::class)->get('order.min_order_amount', 0);
        $grandTotalAmount = (float) (($totals['grand_total'] ?? 0) / 100);
        if ($minOrderAmount > 0 && $grandTotalAmount < $minOrderAmount) {
            return redirect()->route('storefront.cart')->with('error', 'Minimum order amount is ₹'.number_format($minOrderAmount, 2).'. Your current cart total is ₹'.number_format($grandTotalAmount, 2).'.');
        }

        $isServiceable = ! DeliveryZonePincode::exists() || app(DeliveryService::class)->isServiceable($pincode);

        $slots = DeliverySlot::where('is_active', true)->get();

        $wallet = null;
        if ($request->user() && class_exists(WalletService::class)) {
            $wallet = app(WalletService::class)->getOrCreateWallet($request->user());
        }

        $company = null;
        $companyUser = null;
        if ($request->user() && class_exists(CompanyUser::class)) {
            $companyUser = CompanyUser::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->with('company')
                ->first();
            $company = $companyUser?->company;
        }

        $savedAddresses = $request->user()
            ? Address::where('user_id', $request->user()->id)->orderByDesc('is_default')->get()
            : collect();

        return view('storefront.checkout', compact('cart', 'totals', 'pincode', 'isServiceable', 'slots', 'wallet', 'company', 'companyUser', 'savedAddresses'));
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            $guestAllowed = (bool) app(SettingService::class)->get('features.guest_checkout_enabled', true);
            if (! $guestAllowed) {
                return redirect()->route('admin.login')->with('error', 'Guest checkout is currently disabled. Please sign in to complete your order.');
            }
        }

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'payment_gateway' => 'required|string|in:cod,wallet,purchase_order',
            'po_number' => 'nullable|string|max:100',
            'is_site_address' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user(), session()->getId());

        $calcAddress = new Address(['pincode' => $validated['pincode']]);
        $totals = $this->pricingPipeline->calculate($cart, $calcAddress, [
            'coupon_code' => session('applied_coupon'),
        ]);

        $minOrderAmount = (float) app(SettingService::class)->get('order.min_order_amount', 0);
        $grandTotalAmount = (float) (($totals['grand_total'] ?? 0) / 100);
        if ($minOrderAmount > 0 && $grandTotalAmount < $minOrderAmount) {
            return redirect()->route('storefront.cart')->with('error', 'Minimum order amount is ₹'.number_format($minOrderAmount, 2).'. Your current cart total is ₹'.number_format($grandTotalAmount, 2).'.');
        }

        if ($request->user()) {
            $address = Address::create([
                'user_id' => $request->user()->id,
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'address_line_1' => $validated['address_line_1'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'pincode' => $validated['pincode'],
                'is_site_address' => ! empty($validated['is_site_address']),
            ]);
        } else {
            $address = new Address([
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'address_line_1' => $validated['address_line_1'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'pincode' => $validated['pincode'],
                'is_site_address' => ! empty($validated['is_site_address']),
            ]);
        }

        $options = ['coupon_code' => session('applied_coupon')];
        if (! empty($validated['po_number'])) {
            $options['po_number'] = $validated['po_number'];
        }

        $order = $this->checkoutService->checkout(
            $cart,
            $address,
            $address,
            $validated['payment_gateway'],
            $validated['notes'] ?? null,
            $request->user(),
            $options
        );

        session()->forget('applied_coupon');

        return redirect()->route('storefront.order_confirmation', $order->order_number);
    }

    public function orderConfirmation(string $orderNumber): View
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items', 'payments', 'invoice', 'statusHistory'])
            ->firstOrFail();

        return view('storefront.order_confirmation', compact('order'));
    }
}
