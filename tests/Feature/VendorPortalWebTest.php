<?php

namespace Tests\Feature;

use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOffer;
use Packages\VendorMarketplace\Models\VendorOrder;
use Packages\VendorMarketplace\Models\VendorPayout;
use Packages\VendorMarketplace\Models\VendorUser;
use Tests\TestCase;

class VendorPortalWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->adminUser = User::factory()->create([
            'email' => 'admin@universal-ecom.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);
    }

    public function test_unauthenticated_user_accessing_vendor_dashboard_redirects_to_vendor_login(): void
    {
        $response = $this->get(route('vendor.dashboard'));
        $response->assertRedirect(route('vendor.login'));
    }

    public function test_vendor_registration_onboarding_creates_pending_vendor(): void
    {
        $response = $this->post(route('vendor.register'), [
            'company_name' => 'Delta Cement Works Ltd',
            'display_name' => 'Delta Cement Supplies',
            'name' => 'Amit Verma',
            'email' => 'amit@deltacement.com',
            'phone' => '+91 9988776655',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('vendor.dashboard'));

        $vendor = Vendor::where('email', 'amit@deltacement.com')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals('pending', $vendor->approval_status);
        $this->assertEquals('pending', $vendor->status);

        // While pending, accessing vendor dashboard renders application under review (HTTP 403)
        $dashResponse = $this->get(route('vendor.dashboard'));
        $dashResponse->assertStatus(403);
        $dashResponse->assertSee('Application Under Review');
    }

    public function test_admin_approves_vendor_and_updates_commission(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Apex Steel Corp',
            'display_name' => 'Apex Steel Direct',
            'slug' => 'apex-steel',
            'email' => 'sales@apexsteel.com',
            'phone' => '9876543210',
            'status' => 'pending',
            'approval_status' => 'pending',
            'commission_rate_percentage' => 10.00,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.marketplace.vendors.status', $vendor->id), [
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 8.50,
        ]);

        $response->assertRedirect();
        $vendor->refresh();

        $this->assertEquals('active', $vendor->status);
        $this->assertEquals('approved', $vendor->approval_status);
        $this->assertEquals(8.50, $vendor->commission_rate_percentage);
        $this->assertNotNull($vendor->approved_at);
    }

    public function test_vendor_login_and_dashboard_metrics(): void
    {
        $user = User::factory()->create(['email' => 'merchant@universal.test', 'password' => bcrypt('password123')]);
        $vendor = Vendor::create([
            'legal_name' => 'Universal Merchant Corp',
            'display_name' => 'Universal Merchant',
            'slug' => 'universal-merchant',
            'email' => 'merchant@universal.test',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 12.00,
        ]);
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'active']);

        $loginResponse = $this->post(route('vendor.login'), [
            'email' => 'merchant@universal.test',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect(route('vendor.dashboard'));

        $dashResponse = $this->get(route('vendor.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Universal Merchant');
        $dashResponse->assertSee('Gross Sales');
        $dashResponse->assertSee('Net Earnings');
    }

    public function test_vendor_creates_and_updates_catalog_offer(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'legal_name' => 'Prime Hardware',
            'display_name' => 'Prime Hardware',
            'slug' => 'prime-hardware',
            'email' => $user->email,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'active']);

        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $brand = Brand::create(['name' => 'ToolCraft', 'slug' => 'toolcraft']);
        $product = Product::create(['primary_category_id' => $category->id, 'brand_id' => $brand->id, 'name' => 'Cordless Drill', 'slug' => 'cordless-drill', 'status' => 'published']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'DRILL-01', 'name' => 'Standard', 'mrp' => 500000, 'selling_price' => 450000, 'status' => 'active']);

        $this->actingAs($user);

        // Create Offer
        $createResponse = $this->post(route('vendor.offers.store'), [
            'product_variant_id' => $variant->id,
            'vendor_price' => 4200.00, // ₹4,200.00 = 420,000 minor units
            'vendor_mrp' => 5000.00,
            'vendor_sku' => 'VEND-DRILL-01',
            'initial_stock' => 25,
        ]);
        $createResponse->assertRedirect(route('vendor.offers.index'));

        $offer = VendorOffer::where('vendor_id', $vendor->id)->where('product_variant_id', $variant->id)->first();
        $this->assertNotNull($offer);
        $this->assertEquals(420000, $offer->vendor_price);
        $this->assertEquals('VEND-DRILL-01', $offer->vendor_sku);

        // Update Offer Price
        $updateResponse = $this->post(route('vendor.offers.update', $offer->id), [
            'vendor_price' => 4150.00,
            'vendor_mrp' => 5000.00,
            'status' => 'approved',
        ]);
        $updateResponse->assertRedirect();
        $offer->refresh();
        $this->assertEquals(415000, $offer->vendor_price);
    }

    public function test_multi_vendor_order_split_and_vendor_fulfillment_workflow(): void
    {
        $vendorUser = User::factory()->create();
        $vendor = Vendor::create([
            'legal_name' => 'Zenith Bricks Ltd',
            'display_name' => 'Zenith Bricks',
            'slug' => 'zenith-bricks',
            'email' => $vendorUser->email,
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 10.00,
        ]);
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $vendorUser->id, 'role' => 'owner', 'status' => 'active']);

        $customer = User::factory()->create();
        $zone = DeliveryZone::create(['name' => 'Bangalore', 'base_fee' => 0]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);

        $address = Address::create([
            'user_id' => $customer->id,
            'recipient_name' => 'Civil Contractor',
            'phone' => '9900112233',
            'address_line_1' => 'Plot 42, Green Valley',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);

        $category = Category::create(['name' => 'Masonry', 'slug' => 'masonry']);
        $product = Product::create(['primary_category_id' => $category->id, 'name' => 'Red Clay Brick', 'slug' => 'red-clay-brick', 'status' => 'published']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'BRICK-RED', 'name' => 'Pallet of 500', 'mrp' => 800000, 'selling_price' => 700000, 'status' => 'active']);

        VendorOffer::create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id, 'vendor_price' => 700000, 'status' => 'approved']);

        $wh = Warehouse::create(['code' => 'WH-BRICK', 'name' => 'Brick Depot', 'is_active' => true]);
        InventoryItem::create(['warehouse_id' => $wh->id, 'product_variant_id' => $variant->id, 'on_hand' => 100, 'available' => 100]);

        // Customer checkout
        $cart = app(CartService::class)->getOrCreateCart($customer);
        app(CartService::class)->addItem($cart, $variant->id, 2); // 2 * 700000 = 1,400,000 cents (₹14,000)

        $order = app(CheckoutService::class)->checkout($cart, $address, $address, 'cod', 'Site delivery', $customer);

        // Verify split VendorOrder
        $vendorOrder = VendorOrder::where('vendor_id', $vendor->id)->where('order_id', $order->id)->first();
        $this->assertNotNull($vendorOrder);
        $this->assertEquals(1400000, $vendorOrder->subtotal);
        $this->assertEquals(140000, $vendorOrder->commission_amount); // 10%
        $this->assertEquals(1260000, $vendorOrder->vendor_payout); // ₹12,600.00

        // Vendor logs in and inspects the order
        $this->actingAs($vendorUser);
        $showResponse = $this->get(route('vendor.orders.show', $vendorOrder->id));
        $showResponse->assertOk();
        $showResponse->assertSee($vendorOrder->vendor_order_number);
        $showResponse->assertSee('Plot 42, Green Valley');

        // Vendor fulfills order: updates status to dispatched
        $statusResponse = $this->post(route('vendor.orders.status', $vendorOrder->id), [
            'status' => 'dispatched',
            'tracking_notes' => 'Dispatched via Truck #KA-04-1234',
        ]);
        $statusResponse->assertRedirect();
        $vendorOrder->refresh();
        $this->assertEquals('dispatched', $vendorOrder->status);
    }

    public function test_vendor_requests_payout_and_admin_settles_disbursement(): void
    {
        $vendorUser = User::factory()->create();
        $vendor = Vendor::create([
            'legal_name' => 'Electra Wholesale',
            'display_name' => 'Electra Supplies',
            'slug' => 'electra-supplies',
            'email' => $vendorUser->email,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $vendorUser->id, 'role' => 'owner', 'status' => 'active']);

        // Give vendor an earned order with ₹50,000 payout (5,000,000 minor units)
        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-TST-PAYOUT',
            'user_id' => $customer->id,
            'status' => 'delivered',
            'currency' => 'INR',
            'subtotal' => 6000000,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 6000000,
            'payment_status' => 'paid',
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru', 'address_line_1' => 'Site 1', 'recipient_name' => 'Site Manager'],
        ]);

        VendorOrder::create([
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'vendor_order_number' => 'VO-TST-PAYOUT-01',
            'status' => 'delivered',
            'subtotal' => 6000000,
            'commission_amount' => 1000000,
            'vendor_payout' => 5000000,
        ]);

        $this->actingAs($vendorUser);

        // View Payouts Ledger
        $payoutsView = $this->get(route('vendor.payouts.index'));
        $payoutsView->assertOk();
        $payoutsView->assertSee('50,000.00');

        // Request Payout
        $reqResponse = $this->post(route('vendor.payouts.request'));
        $reqResponse->assertRedirect();

        $payout = VendorPayout::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($payout);
        $this->assertEquals(5000000, $payout->amount);
        $this->assertEquals('pending', $payout->status);

        // Admin settles the payout
        $this->actingAs($this->adminUser);

        $settleResponse = $this->post(route('admin.marketplace.payouts.settle', $payout->id), [
            'payment_reference' => 'NEFT-AXIS-99887766',
        ]);
        $settleResponse->assertRedirect();

        $payout->refresh();
        $this->assertEquals('paid', $payout->status);
        $this->assertEquals('NEFT-AXIS-99887766', $payout->payment_reference);
        $this->assertNotNull($payout->paid_at);
    }

    public function test_vendor_isolation_blocks_unauthorized_vendor_access(): void
    {
        // Vendor A
        $userA = User::factory()->create();
        $vendorA = Vendor::create(['legal_name' => 'Vendor A', 'display_name' => 'Vendor A', 'slug' => 'vendor-a', 'email' => $userA->email, 'status' => 'active', 'approval_status' => 'approved']);
        VendorUser::create(['vendor_id' => $vendorA->id, 'user_id' => $userA->id, 'role' => 'owner', 'status' => 'active']);

        // Vendor B
        $userB = User::factory()->create();
        $vendorB = Vendor::create(['legal_name' => 'Vendor B', 'display_name' => 'Vendor B', 'slug' => 'vendor-b', 'email' => $userB->email, 'status' => 'active', 'approval_status' => 'approved']);
        VendorUser::create(['vendor_id' => $vendorB->id, 'user_id' => $userB->id, 'role' => 'owner', 'status' => 'active']);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-SEC-01',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal' => 10000,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 10000,
            'payment_status' => 'paid',
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru', 'address_line_1' => 'Site A', 'recipient_name' => 'Site Lead'],
        ]);
        $orderA = VendorOrder::create(['vendor_id' => $vendorA->id, 'order_id' => $order->id, 'vendor_order_number' => 'VO-A-01', 'status' => 'confirmed', 'subtotal' => 10000, 'commission_amount' => 1000, 'vendor_payout' => 9000]);

        // Vendor B tries to view Vendor A's order -> 404
        $this->actingAs($userB);
        $response = $this->get(route('vendor.orders.show', $orderA->id));
        $response->assertStatus(404);

        // Vendor B tries to transition Vendor A's order status -> 404
        $transitionResponse = $this->post(route('vendor.orders.status', $orderA->id), ['status' => 'dispatched']);
        $transitionResponse->assertStatus(404);
    }

    public function test_offer_price_guardrails_prevent_vendor_price_from_exceeding_mrp(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'legal_name' => 'Guardrail Hardware',
            'display_name' => 'Guardrail Hardware',
            'slug' => 'guardrail-hw',
            'email' => $user->email,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'active']);

        $category = Category::create(['name' => 'Safety', 'slug' => 'safety']);
        $brand = Brand::create(['name' => 'SafeGear', 'slug' => 'safegear']);
        $product = Product::create(['primary_category_id' => $category->id, 'brand_id' => $brand->id, 'name' => 'Safety Helmet', 'slug' => 'safety-helmet', 'status' => 'published']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'HELMET-01', 'name' => 'Yellow', 'mrp' => 100000, 'selling_price' => 85000, 'status' => 'active']); // MRP ₹1,000.00

        $this->actingAs($user);

        // 1. Attempt to set selling price above entered vendor MRP (e.g. Price ₹1,200, MRP ₹900)
        $resp1 = $this->from(route('vendor.offers.create'))->post(route('vendor.offers.store'), [
            'product_variant_id' => $variant->id,
            'vendor_price' => 1200.00,
            'vendor_mrp' => 900.00,
        ]);
        $resp1->assertRedirect(route('vendor.offers.create'));
        $resp1->assertSessionHasErrors('vendor_price');

        // 2. Attempt to set vendor MRP above manufacturer catalog MRP (e.g. Vendor MRP ₹1,500, Catalog MRP ₹1,000)
        $resp2 = $this->from(route('vendor.offers.create'))->post(route('vendor.offers.store'), [
            'product_variant_id' => $variant->id,
            'vendor_price' => 950.00,
            'vendor_mrp' => 1500.00,
        ]);
        $resp2->assertRedirect(route('vendor.offers.create'));
        $resp2->assertSessionHasErrors('vendor_mrp');

        // 3. Attempt to set selling price above manufacturer catalog MRP when vendor_mrp is omitted (Price ₹1,100, Catalog MRP ₹1,000)
        $resp3 = $this->from(route('vendor.offers.create'))->post(route('vendor.offers.store'), [
            'product_variant_id' => $variant->id,
            'vendor_price' => 1100.00,
        ]);
        $resp3->assertRedirect(route('vendor.offers.create'));
        $resp3->assertSessionHasErrors('vendor_price');

        // 4. Valid offer within MRP is accepted
        $validResp = $this->post(route('vendor.offers.store'), [
            'product_variant_id' => $variant->id,
            'vendor_price' => 850.00,
            'vendor_mrp' => 1000.00,
            'initial_stock' => 20,
        ]);
        $validResp->assertRedirect(route('vendor.offers.index'));
        $offer = VendorOffer::where('vendor_id', $vendor->id)->where('product_variant_id', $variant->id)->first();
        $this->assertNotNull($offer);

        // 5. Updating offer with price exceeding MRP fails guardrail
        $updateResp = $this->from(route('vendor.offers.index'))->post(route('vendor.offers.update', $offer->id), [
            'vendor_price' => 1250.00,
            'vendor_mrp' => 1000.00,
            'status' => 'approved',
        ]);
        $updateResp->assertRedirect(route('vendor.offers.index'));
        $updateResp->assertSessionHasErrors('vendor_price');
        $this->assertEquals(85000, $offer->fresh()->vendor_price);
    }

    public function test_vendor_dashboard_displays_all_four_live_kpi_cards_including_available_payout(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'legal_name' => 'Metro Cement Supplies',
            'display_name' => 'Metro Cement',
            'slug' => 'metro-cement',
            'email' => $user->email,
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 10.00,
        ]);
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'active']);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-KPI-01',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal' => 50000,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 50000,
            'payment_status' => 'paid',
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru', 'address_line_1' => 'Site 1', 'recipient_name' => 'Builder'],
        ]);

        // Total Earned: ₹450.00 (45000 minor units)
        VendorOrder::create([
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'vendor_order_number' => 'VO-KPI-01',
            'status' => 'confirmed',
            'subtotal' => 50000,
            'commission_amount' => 5000,
            'vendor_payout' => 45000,
        ]);

        // Payout Requested: ₹200.00 (20000 minor units)
        VendorPayout::create([
            'vendor_id' => $vendor->id,
            'payout_number' => 'VP-KPI-01',
            'amount' => 20000,
            'status' => 'pending',
        ]);

        // Remaining Available Payout: ₹250.00 (25000 minor units)
        $this->actingAs($user);
        $response = $this->get(route('vendor.dashboard'));

        $response->assertOk();
        $response->assertSee('Active Variant Offers');
        $response->assertSee('Pending Orders');
        $response->assertSee('Net Earnings');
        $response->assertSee('Available Payout');
        $response->assertSee('₹250.00'); // Unsettled balance
        $response->assertSee('₹450.00'); // Total net earnings
    }
}
