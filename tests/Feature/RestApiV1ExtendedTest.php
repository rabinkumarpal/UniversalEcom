<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxClass;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Packages\PromotionEngine\Models\Coupon;
use Packages\PromotionEngine\Models\Promotion;
use Tests\TestCase;

class RestApiV1ExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Product $product;

    protected ProductVariant $variant;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'customer@metroinfra.test',
        ]);

        $brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech', 'is_active' => true]);
        $cat = Category::create(['name' => 'Cement', 'slug' => 'cement', 'is_active' => true]);
        $tax = TaxClass::create(['name' => 'GST 18%', 'code' => 'GST_18', 'rate' => 1800]);

        $this->product = Product::create([
            'brand_id' => $brand->id,
            'primary_category_id' => $cat->id,
            'name' => 'UltraTech WeatherPro Cement',
            'slug' => 'ultratech-weatherpro-cement',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'tax_class_id' => $tax->id,
            'sku' => 'UT-WPRO-50KG',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'pack_size' => 1,
            'mrp' => 45000,
            'selling_price' => 41000,
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'CENTRAL-WH',
            'name' => 'Central Depot',
            'is_active' => true,
        ]);

        InventoryItem::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 500,
            'reserved' => 0,
            'available' => 500,
            'reorder_level' => 20,
        ]);

        $zone = DeliveryZone::create([
            'name' => 'Bengaluru Metro Core',
            'base_fee' => 5000,
            'is_active' => true,
        ]);

        DeliveryZonePincode::create([
            'delivery_zone_id' => $zone->id,
            'pincode' => '560001',
        ]);

        $promo = Promotion::create([
            'name' => 'Infrastructure 10% Off',
            'slug' => 'infra10-promo',
            'code' => 'INFRA10',
            'type' => 'percentage',
            'status' => 'active',
            'priority' => 1,
            'starts_at' => now()->subYear(),
            'ends_at' => now()->addYear(),
            'configuration' => ['discount_percentage' => 10],
        ]);

        Coupon::create([
            'code' => 'INFRA10',
            'promotion_id' => $promo->id,
            'max_uses' => 1000,
            'uses_count' => 0,
            'is_active' => true,
        ]);
    }

    public function test_health_check_returns_healthy_diagnostics(): void
    {
        $response = $this->get('/health');
        $response->assertOk();
        $response->assertJsonPath('status', 'healthy');
        $response->assertJsonPath('checks.database.status', 'healthy');
        $response->assertJsonPath('checks.cache.status', 'healthy');
        $response->assertJsonPath('checks.storage.status', 'healthy');

        $apiResponse = $this->get('/api/v1/health');
        $apiResponse->assertOk();
        $apiResponse->assertJsonPath('status', 'healthy');
    }

    public function test_customer_wishlist_crud_api(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Add item to wishlist
        $addResponse = $this->postJson('/api/v1/wishlist/items', [
            'product_variant_id' => $this->variant->id,
        ]);
        $addResponse->assertCreated();
        $addResponse->assertJsonPath('data.product_variant_id', $this->variant->id);

        // 2. List wishlist
        $listResponse = $this->getJson('/api/v1/wishlist');
        $listResponse->assertOk();
        $listResponse->assertJsonCount(1, 'data');
        $listResponse->assertJsonPath('data.0.variant.sku', 'UT-WPRO-50KG');

        // 3. Remove item from wishlist
        $deleteResponse = $this->deleteJson("/api/v1/wishlist/items/{$this->variant->id}");
        $deleteResponse->assertOk();

        // 4. Verify empty
        $verifyResponse = $this->getJson('/api/v1/wishlist');
        $verifyResponse->assertJsonCount(0, 'data');
    }

    public function test_customer_addresses_crud_api(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Create address
        $createResponse = $this->postJson('/api/v1/addresses', [
            'label' => 'Construction Site 4B',
            'recipient_name' => 'Project Manager Suresh',
            'phone' => '+91 9887766554',
            'address_line_1' => 'Plot 104, Outer Ring Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_default' => true,
            'is_site_address' => true,
        ]);

        $createResponse->assertCreated();
        $addressId = $createResponse->json('data.id');
        $this->assertNotNull($addressId);

        // 2. List addresses
        $listResponse = $this->getJson('/api/v1/addresses');
        $listResponse->assertOk();
        $listResponse->assertJsonCount(1, 'data');
        $listResponse->assertJsonPath('data.0.label', 'Construction Site 4B');

        // 3. Update address
        $updateResponse = $this->patchJson("/api/v1/addresses/{$addressId}", [
            'address_line_2' => 'Tower A Gate',
        ]);
        $updateResponse->assertOk();
        $this->assertEquals('Tower A Gate', $updateResponse->json('data.address_line_2'));

        // 4. Delete address
        $deleteResponse = $this->deleteJson("/api/v1/addresses/{$addressId}");
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('addresses', ['id' => $addressId]);
    }

    public function test_product_reviews_api_and_verified_buyer_badge(): void
    {
        Sanctum::actingAs($this->user);

        // Create a prior completed order to qualify as verified buyer
        $order = Order::create([
            'order_number' => 'ORD-REVIEW-001',
            'user_id' => $this->user->id,
            'status' => 'delivered',
            'payment_status' => 'captured',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'INR',
            'subtotal' => 41000,
            'grand_total' => 41000,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'sku_snapshot' => $this->variant->sku,
            'product_name_snapshot' => $this->product->name,
            'variant_name_snapshot' => $this->variant->name,
            'unit_price' => 41000,
            'quantity' => 2,
            'line_total' => 82000,
        ]);

        // Submit review
        $reviewResponse = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Excellent workability and setting time',
            'comment' => 'Used for second floor slab casting. High initial compressive strength verified.',
        ]);

        $reviewResponse->assertCreated();
        $reviewResponse->assertJsonPath('meta.is_verified_buyer', true);

        // Public reviews list
        $publicList = $this->getJson("/api/v1/products/{$this->product->slug}/reviews");
        $publicList->assertOk();
        $publicList->assertJsonPath('meta.total_reviews', 1);
        $publicList->assertJsonPath('meta.average_rating', 5);
    }

    public function test_cart_coupon_and_checkout_validation_api(): void
    {
        // 1. Add to cart
        $sessionToken = 'test-token-coupon-999';
        $addResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $this->variant->id,
                'quantity' => 10,
            ]);
        $addResponse->assertOk();

        // 2. Apply coupon
        $couponResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/cart/coupon', [
                'coupon_code' => 'INFRA10',
            ]);
        $couponResponse->assertOk();
        $couponResponse->assertJsonPath('data.coupon_code', 'INFRA10');

        // 3. Validate checkout with serviceable pincode
        $validateResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/checkout/validate', [
                'pincode' => '560001',
            ]);
        $validateResponse->assertOk();
        $validateResponse->assertJsonPath('data.is_valid', true);
        $validateResponse->assertJsonPath('data.is_serviceable', true);

        // 4. Validate checkout with unserviceable pincode
        $invalidPincodeResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/checkout/validate', [
                'pincode' => '999999',
            ]);
        $invalidPincodeResponse->assertStatus(422);
        $invalidPincodeResponse->assertJsonPath('data.is_valid', false);
        $invalidPincodeResponse->assertJsonPath('data.is_serviceable', false);

        // 5. Remove coupon
        $removeResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->deleteJson('/api/v1/cart/coupon');
        $removeResponse->assertOk();
    }

    public function test_order_reorder_api(): void
    {
        Sanctum::actingAs($this->user);

        $order = Order::create([
            'order_number' => 'ORD-REORDER-123',
            'user_id' => $this->user->id,
            'status' => 'delivered',
            'payment_status' => 'captured',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'INR',
            'subtotal' => 41000,
            'grand_total' => 41000,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'sku_snapshot' => $this->variant->sku,
            'product_name_snapshot' => $this->product->name,
            'variant_name_snapshot' => $this->variant->name,
            'unit_price' => 41000,
            'quantity' => 15,
            'line_total' => 615000,
        ]);

        $reorderResponse = $this->postJson("/api/v1/orders/{$order->order_number}/reorder");
        $reorderResponse->assertOk();
        $reorderResponse->assertJsonPath('data.total_quantity', 15);
    }

    public function test_payment_creation_verification_and_idempotent_webhook(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-PAY-TEST-001',
            'user_id' => $this->user->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 50000,
            'grand_total' => 50000,
            'billing_address_snapshot' => ['city' => 'Bengaluru'],
            'shipping_address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        // 1. Create payment intent
        $createResponse = $this->postJson('/api/v1/payments/create', [
            'order_number' => $order->order_number,
            'gateway' => 'razorpay',
        ]);
        $createResponse->assertCreated();
        $paymentId = $createResponse->json('data.payment_id');
        $this->assertNotNull($paymentId);

        // 2. Verify payment
        $verifyResponse = $this->postJson("/api/v1/payments/{$paymentId}/verify", [
            'transaction_id' => 'pay_rzp_test_123456',
            'status' => 'captured',
        ]);
        $verifyResponse->assertOk();
        $this->assertEquals('captured', $order->fresh()->payment_status);
        $this->assertEquals('paid', $order->fresh()->status);

        // 3. Asynchronous Webhook with HMAC signature
        $secret = 'universal_ecommerce_webhook_secret';
        $webhookData = [
            'event' => 'payment.captured',
            'transaction_id' => 'pay_webhook_999999',
            'order_number' => $order->order_number,
        ];
        $payload = json_encode($webhookData);
        $signature = hash_hmac('sha256', $payload, $secret);

        $webhookResponse = $this->call(
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

        $webhookResponse->assertOk();
        $webhookResponse->assertJsonPath('status', 'success');

        // 4. Idempotent re-execution of same webhook
        $repeatWebhookResponse = $this->call(
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

        $repeatWebhookResponse->assertOk();
        $repeatWebhookResponse->assertJsonPath('status', 'idempotent_ok');
    }
}
