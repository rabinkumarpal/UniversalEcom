<?php

namespace Packages\LoyaltyWallet\Controllers;

use App\Core\Services\SettingService;
use App\Domain\Cart\CartService;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Packages\LoyaltyWallet\Models\ProductReview;
use Packages\LoyaltyWallet\Models\Wishlist;
use Packages\LoyaltyWallet\Services\ReorderService;
use Packages\LoyaltyWallet\Services\WalletService;

class CustomerAccountController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected ReorderService $reorderService,
        protected CartService $cartService
    ) {}

    /**
     * Display customer order history with tracking and 1-click reorder.
     */
    public function orders(Request $request): View
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)
            ->with(['items.variant.product.primaryMedia', 'invoice', 'gstInvoice', 'payments', 'shipments'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $userReviews = ProductReview::where('user_id', $user->id)
            ->pluck('rating', 'product_id')
            ->toArray();

        return view('account.orders', compact('orders', 'userReviews'));
    }

    /**
     * 1-Click reorder: re-populate cart with items from a past order.
     */
    public function reorder(Request $request, string $orderNumber): RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($order->user_id && $order->user_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized to reorder from this order.');
        }

        $result = $this->reorderService->reorder($order, $request->user(), session()->getId());

        $count = count($result['added']);
        $skipped = count($result['skipped']);

        $msg = "Added {$count} item(s) to your cart from order #{$orderNumber}.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} item(s) were out of stock or inactive).";
        }

        return redirect()->route('storefront.cart')->with('success', $msg);
    }

    /**
     * View wallet balance and transaction ledger.
     */
    public function wallet(Request $request): View
    {
        $user = $request->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        $query = $wallet->ledgerEntries()->orderByDesc('created_at');

        $currentType = $request->query('type', 'all');
        if (in_array($currentType, ['credit', 'debit'])) {
            $query->where('type', $currentType);
        }

        $entries = $query->paginate(15)->withQueryString();

        $totalCashbackEarned = (int) $wallet->ledgerEntries()->where('reference_type', 'cashback')->sum('amount');
        $totalSpent = (int) $wallet->ledgerEntries()->where('type', 'debit')->sum('amount');
        $totalTopups = (int) $wallet->ledgerEntries()->where('reference_type', 'topup')->sum('amount');

        return view('account.wallet', compact(
            'wallet',
            'entries',
            'currentType',
            'totalCashbackEarned',
            'totalSpent',
            'totalTopups'
        ));
    }

    /**
     * Add funds (top-up) to digital wallet.
     */
    public function topUpWallet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount_in_rupees' => 'required|numeric|min:10|max:100000',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $amountInPaise = (int) round($validated['amount_in_rupees'] * 100);
        $user = $request->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        $refId = ! empty($validated['payment_reference'])
            ? trim($validated['payment_reference'])
            : 'TOPUP-'.strtoupper(bin2hex(random_bytes(4)));

        $this->walletService->credit(
            $wallet,
            $amountInPaise,
            'topup',
            $refId,
            'Digital wallet prepaid balance top-up'
        );

        return back()->with('success', 'Successfully added ₹'.number_format($validated['amount_in_rupees'], 2).' to your digital wallet.');
    }

    /**
     * View customer saved wishlist.
     */
    public function wishlist(Request $request): View
    {
        $user = $request->user();
        $items = Wishlist::where('user_id', $user->id)
            ->with(['variant.product.primaryMedia'])
            ->orderByDesc('created_at')
            ->get();

        return view('account.wishlist', compact('items'));
    }

    /**
     * Toggle item in/out of wishlist.
     */
    public function toggleWishlist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        $userId = $request->user()->id;
        $variantId = $validated['variant_id'];

        $existing = Wishlist::where('user_id', $userId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = 'Item removed from your saved wishlist.';
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'product_variant_id' => $variantId,
                'created_at' => now(),
            ]);
            $msg = 'Item added to your saved wishlist.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Submit a customer product review.
     */
    public function submitReview(Request $request, int $productId): RedirectResponse
    {
        $reviewsEnabled = (bool) app(SettingService::class)->get('features.reviews_enabled', true);
        if (! $reviewsEnabled) {
            return back()->with('error', 'Customer reviews are currently disabled.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:150',
            'comment' => 'required|string|max:2000',
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $product = Product::findOrFail($productId);
        $userId = $request->user()->id;

        // Check if user is a verified buyer of this product
        $userOrderIds = Order::where('user_id', $userId)
            ->whereIn('status', ['confirmed', 'picking', 'packed', 'dispatched', 'out_for_delivery', 'delivered'])
            ->pluck('id');

        $isVerifiedBuyer = OrderItem::whereIn('order_id', $userOrderIds)
            ->whereHas('variant', fn ($q) => $q->where('product_id', $productId))
            ->exists();

        ProductReview::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'order_id' => $validated['order_id'] ?? null,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'is_verified_buyer' => $isVerifiedBuyer,
            'status' => 'approved',
        ]);

        return back()->with('success', 'Thank you! Your product review has been published.');
    }

    /**
     * Display customer saved delivery addresses & construction site yards.
     */
    public function addresses(Request $request): View
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('account.addresses', compact('addresses'));
    }

    /**
     * Store a new delivery or project site address.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'recipient_name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:150',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'is_site_address' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $userId = $request->user()->id;
        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            Address::where('user_id', $userId)->update(['is_default' => false]);
        }

        Address::create([
            'user_id' => $userId,
            'label' => $validated['label'] ?? null,
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => 'India',
            'pincode' => $validated['pincode'],
            'is_site_address' => $request->boolean('is_site_address'),
            'is_default' => $isDefault || (Address::where('user_id', $userId)->count() === 0),
        ]);

        return back()->with('success', 'Delivery address added successfully.');
    }

    /**
     * Delete a saved delivery address.
     */
    public function destroyAddress(Request $request, int $id): RedirectResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = Address::where('user_id', $request->user()->id)->first();
            $next?->update(['is_default' => true]);
        }

        return back()->with('success', 'Delivery address deleted successfully.');
    }

    /**
     * Set a saved address as the primary default.
     */
    public function setDefaultAddress(Request $request, int $id): RedirectResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', 'Primary default delivery address updated.');
    }

    /**
     * Display customer account profile & credentials.
     */
    public function profile(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update customer personal profile & password.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? $user->phone;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
