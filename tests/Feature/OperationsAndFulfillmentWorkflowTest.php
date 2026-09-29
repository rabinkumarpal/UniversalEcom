<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\TaxClass;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\DriverLogistics\Models\Driver;
use Packages\InvoiceGst\Models\GstInvoice;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOrder;
use Packages\VendorMarketplace\Models\VendorOrderItem;
use Packages\VendorMarketplace\Models\VendorUser;
use Tests\TestCase;

class OperationsAndFulfillmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected Brand $brand;

    protected Warehouse $warehouse1;

    protected Warehouse $warehouse2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->admin = User::factory()->create([
            'email' => 'operations.lead@universal-ecom.test',
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->category = Category::create([
            'name' => 'Structural Construction',
            'slug' => 'structural-construction',
            'status' => 'active',
        ]);

        $this->brand = Brand::create([
            'name' => 'UltraTech & Tata',
            'slug' => 'ultratech-tata',
        ]);

        $this->warehouse1 = Warehouse::create([
            'code' => 'WH-BLR-01',
            'name' => 'Whitefield Central Depot',
            'address' => 'Plot #45, EPIP Zone, Whitefield, Bengaluru',
            'is_active' => true,
        ]);

        $this->warehouse2 = Warehouse::create([
            'code' => 'WH-HYD-01',
            'name' => 'Hyderabad Hub',
            'address' => 'Gachibowli Logistics Hub, Hyderabad',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_inventory_overview_with_kpi_counts(): void
    {
        $product = Product::create([
            'name' => 'TMT Steel Rebar 12mm',
            'slug' => 'tmt-steel-rebar-12mm',
            'primary_category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'published',
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TMT-FE550-12MM',
            'name' => '12mm 12m Rod',
            'mrp' => 85000,
            'selling_price' => 78000,
            'status' => 'active',
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TMT-FE550-16MM',
            'name' => '16mm 12m Rod',
            'mrp' => 110000,
            'selling_price' => 99000,
            'status' => 'active',
        ]);

        // Item 1: Healthy stock
        InventoryItem::create([
            'warehouse_id' => $this->warehouse1->id,
            'product_variant_id' => $variant1->id,
            'on_hand' => 100,
            'reserved' => 10,
            'available' => 90,
            'reorder_level' => 20,
        ]);

        // Item 2: Low stock alert (available 8 <= reorder_level 15)
        InventoryItem::create([
            'warehouse_id' => $this->warehouse1->id,
            'product_variant_id' => $variant2->id,
            'on_hand' => 10,
            'reserved' => 2,
            'available' => 8,
            'reorder_level' => 15,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.inventory'));

        $response->assertOk();
        $response->assertViewHas('inventory');
        $response->assertViewHas('totalSkus', 2);
        $response->assertViewHas('lowStockCount', 1);
        $response->assertViewHas('outOfStockCount', 0);
        $response->assertViewHas('totalOnHand', 110);
        $response->assertSee('TMT-FE550-12MM');
        $response->assertSee('TMT-FE550-16MM');
        $response->assertSee('Whitefield Central Depot');
    }

    public function test_admin_can_search_and_filter_inventory_by_status_and_warehouse(): void
    {
        $product = Product::create([
            'name' => 'OPC 53 Cement',
            'slug' => 'opc-53-cement',
            'primary_category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'published',
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CEM-OPC53-BLR',
            'name' => '50kg Bag Bangalore',
            'mrp' => 45000,
            'selling_price' => 41000,
            'status' => 'active',
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CEM-OPC53-HYD',
            'name' => '50kg Bag Hyderabad',
            'mrp' => 45000,
            'selling_price' => 41000,
            'status' => 'active',
        ]);

        // Item 1 in WH 1: Low stock
        InventoryItem::create([
            'warehouse_id' => $this->warehouse1->id,
            'product_variant_id' => $variant1->id,
            'on_hand' => 5,
            'reserved' => 0,
            'available' => 5,
            'reorder_level' => 20,
        ]);

        // Item 2 in WH 2: Healthy stock
        InventoryItem::create([
            'warehouse_id' => $this->warehouse2->id,
            'product_variant_id' => $variant2->id,
            'on_hand' => 200,
            'reserved' => 0,
            'available' => 200,
            'reorder_level' => 30,
        ]);

        // 1. Status Filter: Low Stock
        $lowStockResponse = $this->actingAs($this->admin)->get(route('admin.inventory', ['status' => 'low_stock']));
        $lowStockResponse->assertOk();
        $lowStockResponse->assertSee('CEM-OPC53-BLR');
        $lowStockResponse->assertDontSee('CEM-OPC53-HYD');

        // 2. Warehouse Filter: Hyderabad
        $whResponse = $this->actingAs($this->admin)->get(route('admin.inventory', ['warehouse_id' => $this->warehouse2->id]));
        $whResponse->assertOk();
        $whResponse->assertSee('CEM-OPC53-HYD');
        $whResponse->assertDontSee('CEM-OPC53-BLR');

        // 3. Search Query: BLR
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.inventory', ['q' => 'BLR']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('CEM-OPC53-BLR');
        $searchResponse->assertDontSee('CEM-OPC53-HYD');
    }

    public function test_admin_can_adjust_stock_quantity_and_update_reorder_level(): void
    {
        $product = Product::create([
            'name' => 'AAC Blocks 6 Inch',
            'slug' => 'aac-blocks-6-inch',
            'primary_category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'AAC-BLK-6IN',
            'name' => 'Standard Block',
            'mrp' => 8000,
            'selling_price' => 7200,
            'status' => 'active',
        ]);

        $item = InventoryItem::create([
            'warehouse_id' => $this->warehouse1->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 50,
            'reserved' => 0,
            'available' => 50,
            'reorder_level' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.inventory.adjust', $item->id), [
            'quantity_change' => 25,
            'reorder_level' => 30,
            'reason' => 'Physical stock count inbound reconciliation',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertEquals(75, $item->on_hand);
        $this->assertEquals(75, $item->available);
        $this->assertEquals(30, $item->reorder_level);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'quantity' => 25,
            'reason' => 'Physical stock count inbound reconciliation',
        ]);
    }

    public function test_admin_can_transfer_stock_between_warehouses_atomically(): void
    {
        $product = Product::create([
            'primary_category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Ultratech Super Cement 50kg',
            'slug' => 'ultratech-super-cement-50kg-transfer',
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'UT-CEMENT-TRANS-50',
            'name' => '50kg Bag',
            'mrp' => 45000,
            'selling_price' => 38000,
            'status' => 'active',
        ]);

        $sourceItem = InventoryItem::create([
            'warehouse_id' => $this->warehouse1->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 100,
            'reserved' => 10,
            'available' => 90,
            'reorder_level' => 15,
        ]);

        // Transfer 40 units from warehouse 1 to warehouse 2
        $response = $this->actingAs($this->admin)->post(route('admin.inventory.transfer', $sourceItem->id), [
            'destination_warehouse_id' => $this->warehouse2->id,
            'quantity' => 40,
            'reason' => 'Balancing site inventory for major pour',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $sourceItem->refresh();
        $this->assertEquals(60, $sourceItem->on_hand);
        $this->assertEquals(50, $sourceItem->available);
        $this->assertEquals(10, $sourceItem->reserved);

        $destItem = InventoryItem::where('warehouse_id', $this->warehouse2->id)
            ->where('product_variant_id', $variant->id)
            ->firstOrFail();

        $this->assertEquals(40, $destItem->on_hand);
        $this->assertEquals(40, $destItem->available);

        // Assert double-entry movement ledger
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $sourceItem->id,
            'type' => 'transfer_out',
            'quantity' => -40,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $destItem->id,
            'type' => 'transfer_in',
            'quantity' => 40,
        ]);
    }

    public function test_inventory_transfer_fails_when_insufficient_stock_or_same_warehouse(): void
    {
        $product = Product::create([
            'primary_category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Steel Rebar 16mm',
            'slug' => 'steel-rebar-16mm-transfer',
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'STEEL-16MM-TRANS',
            'name' => '16mm Bundle',
            'mrp' => 95000,
            'selling_price' => 85000,
            'status' => 'active',
        ]);

        $sourceItem = InventoryItem::create([
            'warehouse_id' => $this->warehouse1->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 20,
            'reserved' => 5,
            'available' => 15,
            'reorder_level' => 5,
        ]);

        // 1. Exceeds available stock (available is 15, requested is 25)
        $excessResp = $this->actingAs($this->admin)->post(route('admin.inventory.transfer', $sourceItem->id), [
            'destination_warehouse_id' => $this->warehouse2->id,
            'quantity' => 25,
            'reason' => 'Invalid excess transfer',
        ]);
        $excessResp->assertRedirect();
        $excessResp->assertSessionHas('warning');

        $this->assertEquals(20, $sourceItem->refresh()->on_hand);

        // 2. Transfer to same warehouse
        $sameWhResp = $this->actingAs($this->admin)->post(route('admin.inventory.transfer', $sourceItem->id), [
            'destination_warehouse_id' => $this->warehouse1->id,
            'quantity' => 5,
        ]);
        $sameWhResp->assertRedirect();
        $sameWhResp->assertSessionHas('warning');
    }

    public function test_admin_can_dispatch_shipment_with_assigned_fleet_driver(): void
    {
        $driverUser = User::factory()->create([
            'name' => 'Mahesh Driver',
            'phone' => '+91 9123456780',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'vehicle_number' => 'KA-04-TR-4567',
            'vehicle_type' => 'Flatbed Heavy Truck',
            'license_number' => 'DL-KA-2022-9988',
            'status' => 'active',
        ]);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-DISPATCH-991',
            'user_id' => $customer->id,
            'status' => 'packed',
            'currency' => 'INR',
            'subtotal' => 250000,
            'shipping_amount' => 0,
            'tax_amount' => 45000,
            'discount_amount' => 0,
            'grand_total' => 295000,
            'billing_address_snapshot' => ['recipient_name' => 'Site Lead', 'city' => 'Bengaluru', 'pincode' => '560066', 'address_line_1' => 'Plot 10'],
            'shipping_address_snapshot' => ['recipient_name' => 'Site Lead', 'city' => 'Bengaluru', 'pincode' => '560066', 'address_line_1' => 'Plot 10'],
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-991-A',
            'warehouse_id' => $this->warehouse1->id,
            'status' => 'packed',
        ]);

        // 1. View Shipment details screen -> availableDrivers must be present
        $showResponse = $this->actingAs($this->admin)->get(route('admin.shipments.show', $shipment->id));
        $showResponse->assertOk();
        $showResponse->assertViewHas('availableDrivers');
        $showResponse->assertSee('KA-04-TR-4567');
        $showResponse->assertSee('Mahesh Driver');

        // 2. Dispatch with assigned fleet driver
        $dispatchResponse = $this->actingAs($this->admin)->post(route('admin.shipments.dispatch', $shipment->id), [
            'driver_id' => $driver->id,
            'notes' => 'Heavy materials - gate #2 entry',
        ]);

        $dispatchResponse->assertRedirect(route('admin.shipments.show', $shipment->id));
        $dispatchResponse->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals('out_for_delivery', $shipment->status);
        $this->assertEquals($driver->id, $shipment->driver_id);
        $this->assertStringContainsString('Mahesh Driver', $shipment->carrier_or_driver_name);
        $this->assertStringContainsString('KA-04-TR-4567', $shipment->carrier_or_driver_name);
        $this->assertEquals('+91 9123456780', $shipment->driver_phone);
        $this->assertNotEmpty($shipment->delivery_otp);
        $this->assertNotNull($shipment->dispatched_at);
    }

    public function test_admin_can_generate_and_view_gst_invoice_for_order(): void
    {
        TaxClass::create(['name' => 'Standard GST 18%', 'rate_percentage' => 18.00]);

        $customer = User::factory()->create([
            'name' => 'Vikram Buildtech',
        ]);

        $product = Product::create([
            'name' => 'Structural Cement OPC',
            'slug' => 'structural-cement-opc',
            'primary_category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CEM-OPC-GEN',
            'name' => '50kg Standard Bag',
            'mrp' => 45000,
            'selling_price' => 40000,
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-INV-TEST-001',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal' => 200000,
            'shipping_amount' => 0,
            'tax_amount' => 36000,
            'discount_amount' => 0,
            'grand_total' => 236000,
            'billing_address_snapshot' => [
                'recipient_name' => 'Vikram Contractor',
                'address_line_1' => 'Plot 88 Industrial Area',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560066',
            ],
            'shipping_address_snapshot' => [
                'recipient_name' => 'Vikram Site Office',
                'address_line_1' => 'Plot 88 Industrial Area',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560066',
            ],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'sku_snapshot' => $variant->sku,
            'quantity' => 5,
            'unit_price' => 40000,
            'tax_rate' => 18.00,
            'tax_amount' => 36000,
            'discount_amount' => 0,
            'line_total' => 236000,
        ]);

        // 1. Admin generates GST Tax Invoice
        $response = $this->actingAs($this->admin)->post(route('admin.orders.invoice', $order->order_number));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gst_invoices', [
            'order_id' => $order->id,
            'status' => 'issued',
        ]);

        $invoice = GstInvoice::where('order_id', $order->id)->firstOrFail();

        // 2. Admin views generated tax invoice
        $invoiceResponse = $this->actingAs($this->admin)->get(route('account.invoices.show', $invoice->invoice_number));
        $invoiceResponse->assertOk();
        $invoiceResponse->assertSee($invoice->invoice_number);
        $invoiceResponse->assertSee('Vikram Contractor');
        $invoiceResponse->assertSee('Tax Invoice (Rule 46 of CGST Rules)');
    }

    public function test_vendor_can_view_orders_with_counts_and_print_packing_slip(): void
    {
        $vendorUser = User::factory()->create([
            'name' => 'Vendor Manager Rajesh',
            'email' => 'rajesh@cementco.test',
        ]);

        $vendor = Vendor::create([
            'legal_name' => 'Cement Co Supplies Private Limited',
            'display_name' => 'Cement Co Supplies',
            'slug' => 'cement-co-supplies',
            'email' => 'rajesh@cementco.test',
            'phone' => '+91 9888877777',
            'commission_rate_percentage' => 10.00,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        VendorUser::create([
            'vendor_id' => $vendor->id,
            'user_id' => $vendorUser->id,
            'role' => 'admin',
        ]);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-VEND-777',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal' => 100000,
            'shipping_amount' => 0,
            'tax_amount' => 18000,
            'discount_amount' => 0,
            'grand_total' => 118000,
            'billing_address_snapshot' => ['recipient_name' => 'Site Builder', 'city' => 'Bengaluru', 'pincode' => '560001', 'address_line_1' => 'MG Road 10'],
            'shipping_address_snapshot' => ['recipient_name' => 'Site Builder', 'city' => 'Bengaluru', 'pincode' => '560001', 'address_line_1' => 'MG Road 10'],
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name_snapshot' => 'Rapid Hardening Cement',
            'variant_name_snapshot' => '50kg Bag',
            'sku_snapshot' => 'RAPID-CEM-50',
            'quantity' => 10,
            'unit_price' => 10000,
            'line_total' => 100000,
        ]);

        $vendorOrder = VendorOrder::create([
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'vendor_order_number' => 'VO-ORD-VEND-777-01',
            'status' => 'confirmed',
            'subtotal' => 100000,
            'commission_amount' => 10000,
            'vendor_payout' => 90000,
        ]);

        VendorOrderItem::create([
            'vendor_order_id' => $vendorOrder->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 10,
            'unit_price' => 10000,
            'commission_amount' => 1000,
            'payout_amount' => 9000,
        ]);

        // 1. Vendor views orders index
        $indexResponse = $this->actingAs($vendorUser)->get(route('vendor.orders.index'));
        $indexResponse->assertOk();
        $indexResponse->assertViewHas('statusCounts');
        $indexResponse->assertViewHas('totalOrdersCount', 1);
        $indexResponse->assertSee('VO-ORD-VEND-777-01');

        // 2. Vendor views order show screen
        $showResponse = $this->actingAs($vendorUser)->get(route('vendor.orders.show', $vendorOrder->id));
        $showResponse->assertOk();
        $showResponse->assertSee('VO-ORD-VEND-777-01');
        $showResponse->assertSee('Print Packing Slip');

        // 3. Vendor views printable packing slip
        $packingSlipResponse = $this->actingAs($vendorUser)->get(route('vendor.orders.packing-slip', $vendorOrder->id));
        $packingSlipResponse->assertOk();
        $packingSlipResponse->assertSee('PACKING SLIP &amp; MANIFEST', false);
        $packingSlipResponse->assertSee('Cement Co Supplies');
        $packingSlipResponse->assertSee('VO-ORD-VEND-777-01');
        $packingSlipResponse->assertSee('Rapid Hardening Cement');
        $packingSlipResponse->assertSee('MG Road 10');
    }

    public function test_vendor_order_status_update_records_audit_history_on_parent_order(): void
    {
        $vendorUser = User::factory()->create([
            'name' => 'Fulfillment Supervisor',
            'email' => 'supervisor@bricks.test',
        ]);

        $vendor = Vendor::create([
            'legal_name' => 'Supreme Brickworks Private Limited',
            'display_name' => 'Supreme Brickworks',
            'slug' => 'supreme-brickworks',
            'email' => 'supervisor@bricks.test',
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        VendorUser::create([
            'vendor_id' => $vendor->id,
            'user_id' => $vendorUser->id,
            'role' => 'admin',
        ]);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-AUDIT-123',
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal' => 50000,
            'shipping_amount' => 0,
            'tax_amount' => 9000,
            'discount_amount' => 0,
            'grand_total' => 59000,
            'billing_address_snapshot' => ['recipient_name' => 'Builder', 'city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['recipient_name' => 'Builder', 'city' => 'Bengaluru'],
        ]);

        $vendorOrder = VendorOrder::create([
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'vendor_order_number' => 'VO-ORD-AUDIT-123-01',
            'status' => 'confirmed',
            'subtotal' => 50000,
            'commission_amount' => 5000,
            'vendor_payout' => 45000,
        ]);

        $response = $this->actingAs($vendorUser)->post(route('vendor.orders.status', $vendorOrder->id), [
            'status' => 'dispatched',
            'tracking_notes' => 'Loaded on Lorry KA-01-EE-8822 with tarpaulin covering',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $vendorOrder->refresh();
        $this->assertEquals('dispatched', $vendorOrder->status);

        // Verify OrderStatusHistory was appended to parent order
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'previous_status' => 'confirmed',
            'new_status' => 'dispatched',
        ]);

        $history = OrderStatusHistory::where('order_id', $order->id)->latest()->first();
        $this->assertStringContainsString('Supreme Brickworks', $history->note);
        $this->assertStringContainsString('KA-01-EE-8822', $history->note);
    }
}
