<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Packages\PromotionEngine\Models\Promotion;
use Tests\TestCase;

class CustomerAccountAndPromotionsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->customer = User::factory()->create([
            'name' => 'Kiran Builder',
            'email' => 'kiran@builder.test',
            'phone' => '+91 9876543210',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->admin = User::factory()->create([
            'email' => 'marketing.admin@universal-ecom.test',
        ]);
        $this->admin->roles()->attach($adminRole);
    }

    public function test_authenticated_customer_can_view_and_create_delivery_addresses(): void
    {
        // 1. Visit addresses screen
        $response = $this->actingAs($this->customer)->get(route('account.addresses'));
        $response->assertOk();
        $response->assertSee('Delivery Addresses &amp; Project Sites', false);

        // 2. Store new site yard address
        $storeResponse = $this->actingAs($this->customer)->post(route('account.addresses.store'), [
            'label' => 'Whitefield Construction Gate 3',
            'recipient_name' => 'Suresh Site Incharge',
            'phone' => '+91 9123456789',
            'address_line_1' => 'Plot 45, EPIP Industrial Zone',
            'address_line_2' => 'Behind IT Park Phase 2',
            'landmark' => 'Near Water Tower',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560066',
            'is_site_address' => '1',
            'is_default' => '1',
        ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('success');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $this->customer->id,
            'label' => 'Whitefield Construction Gate 3',
            'recipient_name' => 'Suresh Site Incharge',
            'is_site_address' => true,
            'is_default' => true,
        ]);
    }

    public function test_customer_can_set_default_address_and_delete_address(): void
    {
        $addr1 = Address::create([
            'user_id' => $this->customer->id,
            'label' => 'Primary Depot',
            'recipient_name' => 'Kiran Depo Lead',
            'phone' => '+91 9876543210',
            'address_line_1' => '100 Ring Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'country' => 'India',
            'pincode' => '560001',
            'is_default' => true,
        ]);

        $addr2 = Address::create([
            'user_id' => $this->customer->id,
            'label' => 'Subsite B',
            'recipient_name' => 'Subsite Manager',
            'phone' => '+91 9876543211',
            'address_line_1' => '200 Bannerghatta Rd',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'country' => 'India',
            'pincode' => '560076',
            'is_default' => false,
        ]);

        // 1. Set Address 2 as default
        $defaultResponse = $this->actingAs($this->customer)->post(route('account.addresses.default', $addr2->id));
        $defaultResponse->assertRedirect();

        $this->assertTrue($addr2->refresh()->is_default);
        $this->assertFalse($addr1->refresh()->is_default);

        // 2. Delete Address 2 -> Address 1 automatically promoted to default
        $deleteResponse = $this->actingAs($this->customer)->delete(route('account.addresses.destroy', $addr2->id));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('addresses', ['id' => $addr2->id]);
        $this->assertTrue($addr1->refresh()->is_default);
    }

    public function test_customer_can_view_and_update_profile_and_change_password(): void
    {
        // 1. View profile screen
        $viewResponse = $this->actingAs($this->customer)->get(route('account.profile'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Profile &amp; Account Settings', false);
        $viewResponse->assertSee($this->customer->email);

        // 2. Update profile name and phone and password
        $updateResponse = $this->actingAs($this->customer)->post(route('account.profile.update'), [
            'name' => 'Kiran Executive Contractor',
            'phone' => '+91 9998887776',
            'current_password' => 'SecretPass123!',
            'password' => 'NewSecurePassword888!',
            'password_confirmation' => 'NewSecurePassword888!',
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals('Kiran Executive Contractor', $this->customer->name);
        $this->assertEquals('+91 9998887776', $this->customer->phone);
        $this->assertTrue(Hash::check('NewSecurePassword888!', $this->customer->password));
    }

    public function test_checkout_displays_saved_addresses_for_authenticated_customer(): void
    {
        $category = Category::create(['name' => 'Masonry', 'slug' => 'masonry']);
        $brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Super Cement Bag',
            'slug' => 'super-cement-bag',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SUP-CEM-50',
            'name' => '50kg',
            'mrp' => 50000,
            'selling_price' => 45000,
            'status' => 'active',
        ]);

        Address::create([
            'user_id' => $this->customer->id,
            'label' => 'Pre-Saved Project Site',
            'recipient_name' => 'Site Receiver John',
            'phone' => '+91 9876543210',
            'address_line_1' => 'Plot 101 Outer Ring Rd',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'country' => 'India',
            'pincode' => '560001',
            'is_site_address' => true,
            'is_default' => true,
        ]);

        // Put an item in customer's cart
        $cart = Cart::create([
            'user_id' => $this->customer->id,
            'session_token' => 'test-session-cart-123',
            'status' => 'active',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $checkoutResponse = $this->actingAs($this->customer)->get(route('storefront.checkout'));
        $checkoutResponse->assertOk();
        $checkoutResponse->assertViewHas('savedAddresses');
        $checkoutResponse->assertSee('Pre-Saved Project Site');
        $checkoutResponse->assertSee('Plot 101 Outer Ring Rd');
        $checkoutResponse->assertSee('selectSavedAddress', false);
    }

    public function test_admin_can_search_promotions_and_quick_toggle_status(): void
    {
        $promo1 = Promotion::create([
            'name' => 'Festival Cement Discount 10%',
            'slug' => 'festival-cement-discount',
            'code' => 'CEMENT10',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 10,
            'stackable' => true,
        ]);

        $promo2 = Promotion::create([
            'name' => 'Steel Rod Bulk Discount 15%',
            'slug' => 'steel-bulk-discount',
            'code' => 'STEEL15',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 20,
            'stackable' => false,
        ]);

        // 1. Search by coupon code 'CEMENT10'
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.promotions.index', ['search' => 'CEMENT10']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Festival Cement Discount 10%');
        $searchResponse->assertSee('CEMENT10');
        $searchResponse->assertDontSee('STEEL15');

        // 2. Quick toggle promo1 from active to paused
        $toggleResponse = $this->actingAs($this->admin)->post(route('admin.promotions.toggle-status', $promo1->id));
        $toggleResponse->assertRedirect();
        $toggleResponse->assertSessionHas('success');

        $this->assertEquals('paused', $promo1->refresh()->status);

        // 3. Quick toggle promo1 back to active
        $toggleResponse2 = $this->actingAs($this->admin)->post(route('admin.promotions.toggle-status', $promo1->id));
        $toggleResponse2->assertRedirect();

        $this->assertEquals('active', $promo1->refresh()->status);
    }

    public function test_customer_orders_view_displays_clickable_product_links_order_tracking_and_review_flow(): void
    {
        $category = Category::create(['name' => 'Roofing Solutions', 'slug' => 'roofing-solutions']);
        $brand = Brand::create(['name' => 'Tata Steel', 'slug' => 'tata-steel']);

        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Tata Colorpon Roofing Sheet',
            'slug' => 'tata-colorpon-roofing-sheet',
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TATA-ROOF-BLUE-10FT',
            'name' => '10 Feet Royal Blue',
            'mrp' => 85000,
            'selling_price' => 75000,
            'status' => 'active',
        ]);

        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_number' => 'ORD-REV-2026-001',
            'status' => 'delivered',
            'payment_status' => 'captured',
            'subtotal' => 150000,
            'discount' => 0,
            'tax' => 27000,
            'shipping_fee' => 0,
            'grand_total' => 177000,
            'currency' => 'INR',
            'shipping_address_snapshot' => [
                'recipient_name' => 'Kiran Builder',
                'phone' => '9876543210',
                'address_line_1' => 'Plot 45',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
            'billing_address_snapshot' => [
                'recipient_name' => 'Kiran Builder',
                'phone' => '9876543210',
                'address_line_1' => 'Plot 45',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_price' => 75000,
            'quantity' => 2,
            'discount' => 0,
            'tax' => 27000,
            'line_total' => 177000,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-990011',
            'status' => 'delivered',
            'carrier_or_driver_name' => 'FastLogistics Truck #4',
            'tracking_number' => 'AWB-FL-998877',
            'dispatched_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);

        // 1. Visit account orders page
        $response = $this->actingAs($this->customer)->get(route('account.orders'));
        $response->assertOk();
        $response->assertSee('ORD-REV-2026-001');
        $response->assertSee('delivered');
        $response->assertSee('Tata Colorpon Roofing Sheet');
        $response->assertSee('TATA-ROOF-BLUE-10FT');
        $response->assertSee(route('storefront.product', $product->slug));
        $response->assertSee(route('storefront.order_confirmation', $order->order_number));
        $response->assertSee('Dispatch #SHP-990011');
        $response->assertSee('Write a Product Review');

        // 2. Submit a verified review for this delivered product
        $reviewResp = $this->actingAs($this->customer)->post(route('account.product.review', $product->id), [
            'order_id' => $order->id,
            'rating' => 5,
            'title' => 'Sturdy weatherproofing sheets',
            'comment' => 'Installed on warehouse shed roof. Withstands high winds perfectly.',
        ]);
        $reviewResp->assertRedirect();
        $reviewResp->assertSessionHas('success');

        // 3. Re-visit orders page - should now show 'Reviewed (5 / 5)' instead of review form button
        $responseAfterReview = $this->actingAs($this->customer)->get(route('account.orders'));
        $responseAfterReview->assertOk();
        $responseAfterReview->assertSee('Reviewed (5 / 5)');
    }
}
