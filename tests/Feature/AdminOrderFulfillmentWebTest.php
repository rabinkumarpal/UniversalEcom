<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\DomainConstruction\Seeders\ConstructionDomainSeeder;
use Tests\TestCase;

class AdminOrderFulfillmentWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ConstructionDomainSeeder::class);

        $this->admin = User::where('email', 'admin@ecom-laravel.test')->firstOrFail();

        $variant = ProductVariant::where('sku', 'UT-PPC-50KG')->firstOrFail();

        $this->order = Order::create([
            'order_number' => 'ORD-TEST-9901',
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 90000,
            'discount_total' => 0,
            'tax_total' => 16200,
            'delivery_fee' => 5000,
            'grand_total' => 111200,
            'shipping_address_snapshot' => [
                'recipient_name' => 'Brigade Project Site Engineer',
                'phone' => '9888877777',
                'address_line_1' => 'Plot 44, Industrial Logistics Corridor',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560055',
                'is_site_address' => true,
            ],
            'billing_address_snapshot' => [
                'recipient_name' => 'Brigade Enterprises Ltd',
                'address_line_1' => 'Suite 101, Brigade Tower',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560055',
            ],
            'notes' => 'Deliver to Gate 3 near the tower crane. Heavy vehicle clearance required.',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $variant->product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_price' => 45000,
            'quantity' => 2,
            'discount' => 0,
            'tax' => 8100,
            'line_total' => 90000,
        ]);
    }

    public function test_admin_orders_index_shows_pick_slip_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders'));
        $response->assertOk();
        $response->assertSee($this->order->order_number);
        $response->assertSee('Pick Slip');
    }

    public function test_order_details_shows_visual_progression_stepper_and_quick_action(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->order_number));
        $response->assertOk();

        // 1. Verify Stepper Milestones
        $response->assertSee('Fulfillment Pipeline &amp; Lifecycle Stepper', false);
        $response->assertSee('Step 01');
        $response->assertSee('Order Placed');
        $response->assertSee('Step 02');
        $response->assertSee('Confirmed');
        $response->assertSee('Step 03');
        $response->assertSee('Pick &amp; Pack', false);
        $response->assertSee('Step 04');
        $response->assertSee('Dispatched');
        $response->assertSee('Step 05');
        $response->assertSee('Delivered');

        // 2. Verify Quick Action Button for confirmed status
        $response->assertSee('Start Warehouse Picking');

        // 3. Verify Pick Slip Button in header
        $response->assertSee('Warehouse Pick &amp; Pack Slip', false);
    }

    public function test_quick_action_transitions_order_state_and_updates_stepper(): void
    {
        // Transition from confirmed -> picking via contextual action
        $response = $this->actingAs($this->admin)->post(route('admin.orders.transition', $this->order->order_number), [
            'status' => 'picking',
            'note' => 'Operator picked physical pallet from Depot #1',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->order->refresh();
        $this->assertEquals('picking', $this->order->status);

        // Next view should show next action "Mark Order Packed"
        $showResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->order_number));
        $showResponse->assertOk();
        $showResponse->assertSee('Mark Order Packed');
    }

    public function test_warehouse_pick_and_pack_slip_renders_printable_document(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.packing_slip', $this->order->order_number));
        $response->assertOk();

        // Document headers
        $response->assertSee('WAREHOUSE PICK &amp; PACK SLIP', false);
        $response->assertSee($this->order->order_number);
        $response->assertSee('Print Packing Slip');

        // Shipping destination & gate notes
        $response->assertSee('Brigade Project Site Engineer');
        $response->assertSee('Heavy Vehicle / Project Site Gate');
        $response->assertSee('Deliver to Gate 3 near the tower crane');

        // Items and quantities
        $response->assertSee('UT-PPC-50KG');
        $response->assertSee('UltraTech Super PPC High Strength Cement');
        $response->assertSee('2 Units');

        // Sign-off verification sections
        $response->assertSee('Warehouse Picker');
        $response->assertSee('QC &amp; Packing Specialist', false);
        $response->assertSee('Fleet Dispatch / Gate Exit');
    }

    public function test_cancelled_order_displays_terminal_banner_in_stepper(): void
    {
        // Transition to cancelled
        $this->actingAs($this->admin)->post(route('admin.orders.transition', $this->order->order_number), [
            'status' => 'cancelled',
            'note' => 'Client requested cancellation due to weather delay.',
        ]);

        $this->order->refresh();
        $this->assertEquals('cancelled', $this->order->status);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->order_number));
        $response->assertOk();
        $response->assertSee('Order Cancelled');
        $response->assertSee('Client requested cancellation due to weather delay.');
        $response->assertSee('Terminal State');
    }
}
