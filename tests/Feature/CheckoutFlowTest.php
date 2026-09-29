<?php

namespace Tests\Feature;

use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Domain\Orders\OrderStateMachine;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_cart_and_checkout_flow(): void
    {
        $user = User::factory()->create();
        $zone = DeliveryZone::create(['name' => 'City Metro', 'base_fee' => 5000, 'min_order_free_shipping' => 100000]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);

        $address = Address::create([
            'user_id' => $user->id,
            'recipient_name' => 'John Doe',
            'phone' => '9876543210',
            'address_line_1' => 'Flat 101, Sunshine Heights',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_default' => true,
        ]);

        $category = Category::create(['name' => 'Hardware', 'slug' => 'hardware']);
        $brand = Brand::create(['name' => 'Tata', 'slug' => 'tata']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Heavy Duty Screw Pack',
            'slug' => 'heavy-duty-screw-pack',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SCREW-100',
            'name' => 'Box of 100',
            'unit' => 'box',
            'pack_size' => '100',
            'mrp' => 60000,
            'selling_price' => 50000, // ₹500
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create(['code' => 'WH-TEST', 'name' => 'Hub', 'is_active' => true]);
        $inv = InventoryItem::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 50,
            'reserved' => 0,
            'available' => 50,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);

        // Add 2 boxes to cart
        $cartService->addItem($cart, $variant->id, 2);

        $this->assertEquals(2, $cart->fresh()->total_quantity);

        // Run checkout
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->checkout($cart, $address, $address, 'cod', 'Leave at door', $user);

        // Assert Order attributes
        $this->assertNotNull($order->id);
        $this->assertStringStartsWith('ORD-', $order->order_number);
        $this->assertEquals(100000, $order->subtotal); // 2 * 50000 = 100000
        $this->assertEquals(0, $order->delivery_fee); // Met min order free shipping threshold 100000
        $this->assertEquals(100000, $order->grand_total);
        $this->assertEquals('confirmed', $order->status); // COD immediately confirmed
        $this->assertEquals('pending', $order->payment_status);

        // Assert order item snapshots
        $orderItem = $order->items->first();
        $this->assertEquals('SCREW-100', $orderItem->sku_snapshot);
        $this->assertEquals('Heavy Duty Screw Pack', $orderItem->product_name_snapshot);
        $this->assertEquals('Box of 100', $orderItem->variant_name_snapshot);
        $this->assertEquals(50000, $orderItem->unit_price);
        $this->assertEquals(2, $orderItem->quantity);

        // Assert stock was consumed: on_hand was 50, now should be 48
        $inv->refresh();
        $this->assertEquals(48, $inv->on_hand);
        $this->assertEquals(48, $inv->available);

        // Test Order state machine transitions
        $stateMachine = app(OrderStateMachine::class);
        $stateMachine->transitionTo($order, 'picking', $user->id, 'Started picking');
        $this->assertEquals('picking', $order->fresh()->status);

        $stateMachine->transitionTo($order, 'packed', $user->id, 'Packed in box');
        $this->assertEquals('packed', $order->fresh()->status);
    }
}
