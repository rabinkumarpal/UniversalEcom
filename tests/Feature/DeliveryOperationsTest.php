<?php

namespace Tests\Feature;

use App\Domain\Delivery\ShipmentService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\TaxClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Order $order;

    protected ShipmentService $shipmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->adminUser = User::factory()->create([
            'email' => 'admin@universal-ecom.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech', 'is_active' => true]);
        $cat = Category::create(['name' => 'Cement', 'slug' => 'cement', 'is_active' => true]);
        $tax = TaxClass::create(['name' => 'GST 18%', 'code' => 'GST_18', 'rate' => 1800]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'primary_category_id' => $cat->id,
            'name' => 'Super Cement 53',
            'slug' => 'super-cement-53',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $tax->id,
            'sku' => 'CEMENT-53-BAG',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'pack_size' => 1,
            'mrp' => 42000,
            'selling_price' => 38000,
            'status' => 'active',
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-2026-DELIV-001',
            'user_id' => $this->adminUser->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 38000,
            'grand_total' => 38000,
            'billing_address_snapshot' => ['recipient_name' => 'Karan Mehta', 'city' => 'Bengaluru', 'pincode' => '560001'],
            'shipping_address_snapshot' => [
                'recipient_name' => 'Karan Mehta',
                'phone' => '+91 9988776655',
                'address_line_1' => 'Plot 45, Site Gate B',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_price' => 38000,
            'quantity' => 10,
            'line_total' => 380000,
        ]);

        $this->shipmentService = app(ShipmentService::class);
    }

    public function test_shipment_lifecycle_creation_and_admin_views(): void
    {
        // 1. Create shipment via domain service
        $shipment = $this->shipmentService->createShipmentForOrder($this->order, [
            'notes' => 'Heavy structural transport required',
        ]);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        // 2. Admin inspects index and show view
        $indexResponse = $this->actingAs($this->adminUser)->get(route('admin.shipments.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($shipment->shipment_number);
        $indexResponse->assertSee($this->order->order_number);

        $showResponse = $this->actingAs($this->adminUser)->get(route('admin.shipments.show', $shipment->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Fleet Dispatch');
        $showResponse->assertSee('Proof of Delivery (POD)');
    }

    public function test_driver_dispatch_updates_shipment_and_order_status(): void
    {
        $shipment = $this->shipmentService->createShipmentForOrder($this->order);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipments.dispatch', $shipment->id), [
            'carrier_or_driver_name' => 'Mahesh Yadav (Truck #KA-03-9988)',
            'driver_phone' => '+91 9123456780',
            'tracking_number' => 'TRK-2026-9988',
            'notes' => 'Dispatched from central yard',
        ]);

        $response->assertRedirect(route('admin.shipments.show', $shipment->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'out_for_delivery',
            'carrier_or_driver_name' => 'Mahesh Yadav (Truck #KA-03-9988)',
            'tracking_number' => 'TRK-2026-9988',
        ]);

        $this->assertEquals('out_for_delivery', $this->order->fresh()->status);
    }

    public function test_proof_of_delivery_recording_completes_order(): void
    {
        $shipment = $this->shipmentService->createShipmentForOrder($this->order);
        $this->shipmentService->dispatchShipment($shipment, [
            'carrier_or_driver_name' => 'Driver Arjun',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipments.pod', $shipment->id), [
            'pod_recipient_name' => 'Engineer Raghavendra',
            'pod_otp' => '654321',
            'pod_latitude' => 12.9716000,
            'pod_longitude' => 77.5946000,
            'notes' => 'Received with physical material testing report',
        ]);

        $response->assertRedirect(route('admin.shipments.show', $shipment->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'delivered',
            'pod_recipient_name' => 'Engineer Raghavendra',
            'pod_otp' => '654321',
        ]);

        $this->assertEquals('delivered', $this->order->fresh()->status);
        $this->assertEquals('fulfilled', $this->order->fresh()->fulfillment_status);
    }

    public function test_delivery_exception_marks_shipment_failed(): void
    {
        $shipment = $this->shipmentService->createShipmentForOrder($this->order);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipments.exception', $shipment->id), [
            'exception_code' => DeliveryException::CODE_PINCODE_NOT_SERVICEABLE,
            'notes' => 'Site approach road blocked by municipal construction, heavy truck entry restricted.',
        ]);

        $response->assertRedirect(route('admin.shipments.show', $shipment->id));
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('delivery_exceptions', [
            'shipment_id' => $shipment->id,
            'exception_code' => DeliveryException::CODE_PINCODE_NOT_SERVICEABLE,
        ]);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'failed',
        ]);
    }
}
