<?php

namespace Tests\Feature;

use App\Core\Services\SettingService;
use App\Domain\Cart\CartService;
use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\DomainConstruction\Seeders\ConstructionDomainSeeder;
use Tests\TestCase;

class StorefrontWebFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConstructionDomainSeeder::class);
    }

    public function test_storefront_home_and_catalog_render_successfully(): void
    {
        // 1. Home Page
        $homeResponse = $this->get(route('storefront.home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('UniversalEcom');
        $homeResponse->assertSee('Reference Domain Pack: Construction Materials');
        $homeResponse->assertSee('UltraTech Super PPC High Strength Cement');

        // 2. Catalog Page
        $catalogResponse = $this->get(route('storefront.catalog'));
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Materials Catalog');
        $catalogResponse->assertSee('Tata Tiscon 550D High Ductility');

        // 3. Product Details Page
        $productResponse = $this->get(route('storefront.product', 'ultratech-super-cement'));
        $productResponse->assertStatus(200);
        $productResponse->assertSee('UltraTech Super PPC High Strength Cement');
        $productResponse->assertSee('Direct Volume Pricing Tiers:');
        $productResponse->assertSee('₹450.00');
    }

    public function test_storefront_cart_operations_and_authoritative_pricing(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        // 1. Add 15 bags to cart (Tier 2 volume pricing: ₹435/bag)
        $addResponse = $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 15,
        ]);
        $addResponse->assertRedirect();
        $addResponse->assertSessionHas('success');

        // 2. View Cart Page
        $cartResponse = $this->get(route('storefront.cart'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('Shopping Cart');
        $cartResponse->assertSee('UT-PPC-50KG');

        // 3. Update Item Quantity
        $cart = app(CartService::class)->getOrCreateCart($user);
        $item = $cart->items()->firstOrFail();

        $updateResponse = $this->post(route('storefront.cart.update', $item->id), [
            'quantity' => 50, // Tier 3 pricing: ₹420/bag
        ]);
        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        // 4. Set Serviceable Location
        $locResponse = $this->post(route('storefront.set_location'), [
            'pincode' => '560002',
        ]);
        $locResponse->assertRedirect();
        $this->assertEquals('560002', session('delivery_pincode'));

        // 5. Apply Coupon Code
        $couponResponse = $this->post(route('storefront.cart.coupon'), [
            'coupon_code' => 'TESTCOUPON',
        ]);
        $couponResponse->assertRedirect();
        $this->assertEquals('TESTCOUPON', session('applied_coupon'));
    }

    public function test_storefront_checkout_places_order_and_creates_invoice(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        // Add to cart
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        // Access Checkout Page
        $checkoutResponse = $this->get(route('storefront.checkout'));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Secure Checkout');

        // Submit Checkout Form
        $orderPayload = [
            'recipient_name' => 'Vikram Builders',
            'phone' => '9845012345',
            'address_line_1' => 'Site Plot #12, Electronic City Phase 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'cod',
            'is_site_address' => 1,
            'notes' => 'Heavy vehicle entry permitted after 9 AM',
        ];

        $placeOrderResponse = $this->post(route('storefront.order.place'), $orderPayload);
        $order = Order::latest('id')->firstOrFail();

        $placeOrderResponse->assertRedirect(route('storefront.order_confirmation', $order->order_number));

        // Verify Order Confirmation View & Historical Snapshot
        $confirmationResponse = $this->get(route('storefront.order_confirmation', $order->order_number));
        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee('Order Confirmed!');
        $confirmationResponse->assertSee($order->order_number);
        $confirmationResponse->assertSee('Invoice:');
        $confirmationResponse->assertSee('Order Lines (Immutable Historical Snapshot)');
    }

    public function test_admin_dashboard_inventory_and_order_state_machine(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        // 1. Create an order to manage in admin
        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $this->post(route('storefront.order.place'), [
            'recipient_name' => 'Apex Infra Projects',
            'phone' => '9876543210',
            'address_line_1' => 'Plot 99, Tech Park Zone',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'cod',
        ]);

        $order = Order::latest('id')->firstOrFail();

        // 2. Admin Operations Dashboard (Authenticated as Administrator)
        $admin = User::where('email', 'admin@ecom-laravel.test')->firstOrFail();
        $this->actingAs($admin);

        $dashboardResponse = $this->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('System Operations Dashboard');
        $dashboardResponse->assertSee($order->order_number);
        $dashboardResponse->assertSee('Vendor Marketplace & Multi-Vendor Fulfillment');

        // 3. Admin Orders Management
        $ordersResponse = $this->get(route('admin.orders'));
        $ordersResponse->assertStatus(200);
        $ordersResponse->assertSee('Order Management & State Machine');
        $ordersResponse->assertSee($order->order_number);

        // 4. Order State Transition (confirmed -> picking)
        $transitionResponse = $this->post(route('admin.orders.transition', $order->order_number), [
            'status' => 'picking',
            'note' => 'Warehouse operator started picking items.',
        ]);
        $transitionResponse->assertRedirect();
        $order->refresh();
        $this->assertEquals('picking', $order->status);

        // 5. Warehouse Inventory Management & Manual Adjustment
        $inventoryResponse = $this->get(route('admin.inventory'));
        $inventoryResponse->assertStatus(200);
        $inventoryResponse->assertSee('Warehouse Inventory & Stock Ledger');

        $inventoryItem = InventoryItem::where('product_variant_id', $variant->id)->firstOrFail();
        $initialAvailable = $inventoryItem->available;

        $adjustResponse = $this->post(route('admin.inventory.adjust', $inventoryItem->id), [
            'quantity_change' => 50,
            'reason' => 'Physical Inbound Shipment from Depot #1',
        ]);
        $adjustResponse->assertRedirect();
        $inventoryItem->refresh();
        $this->assertEquals($initialAvailable + 50, $inventoryItem->available);

        // 6. Portable Add-on Registry Screen
        $addonsResponse = $this->get(route('admin.addons'));
        $addonsResponse->assertStatus(200);
        $addonsResponse->assertSee('Portable Add-ons & Domain Packs Registry');
        $addonsResponse->assertSee('Vendor Marketplace & Multi-Vendor Fulfillment');
        $addonsResponse->assertSee('Advanced Promotion & Discount Engine');
        $addonsResponse->assertSee('Construction Materials Domain Pack');
    }

    public function test_storefront_coupon_application_and_removal_workflow(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        // 1. Apply empty coupon -> error
        $emptyResponse = $this->post(route('storefront.cart.coupon'), [
            'coupon_code' => '',
        ]);
        $emptyResponse->assertRedirect();
        $emptyResponse->assertSessionHas('error');
        $this->assertNull(session('applied_coupon'));

        // 2. Apply valid coupon
        $applyResponse = $this->post(route('storefront.cart.coupon'), [
            'coupon_code' => 'TESTBUILD10',
        ]);
        $applyResponse->assertRedirect();
        $applyResponse->assertSessionHas('success');
        $this->assertEquals('TESTBUILD10', session('applied_coupon'));

        // 3. View Cart and verify coupon active badge and remove button exist
        $cartView = $this->get(route('storefront.cart'));
        $cartView->assertStatus(200);
        $cartView->assertSee('COUPON ACTIVE');
        $cartView->assertSee('TESTBUILD10');
        $cartView->assertSee('Remove');

        // 4. View Checkout and verify coupon is displayed
        $checkoutView = $this->get(route('storefront.checkout'));
        $checkoutView->assertStatus(200);
        $checkoutView->assertSee('TESTBUILD10');

        // 5. Remove coupon
        $removeResponse = $this->post(route('storefront.cart.coupon.remove'));
        $removeResponse->assertRedirect();
        $removeResponse->assertSessionHas('success');
        $this->assertNull(session('applied_coupon'));
    }

    public function test_minimum_order_amount_setting_enforcement_without_fatal_error(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1, // 1 bag = ₹450
        ]);

        // Set minimum order amount to ₹1,000 (well above ₹450)
        app(SettingService::class)->set('order.min_order_amount', 1000.0);

        // Visiting checkout should redirect back to cart with error message without crashing
        $checkoutResponse = $this->get(route('storefront.checkout'));
        $checkoutResponse->assertRedirect(route('storefront.cart'));
        $checkoutResponse->assertSessionHas('error');

        // Attempting to place order should also redirect back without crashing
        $placeOrderResponse = $this->post(route('storefront.order.place'), [
            'recipient_name' => 'Site Engineer',
            'phone' => '9876543210',
            'address_line_1' => 'Gate 1, Site Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'payment_gateway' => 'cod',
        ]);
        $placeOrderResponse->assertRedirect(route('storefront.cart'));
        $placeOrderResponse->assertSessionHas('error');
    }

    public function test_delivery_pincode_prefill_and_serviceability_indicator(): void
    {
        $user = User::where('email', 'contractor@acmebuild.test')->firstOrFail();
        $this->actingAs($user);

        // Reset existing addresses' default flag
        Address::where('user_id', $user->id)->update(['is_default' => false]);

        // Create default address with a known pincode
        Address::create([
            'user_id' => $user->id,
            'label' => 'Main Site HQ',
            'recipient_name' => $user->name,
            'phone' => '9876543210',
            'address_line_1' => 'Brigade Gateway',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560055',
            'is_default' => true,
        ]);

        // Create a zone with this pincode
        $zone = DeliveryZone::create([
            'name' => 'Bengaluru West Depot',
            'base_fee' => 0,
            'is_active' => true,
        ]);
        DeliveryZonePincode::create([
            'delivery_zone_id' => $zone->id,
            'pincode' => '560055',
        ]);

        // Clear session pincode
        session()->forget('delivery_pincode');

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        // Cart should auto-detect 560055 from user's default address and show serviceable badge
        $cartResponse = $this->get(route('storefront.cart'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('560055');
        $cartResponse->assertSee('Serviceable');
        $this->assertEquals('560055', session('delivery_pincode'));
    }
}
