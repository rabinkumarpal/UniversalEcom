<?php

namespace Tests\Feature;

use App\Core\Events\OrderStatusUpdated;
use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Domain\Orders\OrderStateMachine;
use App\Models\Address;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Packages\DomainConstruction\Seeders\ConstructionDomainSeeder;
use Packages\DriverLogistics\Models\Driver;
use Packages\DriverLogistics\Services\DriverPodService;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorUser;
use Tests\TestCase;

class AdminAuthAndProductImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_accessing_admin_routes_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));

        $ordersResponse = $this->get(route('admin.orders'));
        $ordersResponse->assertRedirect(route('admin.login'));

        $inventoryResponse = $this->get(route('admin.inventory'));
        $inventoryResponse->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_screen_renders_successfully_with_demo_credentials(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
        $response->assertSee('UniversalEcom Operations');
        $response->assertSee('Administrative Control Console');
        $response->assertSee('admin@ecom-laravel.test');
        $response->assertSee('Auto-Fill');
    }

    public function test_non_admin_customer_login_attempt_to_admin_console_is_denied(): void
    {
        $customerRole = Role::where('slug', 'customer')->firstOrFail();
        $customer = User::factory()->create([
            'email' => 'contractor@example.test',
            'password' => Hash::make('password123'),
        ]);
        $customer->roles()->attach($customerRole);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'contractor@example.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_admin_can_successfully_log_in_and_is_redirected_to_dashboard(): void
    {
        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create([
            'email' => 'ops.manager@universal-ecom.test',
            'password' => Hash::make('secretpass'),
        ]);
        $admin->roles()->attach($adminRole);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'ops.manager@universal-ecom.test',
            'password' => 'secretpass',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_authenticated_admin_visiting_login_is_redirected_to_dashboard(): void
    {
        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.login'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_log_out(): void
    {
        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }

    public function test_guest_accessing_account_routes_is_redirected_to_login(): void
    {
        $response = $this->get(route('account.orders'));
        $response->assertRedirect(route('admin.login'));

        $walletResponse = $this->get(route('account.wallet'));
        $walletResponse->assertRedirect(route('admin.login'));

        $wishlistResponse = $this->get(route('account.wishlist'));
        $wishlistResponse->assertRedirect(route('admin.login'));
    }

    public function test_guest_checkout_creates_order_without_polluting_admin_user_id(): void
    {
        $this->seed(ConstructionDomainSeeder::class);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        $cart = app(CartService::class)->getOrCreateCart(null, 'guest-session-token');
        app(CartService::class)->addItem($cart, $variant->id, 1);

        $guestAddress = new Address([
            'recipient_name' => 'Independent Homeowner',
            'phone' => '9111122222',
            'address_line_1' => 'House 7, Green View Villa',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);

        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->checkout(
            $cart,
            $guestAddress,
            $guestAddress,
            'cod',
            null,
            null
        );

        // Ensure order is NOT assigned to user 1
        $this->assertNull($order->user_id);
        $this->assertEquals('Independent Homeowner', $order->shipping_address_snapshot['recipient_name']);
        $this->assertEquals('House 7, Green View Villa', $order->shipping_address_snapshot['address_line_1']);
    }

    public function test_storefront_checkout_form_does_not_contain_hardcoded_mock_strings(): void
    {
        $this->seed(ConstructionDomainSeeder::class);

        $customerRole = Role::where('slug', 'customer')->firstOrFail();
        $customer = User::factory()->create([
            'name' => 'Kiran Patel',
            'phone' => '9123456780',
            'email' => 'kiran.patel@example.test',
        ]);
        $customer->roles()->attach($customerRole);

        $this->actingAs($customer);

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();
        $this->post(route('storefront.cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->get(route('storefront.checkout'));
        $response->assertStatus(200);

        // Assert authenticated customer details are pre-filled
        $response->assertSee('value="Kiran Patel"', false);
        $response->assertSee('value="9123456780"', false);

        // Assert mock values are not baked into the form
        $response->assertDontSee('value="Rajesh Sharma"', false);
        $response->assertDontSee('value="9888877777"', false);
        $response->assertDontSee('value="Plot 44, Industrial Layout, Phase 2"', false);
    }

    public function test_order_state_machine_auto_generates_shipment_on_confirmation(): void
    {
        $user = User::factory()->create();
        $zone = DeliveryZone::create(['name' => 'Metro Zone', 'base_fee' => 0, 'min_order_free_shipping' => 0]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);

        $order = Order::create([
            'order_number' => 'ORD-TEST-AUTOSHP',
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 50000,
            'discount_total' => 0,
            'tax_total' => 0,
            'delivery_fee' => 0,
            'grand_total' => 50000,
            'billing_address_snapshot' => ['pincode' => '560001'],
            'shipping_address_snapshot' => ['pincode' => '560001'],
        ]);

        $this->assertEquals(0, $order->shipments()->count());

        $stateMachine = app(OrderStateMachine::class);
        $stateMachine->transitionTo($order, 'confirmed', $user->id, 'Confirmed order');

        $order->refresh();
        $this->assertEquals(1, $order->shipments()->count());
        $shipment = $order->shipments()->first();
        $this->assertEquals('pending', $shipment->status);
        $this->assertStringStartsWith('SHP-', $shipment->shipment_number);
    }

    public function test_driver_pod_submission_records_order_status_history_and_fires_event(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $driverRole = Role::firstOrCreate(['slug' => 'driver'], ['name' => 'Driver']);
        $driverUser = User::factory()->create();
        $driverUser->roles()->attach($driverRole);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'license_number' => 'DL-KA-2026-1122',
            'vehicle_number' => 'KA-01-LOG-1122',
            'vehicle_type' => 'Flatbed Truck',
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-POD-TEST',
            'user_id' => $driverUser->id,
            'status' => 'out_for_delivery',
            'payment_status' => 'captured',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 20000,
            'discount_total' => 0,
            'tax_total' => 0,
            'delivery_fee' => 0,
            'grand_total' => 20000,
            'billing_address_snapshot' => [],
            'shipping_address_snapshot' => [],
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-POD-TEST-1',
            'driver_id' => $driver->id,
            'status' => 'out_for_delivery',
            'delivery_otp' => '445566',
        ]);

        $podService = app(DriverPodService::class);
        $podService->submitProofOfDelivery($shipment, $driver, [
            'recipient_name' => 'Harish site manager',
            'otp' => '445566',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'notes' => 'Safely unloaded',
        ]);

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertEquals('fulfilled', $order->fulfillment_status);

        // Verify OrderStatusHistory record created
        $history = OrderStatusHistory::where('order_id', $order->id)->where('new_status', 'delivered')->first();
        $this->assertNotNull($history);
        $this->assertEquals('out_for_delivery', $history->previous_status);
        $this->assertStringContainsString('Harish site manager', $history->note);
        $this->assertEquals($driverUser->id, $history->user_id);

        // Verify OrderStatusUpdated event dispatched
        Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
            return $event->order->id === $order->id && $event->newStatus === 'delivered';
        });
    }

    public function test_vendor_offer_create_view_displays_variant_name(): void
    {
        $vendor = Vendor::create([
            'legal_name' => 'Tata Steel Distribution Ltd',
            'display_name' => 'Tata Direct',
            'slug' => 'tata-direct',
            'email' => 'tata@direct.test',
            'phone' => '9888877766',
            'status' => 'active',
            'approval_status' => 'approved',
            'commission_rate_percentage' => 5.0,
        ]);

        $vendorUser = User::factory()->create([
            'email' => 'partner@tatasteel.test',
        ]);

        VendorUser::create([
            'vendor_id' => $vendor->id,
            'user_id' => $vendorUser->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $category = Category::create(['name' => 'Steel', 'slug' => 'steel']);
        $product = Product::create([
            'name' => 'Reinforcement Steel Bar',
            'slug' => 'reinforcement-steel-bar',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'STEEL-FE500D-12MM',
            'name' => 'Fe500D Grade 12mm',
            'mrp' => 50000,
            'selling_price' => 45000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($vendorUser)->get(route('vendor.offers.create'));
        $response->assertStatus(200);
        $response->assertSee('Fe500D Grade 12mm');
        $response->assertSee('STEEL-FE500D-12MM');
    }
}
