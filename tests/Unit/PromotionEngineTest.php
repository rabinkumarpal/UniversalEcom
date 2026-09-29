<?php

namespace Tests\Unit;

use App\Domain\Cart\CartService;
use App\Domain\Checkout\CheckoutService;
use App\Domain\Pricing\PricingPipeline;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\PromotionEngine\Models\Coupon;
use Packages\PromotionEngine\Models\Promotion;
use Packages\PromotionEngine\Models\PromotionUsage;
use Tests\TestCase;

class PromotionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentage_discount_and_spending_goal_free_shipping(): void
    {
        $category = Category::create(['name' => 'Cement', 'slug' => 'cement']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'name' => 'UltraTech PPC Cement',
            'slug' => 'ultratech-ppc-cement',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CEM-PPC-50',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'pack_size' => '50kg',
            'mrp' => 45000,
            'selling_price' => 40000, // ₹400
            'status' => 'active',
        ]);

        $cart = Cart::create(['session_token' => 'promo-test-1']);
        CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 10]); // subtotal = 400,000

        // Create 10% off promotion
        $promo = Promotion::create([
            'name' => '10% Seasonal Discount',
            'slug' => '10-percent-seasonal',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 1,
            'stackable' => true,
            'configuration' => ['discount_percentage' => 10],
        ]);

        // Create spending goal: spend >= ₹3,000 (300,000 cents) for free shipping
        $goalPromo = Promotion::create([
            'name' => 'Spend ₹3000 Get Free Delivery',
            'slug' => 'spend-3000-free-shipping',
            'type' => 'spending_goal',
            'status' => 'active',
            'priority' => 2,
            'stackable' => true,
            'configuration' => [
                'goal_amount' => 300000,
                'reward_type' => 'free_shipping',
            ],
        ]);

        $zone = DeliveryZone::create(['name' => 'City', 'base_fee' => 5000]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);
        $address = Address::create([
            'user_id' => User::factory()->create()->id,
            'recipient_name' => 'Tester',
            'phone' => '1234567890',
            'address_line_1' => 'Street 1',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '560001',
        ]);

        $pipeline = app(PricingPipeline::class);
        $result = $pipeline->calculate($cart, $address);

        // 10% of 400,000 = 40,000
        $this->assertEquals(40000, $result['discount_total']);
        // Free shipping unlocked by spending goal
        $this->assertTrue($result['free_shipping']);
        $this->assertEquals(0, $result['delivery_fee']);
        $this->assertEquals(360000, $result['grand_total']);
    }

    public function test_bogo_buy_10_get_1_free(): void
    {
        $category = Category::create(['name' => 'Pipes', 'slug' => 'pipes']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'name' => 'PVC Pipe 4 inch',
            'slug' => 'pvc-pipe-4-inch',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PIPE-4INCH',
            'name' => '10ft Length',
            'mrp' => 10000,
            'selling_price' => 10000, // ₹100
            'status' => 'active',
        ]);

        // Cart with 11 pipes
        $cart = Cart::create(['session_token' => 'bogo-cart']);
        CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 11]);

        // BOGO: buy 10 get 1 free
        Promotion::create([
            'name' => 'Buy 10 Pipes Get 1 Free',
            'slug' => 'bogo-pipes-10-1',
            'type' => 'bogo',
            'status' => 'active',
            'priority' => 1,
            'configuration' => [
                'buy_quantity' => 10,
                'get_quantity' => 1,
            ],
        ]);

        $pipeline = app(PricingPipeline::class);
        $result = $pipeline->calculate($cart);

        // 1 pipe is free = 10,000 discount
        $this->assertEquals(10000, $result['discount_total']);
        $this->assertEquals(100000, $result['grand_total']); // 110,000 - 10,000 = 100,000
    }

    public function test_coupon_validation_and_next_order_coupon_reward(): void
    {
        $user = User::factory()->create();
        $zone = DeliveryZone::create(['name' => 'Hub', 'base_fee' => 0]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);
        $address = Address::create([
            'user_id' => $user->id,
            'recipient_name' => 'Contractor Bob',
            'phone' => '9998887776',
            'address_line_1' => 'Site 4B',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);

        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'name' => 'Power Drill 650W',
            'slug' => 'power-drill-650w',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'DRILL-650',
            'name' => 'Kit with Bits',
            'mrp' => 300000,
            'selling_price' => 250000, // ₹2,500
            'status' => 'active',
        ]);

        $wh = Warehouse::create(['code' => 'WH-DRILL', 'name' => 'Depot', 'is_active' => true]);
        InventoryItem::create([
            'warehouse_id' => $wh->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 10,
            'reserved' => 0,
            'available' => 10,
        ]);

        // Promotion with Next Order Coupon Reward trigger
        $promo = Promotion::create([
            'name' => '₹500 Off Drill Promo',
            'slug' => '500-off-drill',
            'code' => 'DRILL500',
            'type' => 'fixed',
            'status' => 'active',
            'priority' => 1,
            'configuration' => [
                'fixed_amount' => 50000, // ₹500 off
                'generate_next_order_coupon' => [
                    'amount' => 20000, // ₹200 off next order
                    'valid_days' => 15,
                ],
            ],
        ]);

        Coupon::create([
            'code' => 'DRILL500',
            'promotion_id' => $promo->id,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addItem($cart, $variant->id, 1);

        // Checkout with coupon
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->checkout($cart, $address, $address, 'cod', 'Drill promo test', $user, [
            'coupon_code' => 'DRILL500',
        ]);

        $this->assertEquals(200000, $order->grand_total); // 250,000 - 50,000 = 200,000
        $this->assertEquals(50000, $order->discount_total);

        // Check PromotionUsage record
        $usage = PromotionUsage::where('order_id', $order->id)->first();
        $this->assertNotNull($usage);
        $this->assertEquals(50000, $usage->discount_amount);

        // Check that Next Order Coupon was generated for this user
        $nextCoupon = Coupon::where('user_id', $user->id)->where('code', 'like', 'NEXT-%')->first();
        $this->assertNotNull($nextCoupon);
        $this->assertTrue($nextCoupon->is_active);
    }
}
