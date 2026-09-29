<?php

namespace Tests\Feature;

use App\Core\Registry\AddonRegistry;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\TaxClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Packages\DriverLogistics\DriverLogisticsAddon;
use Packages\DriverLogistics\Models\Driver;
use Packages\DriverLogistics\Services\DriverDispatchService;
use Packages\DriverLogistics\Services\DriverPodService;
use RuntimeException;
use Tests\TestCase;

class DriverLogisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $driverUser;

    protected Driver $driver;

    protected Order $order;

    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Admin and Driver users
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        $this->adminUser = User::factory()->create([
            'name' => 'Logistics Dispatcher',
            'email' => 'admin@fleet.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->driverUser = User::factory()->create([
            'name' => 'Ramesh Driver',
            'email' => 'driver@fleet.test',
            'phone' => '9876543210',
            'password' => Hash::make('secret123'),
        ]);

        $this->driver = Driver::create([
            'user_id' => $this->driverUser->id,
            'license_number' => 'DL-KA-2026-008899',
            'vehicle_type' => 'Flatbed Truck',
            'vehicle_number' => 'KA-01-EXP-5544',
            'status' => 'active',
        ]);

        // 2. Setup Catalog, Order & Shipment
        $brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech', 'is_active' => true]);
        $cat = Category::create(['name' => 'Cement', 'slug' => 'cement', 'is_active' => true]);
        $tax = TaxClass::firstOrCreate(['name' => 'GST 18%'], ['rate_percentage' => 18.00]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'primary_category_id' => $cat->id,
            'name' => 'Portland Cement 53',
            'slug' => 'portland-cement-53',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $tax->id,
            'sku' => 'CEM-53-BAG',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'pack_size' => 1,
            'mrp' => 45000,
            'selling_price' => 41000,
            'status' => 'active',
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-2026-FLEET-001',
            'user_id' => $this->adminUser->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 410000,
            'grand_total' => 410000,
            'billing_address_snapshot' => [
                'recipient_name' => 'Gowda Constructions Billing',
                'phone' => '+91 9123456780',
                'address_line_1' => 'Site Yard #12, Electronic City Phase 2',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560100',
            ],
            'shipping_address_snapshot' => [
                'recipient_name' => 'Gowda Constructions Site A',
                'phone' => '+91 9123456780',
                'address_line_1' => 'Site Yard #12, Electronic City Phase 2',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560100',
            ],
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_price' => 41000,
            'quantity' => 10,
            'line_total' => 410000,
        ]);

        $this->shipment = Shipment::create([
            'order_id' => $this->order->id,
            'shipment_number' => 'SHP-2026-FLEET-001',
            'status' => 'pending',
        ]);
    }

    public function test_driver_logistics_addon_is_registered_in_addon_registry(): void
    {
        $registry = app(AddonRegistry::class);

        $this->assertTrue($registry->has('driver-logistics'));
        $addon = $registry->get('driver-logistics');

        $this->assertInstanceOf(DriverLogisticsAddon::class, $addon);
        $this->assertTrue($addon->isEnabled());
        $this->assertEquals('1.0.0', $addon->version());
        $this->assertEquals('Driver Fleet Management & Proof of Delivery (POD)', $addon->name());
    }

    public function test_admin_can_create_driver_profile_and_assign_vehicle(): void
    {
        $newUser = User::factory()->create([
            'name' => 'Sunil Transport',
            'email' => 'sunil@fleet.test',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.logistics.drivers.store'), [
            'user_id' => $newUser->id,
            'license_number' => 'DL-KA-2026-7788',
            'vehicle_type' => 'Heavy Tipper',
            'vehicle_number' => 'KA-04-TR-9900',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.logistics.drivers.index'));
        $this->assertDatabaseHas('drivers', [
            'user_id' => $newUser->id,
            'vehicle_number' => 'KA-04-TR-9900',
            'vehicle_type' => 'Heavy Tipper',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Driver::class, $newUser->fresh()->driver);
        $this->assertEquals('KA-04-TR-9900', $newUser->fresh()->driver->vehicle_number);
    }

    public function test_admin_can_update_driver_duty_status(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.logistics.drivers.status', $this->driver->id), [
            'status' => 'off_duty',
        ]);

        $response->assertRedirect(route('admin.logistics.drivers.index'));
        $this->assertEquals('off_duty', $this->driver->fresh()->status);
        $this->assertTrue($this->driver->fresh()->isOffDuty());
    }

    public function test_dispatch_service_assigns_driver_and_generates_six_digit_otp(): void
    {
        $dispatchService = app(DriverDispatchService::class);

        $dispatched = $dispatchService->assignDriverToShipment($this->shipment, $this->driver, [
            'notes' => 'Heavy vehicle permit cleared',
        ]);

        $this->assertEquals($this->driver->id, $dispatched->driver_id);
        $this->assertEquals('out_for_delivery', $dispatched->status);
        $this->assertNotNull($dispatched->delivery_otp);
        $this->assertEquals(6, strlen($dispatched->delivery_otp));
        $this->assertTrue(ctype_digit($dispatched->delivery_otp));
        $this->assertEquals('out_for_delivery', $this->order->fresh()->status);
    }

    public function test_driver_can_login_via_mobile_portal_with_credentials(): void
    {
        // Login with email
        $response = $this->post(route('driver.login.submit'), [
            'email' => 'driver@fleet.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('driver.dashboard'));
        $this->assertAuthenticatedAs($this->driverUser);
        $this->assertNotNull($this->driver->fresh()->last_active_at);
    }

    public function test_suspended_driver_cannot_login_to_portal(): void
    {
        $this->driver->update(['status' => 'suspended']);

        $response = $this->post(route('driver.login.submit'), [
            'email' => 'driver@fleet.test',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_driver_can_view_manifest_and_start_delivery_trip(): void
    {
        $this->shipment->update([
            'driver_id' => $this->driver->id,
            'status' => 'pending',
        ]);

        // 1. Check dashboard manifest displays drop
        $dashResponse = $this->actingAs($this->driverUser)->get(route('driver.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee($this->shipment->shipment_number);
        $dashResponse->assertSee('Gowda Constructions Site A');

        // 2. Start delivery trip
        $tripResponse = $this->actingAs($this->driverUser)->post(route('driver.shipments.start', $this->shipment->id));
        $tripResponse->assertRedirect();

        $this->assertEquals('out_for_delivery', $this->shipment->fresh()->status);
        $this->assertEquals('out_for_delivery', $this->order->fresh()->status);
    }

    public function test_driver_submits_digital_pod_with_valid_otp_signature_photo_and_gps(): void
    {
        Storage::fake('public');

        $this->shipment->update([
            'driver_id' => $this->driver->id,
            'status' => 'out_for_delivery',
            'delivery_otp' => '654321',
        ]);

        $photo = UploadedFile::fake()->image('unloading_proof.jpg', 800, 600);
        $mockSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($this->driverUser)->post(route('driver.shipments.pod', $this->shipment->id), [
            'recipient_name' => 'Suresh Site Manager',
            'otp' => '654321',
            'signature_data' => $mockSignature,
            'photo' => $photo,
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'notes' => 'Safely unloaded 10 cement bags at site warehouse B',
        ]);

        $response->assertRedirect(route('driver.dashboard'));

        $freshShipment = $this->shipment->fresh();
        $this->assertEquals('delivered', $freshShipment->status);
        $this->assertEquals('Suresh Site Manager', $freshShipment->pod_recipient_name);
        $this->assertEquals('654321', $freshShipment->pod_otp);
        $this->assertEquals($mockSignature, $freshShipment->pod_signature_data);
        $this->assertEquals(12.9716, (float) $freshShipment->pod_latitude);
        $this->assertEquals(77.5946, (float) $freshShipment->pod_longitude);
        $this->assertNotNull($freshShipment->pod_photo_path);
        Storage::disk('public')->assertExists($freshShipment->pod_photo_path);

        $this->assertEquals('delivered', $this->order->fresh()->status);
        $this->assertEquals(12.9716, $this->driver->fresh()->current_latitude);
    }

    public function test_pod_submission_fails_with_invalid_delivery_otp(): void
    {
        $this->shipment->update([
            'driver_id' => $this->driver->id,
            'status' => 'out_for_delivery',
            'delivery_otp' => '654321',
        ]);

        $podService = app(DriverPodService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Delivery OTP');

        $podService->submitProofOfDelivery($this->shipment, $this->driver, [
            'recipient_name' => 'Suresh Site Manager',
            'otp' => '999999', // Wrong OTP
        ]);
    }

    public function test_driver_can_report_on_site_delivery_exception_and_mark_shipment_failed(): void
    {
        $this->shipment->update([
            'driver_id' => $this->driver->id,
            'status' => 'out_for_delivery',
        ]);

        $response = $this->actingAs($this->driverUser)->post(route('driver.shipments.exception', $this->shipment->id), [
            'exception_code' => 'SITE_BLOCKED',
            'notes' => 'Construction site gate blocked by heavy crane. Inaccessible for truck.',
        ]);

        $response->assertRedirect(route('driver.dashboard'));

        $this->assertEquals('failed', $this->shipment->fresh()->status);
        $this->assertDatabaseHas('delivery_exceptions', [
            'shipment_id' => $this->shipment->id,
            'exception_code' => 'SITE_BLOCKED',
            'recorded_by_user_id' => $this->driverUser->id,
            'is_resolved' => false,
        ]);
    }

    public function test_driver_submits_wrong_otp_via_web_receives_graceful_redirect_with_error(): void
    {
        $this->shipment->update([
            'driver_id' => $this->driver->id,
            'status' => 'out_for_delivery',
            'delivery_otp' => '654321',
        ]);

        $response = $this->actingAs($this->driverUser)
            ->from(route('driver.shipments.show', $this->shipment->id))
            ->post(route('driver.shipments.pod', $this->shipment->id), [
                'recipient_name' => 'Suresh Site Manager',
                'otp' => '000000', // Invalid OTP
            ]);

        $response->assertRedirect(route('driver.shipments.show', $this->shipment->id));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Invalid Delivery OTP', session('error'));

        // Status must remain out_for_delivery
        $this->assertEquals('out_for_delivery', $this->shipment->fresh()->status);
    }
}
