<?php

namespace Tests\Feature;

use App\Domain\Checkout\CheckoutService;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pricing\PricingPipeline;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuantityPriceTier;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Packages\PromotionEngine\Models\Coupon;
use Packages\PromotionEngine\Models\Promotion;
use Tests\TestCase;

/**
 * Explicit verification of the 9 Mission-Critical Acceptance Invariants
 * specified in universal_ecommerce_laravel_final_docs/14_TESTING_ACCEPTANCE.md
 */
class AcceptanceCriteriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Warehouse $warehouse;

    protected TaxClass $taxClass;

    protected Brand $brand;

    protected Category $category;

    protected DeliveryZone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->customer = User::factory()->create([
            'email' => 'customer@acceptance-test.test',
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-ACCEPT',
            'name' => 'Acceptance Test Warehouse',
            'is_active' => true,
        ]);

        $this->taxClass = TaxClass::create([
            'name' => 'Standard GST 18%',
            'code' => 'GST_18',
            'rate' => 1800,
        ]);

        $this->brand = Brand::create(['name' => 'Universal Brand', 'slug' => 'universal-brand', 'is_active' => true]);
        $this->category = Category::create(['name' => 'General Materials', 'slug' => 'general-materials', 'is_active' => true]);

        // Serviceable delivery zone
        $this->zone = DeliveryZone::create([
            'name' => 'Urban Core Zone',
            'code' => 'URBAN_CORE',
            'base_rate' => 15000,
            'is_active' => true,
        ]);

        foreach (['560001', '560002', '560025', '560038', '560068'] as $pincode) {
            DeliveryZonePincode::create([
                'delivery_zone_id' => $this->zone->id,
                'pincode' => $pincode,
            ]);
        }
    }

    /**
     * 1. Price integrity:
     * Given a product price of 500, if a malicious client submits 1, the server must use the authoritative price.
     */
    public function test_01_price_integrity_rejects_client_tampered_price(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Reinforced Steel Rod',
            'slug' => 'steel-rod',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'STEEL-500',
            'name' => '12mm Rod',
            'mrp' => 60000,
            'selling_price' => 50000, // 500.00
            'status' => 'active',
        ]);

        InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 100,
            'reserved' => 0,
            'available' => 100,
        ]);

        Sanctum::actingAs($this->customer);

        // Malicious client tries to inject price: 100 (1.00) in cart add request
        $response = $this->withHeader('X-Session-Token', 'session-price-test')
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 2,
                'price' => 100, // malicious client attempt
                'unit_price' => 100,
            ]);

        $response->assertOk();

        // Check active cart in DB: server authoritative price must be 50000 (500.00), not 100!
        $cartItem = CartItem::where('product_variant_id', $variant->id)->firstOrFail();
        $this->assertEquals(50000, $cartItem->unit_price, 'Server must enforce authoritative unit price of 50000');
    }

    /**
     * 2. Quantity tier:
     * Given tiers: 1-9 = 500, 10-29 = 475, 30+ = 450; a quantity of 30 must calculate at 450/unit.
     */
    public function test_02_quantity_tier_pricing_rules(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'High Grade Concrete Block',
            'slug' => 'concrete-block',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'BLOCK-TIER',
            'name' => 'Solid 8-inch Block',
            'mrp' => 60000,
            'selling_price' => 50000, // 1-9 = 500.00
            'status' => 'active',
        ]);

        // Tier 1: 10-29 => 475.00 (47500)
        QuantityPriceTier::create([
            'product_variant_id' => $variant->id,
            'min_quantity' => 10,
            'max_quantity' => 29,
            'unit_price' => 47500,
        ]);

        // Tier 2: 30+ => 450.00 (45000)
        QuantityPriceTier::create([
            'product_variant_id' => $variant->id,
            'min_quantity' => 30,
            'max_quantity' => null,
            'unit_price' => 45000,
        ]);

        $cart = Cart::create(['user_id' => $this->customer->id, 'session_token' => Str::random(32)]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 30,
            'unit_price' => 50000,
            'total_price' => 50000 * 30,
        ]);

        /** @var PricingPipeline $pipeline */
        $pipeline = app(PricingPipeline::class);
        $result = $pipeline->calculate($cart);

        // For quantity of 30, unit rate must be 45000
        $this->assertEquals(45000 * 30, $result['subtotal'], 'Subtotal for 30 units must calculate at 450.00/unit');
    }

    /**
     * 3. Stock:
     * If available stock is 5, a checkout for 6 must fail safely.
     */
    public function test_03_stock_insufficient_fails_safely(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Limited Paint Drum',
            'slug' => 'paint-drum',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'PAINT-LIM-05',
            'name' => '20L Weathercoat',
            'mrp' => 400000,
            'selling_price' => 350000,
            'status' => 'active',
        ]);

        $inventory = InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 5,
            'reserved' => 0,
            'available' => 5,
        ]);

        $cart = Cart::create(['user_id' => $this->customer->id, 'session_token' => Str::random(32)]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 6,
            'unit_price' => 350000,
            'total_price' => 350000 * 6,
        ]);

        $address = Address::create([
            'user_id' => $this->customer->id,
            'recipient_name' => 'Acceptance Site',
            'phone' => '+91 90000 00000',
            'address_line_1' => 'Plot 42, Metro Yard',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);

        /** @var CheckoutService $checkoutService */
        $checkoutService = app(CheckoutService::class);

        $this->expectException(\RuntimeException::class);
        $checkoutService->checkout($cart, $address, $address, 'cod', 'Stock test', $this->customer);

        // Verify stock was not mutated
        $inventory->refresh();
        $this->assertEquals(5, $inventory->available);
        $this->assertEquals(0, $inventory->reserved);
    }

    /**
     * 4. Concurrent checkout:
     * Two simultaneous checkouts cannot reserve more stock than exists.
     */
    public function test_04_concurrent_checkout_prevents_overselling(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Heavy Beam',
            'slug' => 'heavy-beam',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'BEAM-CONCUR',
            'name' => 'ISMB 300 Beam',
            'mrp' => 120000,
            'selling_price' => 100000,
            'status' => 'active',
        ]);

        $inventory = InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 5,
            'reserved' => 0,
            'available' => 5,
        ]);

        /** @var InventoryService $inventoryService */
        $inventoryService = app(InventoryService::class);

        // First checkout reserves 4 units
        $inventoryService->reserveStock($variant, 4, 999, 'token-1');

        $inventory->refresh();
        $this->assertEquals(1, $inventory->available);
        $this->assertEquals(4, $inventory->reserved);

        // Second simultaneous checkout requests 2 units (only 1 available)
        $this->expectException(\RuntimeException::class);
        $inventoryService->reserveStock($variant, 2, 999, 'token-2');

        // Stock numbers remain safe
        $inventory->refresh();
        $this->assertEquals(1, $inventory->available);
        $this->assertEquals(4, $inventory->reserved);
    }

    /**
     * 5. Coupon:
     * Expired or unauthorized coupons must not apply.
     */
    public function test_05_expired_or_unauthorized_coupon_rejected(): void
    {
        $promo = Promotion::create([
            'name' => 'Expired 20% Promo',
            'slug' => 'expired-promo-test',
            'code' => 'EXPIRED2025',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 1,
            'starts_at' => now()->subMonths(6),
            'ends_at' => now()->subDay(), // Expired
            'configuration' => ['discount_percentage' => 20],
        ]);

        Coupon::create([
            'code' => 'EXPIRED2025',
            'promotion_id' => $promo->id,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'General Aggregate',
            'slug' => 'general-aggregate',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'AGG-TEST',
            'name' => '20mm Aggregate',
            'mrp' => 20000,
            'selling_price' => 15000,
            'status' => 'active',
        ]);

        InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 100,
            'reserved' => 0,
            'available' => 100,
        ]);

        Sanctum::actingAs($this->customer);

        $sessionToken = 'test-token-coupon-expired';

        // Add item to cart
        $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        // Attempt applying expired coupon
        $couponResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/cart/coupon', [
                'coupon_code' => 'EXPIRED2025',
            ]);

        $couponResponse->assertStatus(422);
    }

    /**
     * 6. Payment webhook:
     * The same webhook delivered twice must not create two payments/orders/refunds.
     */
    public function test_06_payment_webhook_idempotency(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Test Concrete Bags',
            'slug' => 'test-concrete-bags',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'WEBHOOK-SKU',
            'name' => 'Bags',
            'mrp' => 50000,
            'selling_price' => 45000,
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-WH-001',
            'user_id' => $this->customer->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'subtotal' => 45000,
            'tax_total' => 8100,
            'shipping_fee' => 15000,
            'grand_total' => 68100,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        $secret = 'universal_ecommerce_webhook_secret';
        $webhookData = [
            'event' => 'payment.captured',
            'transaction_id' => 'TXN-IDEMPOTENT-999',
            'order_number' => $order->order_number,
        ];
        $payload = json_encode($webhookData);
        $signature = hash_hmac('sha256', $payload, $secret);

        // 1st delivery
        $res1 = $this->call(
            'POST',
            '/api/v1/payments/webhook/razorpay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ],
            $payload
        );

        $res1->assertOk();

        // 2nd delivery of exact same payload
        $res2 = $this->call(
            'POST',
            '/api/v1/payments/webhook/razorpay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ],
            $payload
        );

        $res2->assertOk();

        // Assert exactly 1 transaction exists
        $transactions = Payment::where('transaction_id', 'TXN-IDEMPOTENT-999')->get();
        $this->assertCount(1, $transactions, 'Duplicate webhook must not record redundant payment transactions');
    }

    /**
     * 7. Order snapshot:
     * Changing a product title after an order is placed must not change the historical order item name.
     */
    public function test_07_order_snapshot_immutability(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Original UltraTech Cement 50kg',
            'slug' => 'ultratech-cement',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'HIST-001',
            'name' => 'Standard Bag',
            'mrp' => 50000,
            'selling_price' => 42000,
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SNAP-001',
            'user_id' => $this->customer->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 42000,
            'tax_total' => 7560,
            'grand_total' => 49560,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->name,
            'unit_price' => 42000,
            'quantity' => 1,
            'line_total' => 42000,
        ]);

        // Now mutate original catalog item
        $product->update(['name' => 'MUTATED Brand Re-branded Cement']);
        $variant->update(['name' => 'MUTATED Deluxe Pack', 'selling_price' => 99000]);

        // Historical order item record must remain unchanged
        $orderItem->refresh();
        $this->assertEquals('Original UltraTech Cement 50kg', $orderItem->product_name_snapshot);
        $this->assertEquals('Standard Bag', $orderItem->variant_name_snapshot);
        $this->assertEquals(42000, $orderItem->unit_price);
    }

    /**
     * 8. Authorization:
     * A catalog manager cannot issue a refund unless the refund permission exists.
     */
    public function test_08_authorization_refund_permission_gate(): void
    {
        $catalogManager = User::factory()->create();
        $catalogRole = Role::where('slug', 'catalog-manager')->firstOrFail();
        $catalogManager->roles()->attach($catalogRole);

        $order = Order::create([
            'order_number' => 'ORD-REF-001',
            'user_id' => $this->customer->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 10000,
            'tax_total' => 1800,
            'grand_total' => 11800,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        Sanctum::actingAs($catalogManager);

        $response = $this->postJson("/api/v1/admin/orders/{$order->order_number}/refund", [
            'amount' => 5000,
            'reason' => 'Unauthorized refund attempt',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 9. Delivery:
     * An unsupported pincode cannot proceed to a delivery slot.
     */
    public function test_09_unsupported_pincode_cannot_proceed_to_checkout(): void
    {
        $product = Product::create([
            'brand_id' => $this->brand->id,
            'primary_category_id' => $this->category->id,
            'name' => 'Site Pipe 100mm',
            'slug' => 'site-pipe',
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => 'PIPE-100',
            'name' => 'PVC 6m',
            'mrp' => 100000,
            'selling_price' => 85000,
            'status' => 'active',
        ]);

        InventoryItem::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 100,
            'reserved' => 0,
            'available' => 100,
        ]);

        Sanctum::actingAs($this->customer);

        $sessionToken = 'test-token-pincode-acceptance';

        // Add to cart
        $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        // Attempt pre-checkout validation with an unserviceable remote pincode
        $validateResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/checkout/validate', [
                'pincode' => '999999', // Unsupported pincode
            ]);

        $validateResponse->assertStatus(422);
        $validateResponse->assertJsonPath('data.is_valid', false);
        $validateResponse->assertJsonPath('data.is_serviceable', false);
    }
}
