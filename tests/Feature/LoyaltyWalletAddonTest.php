<?php

namespace Tests\Feature;

use App\Domain\Cart\CartService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\DomainConstruction\Seeders\ConstructionDomainSeeder;
use Packages\LoyaltyWallet\Models\ProductReview;
use Packages\LoyaltyWallet\Models\WalletAccount;
use Packages\LoyaltyWallet\Models\Wishlist;
use Packages\LoyaltyWallet\Services\WalletService;
use RuntimeException;
use Tests\TestCase;

class LoyaltyWalletAddonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConstructionDomainSeeder::class);
    }

    public function test_wallet_creation_credit_and_debit_ledger_balance(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $walletService = app(WalletService::class);

        // 1. Initialize Wallet
        $wallet = $walletService->getOrCreateWallet($user);
        $this->assertInstanceOf(WalletAccount::class, $wallet);
        $this->assertEquals(0, $wallet->balance);

        // 2. Credit ₹500.00 (50,000 paise)
        $entry1 = $walletService->credit($wallet, 50000, 'topup', 'MANUAL-1', 'Initial deposit');
        $this->assertEquals(50000, $entry1->balance_after);
        $this->assertEquals('credit', $entry1->type);
        $this->assertEquals(50000, $wallet->refresh()->balance);

        // 3. Credit ₹250.00 (25,000 paise)
        $entry2 = $walletService->credit($wallet, 25000, 'reward', 'REW-1', 'Signup bonus');
        $this->assertEquals(75000, $entry2->balance_after);
        $this->assertEquals(75000, $wallet->refresh()->balance);

        // 4. Debit ₹300.00 (30,000 paise)
        $entry3 = $walletService->debit($wallet, 30000, 'order_payment', 'ORD-101', 'Order payment');
        $this->assertEquals(45000, $entry3->balance_after);
        $this->assertEquals('debit', $entry3->type);
        $this->assertEquals(45000, $wallet->refresh()->balance);

        // 5. Verify Insufficient Funds Prevention
        $this->expectException(RuntimeException::class);
        $walletService->debit($wallet, 100000, 'order_payment', 'ORD-102', 'Attempted overdraft');
    }

    public function test_checkout_with_wallet_payment_gateway(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $walletService = app(WalletService::class);
        $wallet = $walletService->getOrCreateWallet($user);

        // Fund customer wallet with ₹20,000.00 (2,000,000 paise)
        $walletService->credit($wallet, 2000000, 'topup', 'BANK-999', 'Prepaid construction budget');

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        // Add 5 bags to cart
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        // Access checkout page - verify wallet balance is displayed
        $checkoutResponse = $this->get(route('storefront.checkout'));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Customer Digital Wallet');
        $checkoutResponse->assertSee('₹20,000.00');

        // Submit checkout using digital wallet
        $placeOrderResponse = $this->post(route('storefront.order.place'), [
            'recipient_name' => 'Rajesh Sharma',
            'phone' => '9888877777',
            'address_line_1' => 'Plot 44, Industrial Layout',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'wallet',
        ]);

        $order = Order::latest('id')->firstOrFail();
        $placeOrderResponse->assertRedirect(route('storefront.order_confirmation', $order->order_number));

        // Verify order status and payment record
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals('captured', $order->payment_status);

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals('wallet', $payment->gateway);
        $this->assertEquals('captured', $payment->status);

        // Verify wallet was debited by exact grand total, plus 2% automatic loyalty cashback earned
        $cashbackEarned = (int) round($order->grand_total * 0.02);
        $remainingExpected = 2000000 - $order->grand_total + $cashbackEarned;
        $this->assertEquals($remainingExpected, $wallet->refresh()->balance);

        // Verify both ledger entries exist: order_payment debit and cashback credit
        $this->assertTrue($wallet->ledgerEntries()->where('reference_type', 'order_payment')->exists());
        $this->assertTrue($wallet->ledgerEntries()->where('reference_type', 'cashback')->exists());
    }

    public function test_order_placed_automatically_earns_two_percent_loyalty_cashback(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        // Add 10 bags to cart
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        $this->post(route('storefront.order.place'), [
            'recipient_name' => 'Rajesh Sharma',
            'phone' => '9888877777',
            'address_line_1' => 'Plot 44, Industrial Layout',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'cod',
        ]);

        $order = Order::latest('id')->firstOrFail();
        $expectedCashback = (int) round($order->grand_total * 0.02);

        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $this->assertEquals($expectedCashback, $wallet->balance);

        $cashbackEntry = $wallet->ledgerEntries()->where('reference_type', 'cashback')->firstOrFail();
        $this->assertEquals($expectedCashback, $cashbackEntry->amount);
        $this->assertEquals($order->order_number, $cashbackEntry->reference_id);
    }

    public function test_one_click_reorder_repopulates_cart(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        // Place initial order
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 8,
        ]);

        $this->post(route('storefront.order.place'), [
            'recipient_name' => 'Rajesh Sharma',
            'phone' => '9888877777',
            'address_line_1' => 'Plot 44, Industrial Layout',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'cod',
        ]);

        $order = Order::latest('id')->firstOrFail();

        // Trigger 1-Click Reorder
        $reorderResponse = $this->post(route('account.reorder', $order->order_number));
        $reorderResponse->assertRedirect(route('storefront.cart'));
        $reorderResponse->assertSessionHas('success');

        // Verify active cart has the reordered item
        $cart = app(CartService::class)->getOrCreateCart($user);
        $this->assertEquals(1, $cart->items->count());
        $this->assertEquals(8, $cart->items->first()->quantity);
    }

    public function test_wishlist_toggle_and_customer_view(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'TATA-TMT-12MM')->firstOrFail();

        // 1. Add to wishlist
        $toggle1 = $this->post(route('account.wishlist.toggle'), [
            'variant_id' => $variant->id,
        ]);
        $toggle1->assertRedirect();
        $toggle1->assertSessionHas('success', 'Item added to your saved wishlist.');
        $this->assertTrue(Wishlist::where('user_id', $user->id)->where('product_variant_id', $variant->id)->exists());

        // 2. View wishlist page
        $wishlistPage = $this->get(route('account.wishlist'));
        $wishlistPage->assertStatus(200);
        $wishlistPage->assertSee('TATA-TMT-12MM');

        // 3. Remove from wishlist
        $toggle2 = $this->post(route('account.wishlist.toggle'), [
            'variant_id' => $variant->id,
        ]);
        $toggle2->assertRedirect();
        $toggle2->assertSessionHas('success', 'Item removed from your saved wishlist.');
        $this->assertFalse(Wishlist::where('user_id', $user->id)->where('product_variant_id', $variant->id)->exists());
    }

    public function test_product_review_submission_with_verified_buyer_badge(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $product = Product::where('slug', 'ultratech-super-cement')->firstOrFail();
        $variant = $product->variants->firstOrFail();

        // Customer purchases product
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->post(route('storefront.order.place'), [
            'recipient_name' => 'Rajesh Sharma',
            'phone' => '9888877777',
            'address_line_1' => 'Plot 44, Industrial Layout',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'cod',
        ]);

        // Submit review
        $reviewResponse = $this->post(route('account.product.review', $product->id), [
            'rating' => 5,
            'title' => 'Top tier concrete strength',
            'comment' => 'Used for casting slab on 3rd floor. Excellent setting time and zero surface hairline cracks.',
        ]);
        $reviewResponse->assertRedirect();
        $reviewResponse->assertSessionHas('success');

        $review = ProductReview::where('product_id', $product->id)->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(5, $review->rating);
        $this->assertTrue($review->is_verified_buyer);

        // View product page - verify review and badge are displayed
        $productPage = $this->get(route('storefront.product', $product->slug));
        $productPage->assertStatus(200);
        $productPage->assertSee('Top tier concrete strength');
        $productPage->assertSee('Verified Project Purchase');
    }

    public function test_customer_can_top_up_wallet_and_filter_transaction_ledger(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        // 1. Visit wallet page
        $walletPage = $this->get(route('account.wallet'));
        $walletPage->assertStatus(200);
        $walletPage->assertSee('Customer Digital Wallet');
        $walletPage->assertSee('Add Funds to Wallet');

        // 2. Top-up wallet by ₹2,500
        $topupResponse = $this->post(route('account.wallet.topup'), [
            'amount_in_rupees' => 2500,
            'payment_reference' => 'UPI-TOPUP-998877',
        ]);
        $topupResponse->assertRedirect();
        $topupResponse->assertSessionHas('success');

        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $this->assertGreaterThanOrEqual(250000, $wallet->refresh()->balance);

        $this->assertDatabaseHas('wallet_ledger_entries', [
            'wallet_account_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 250000,
            'reference_type' => 'topup',
            'reference_id' => 'UPI-TOPUP-998877',
        ]);

        // 3. Filter ledger by 'credit'
        $creditFilterResp = $this->get(route('account.wallet', ['type' => 'credit']));
        $creditFilterResp->assertStatus(200);
        $creditFilterResp->assertSee('UPI-TOPUP-998877');
        $creditFilterResp->assertSee('+₹2,500.00');

        // 4. Filter ledger by 'debit'
        $debitFilterResp = $this->get(route('account.wallet', ['type' => 'debit']));
        $debitFilterResp->assertStatus(200);
        $debitFilterResp->assertDontSee('UPI-TOPUP-998877');
    }
}
