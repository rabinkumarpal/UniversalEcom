<?php

namespace Tests\Feature;

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

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_api_catalog_discovery_and_serviceability(): void
    {
        $cat = Category::create(['name' => 'Electrical', 'slug' => 'electrical', 'status' => 'active']);
        $brand = Brand::create(['name' => 'Schneider', 'slug' => 'schneider', 'status' => 'active']);
        $prod = Product::create([
            'primary_category_id' => $cat->id,
            'brand_id' => $brand->id,
            'name' => 'Schneider Modular Switch',
            'slug' => 'schneider-modular-switch',
            'status' => 'published',
        ]);
        ProductVariant::create([
            'product_id' => $prod->id,
            'sku' => 'SW-MOD-01',
            'name' => '10A 1-Way Switch',
            'mrp' => 15000,
            'selling_price' => 12000,
            'status' => 'active',
        ]);

        $zone = DeliveryZone::create(['name' => 'Metro', 'base_fee' => 1000]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);

        // Test Category Listing
        $catResponse = $this->getJson('/api/v1/categories');
        $catResponse->assertStatus(200);
        $catResponse->assertJsonFragment(['slug' => 'electrical']);

        // Test Products Listing
        $prodResponse = $this->getJson('/api/v1/products?category=electrical');
        $prodResponse->assertStatus(200);
        $prodResponse->assertJsonFragment(['slug' => 'schneider-modular-switch']);

        // Test Product Detail
        $detailResponse = $this->getJson('/api/v1/products/schneider-modular-switch');
        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonFragment(['sku' => 'SW-MOD-01']);

        // Test Serviceability
        $servResponse = $this->getJson('/api/v1/delivery/serviceability?pincode=560001');
        $servResponse->assertStatus(200);
        $servResponse->assertJsonPath('data.is_serviceable', true);

        $unservResponse = $this->getJson('/api/v1/delivery/serviceability?pincode=999999');
        $unservResponse->assertStatus(200);
        $unservResponse->assertJsonPath('data.is_serviceable', false);
    }

    public function test_api_cart_checkout_and_authenticated_order_tracking(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);
        $zone = DeliveryZone::create(['name' => 'Metro', 'base_fee' => 2000, 'min_order_free_shipping' => 50000]);
        DeliveryZonePincode::create(['delivery_zone_id' => $zone->id, 'pincode' => '560001']);

        $cat = Category::create(['name' => 'Lighting', 'slug' => 'lighting', 'status' => 'active']);
        $prod = Product::create([
            'primary_category_id' => $cat->id,
            'name' => 'LED Ceiling Panel 15W',
            'slug' => 'led-ceiling-panel-15w',
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $prod->id,
            'sku' => 'LED-15W-SQ',
            'name' => 'Square Warm White',
            'mrp' => 80000,
            'selling_price' => 60000,
            'status' => 'active',
        ]);

        $wh = Warehouse::create(['code' => 'WH-API', 'name' => 'API Hub', 'is_active' => true]);
        InventoryItem::create([
            'warehouse_id' => $wh->id,
            'product_variant_id' => $variant->id,
            'on_hand' => 20,
            'available' => 20,
        ]);

        $sessionToken = 'test-token-xyz';

        // 1. Add item to cart
        $addResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);
        $addResponse->assertStatus(200);
        $addResponse->assertJsonPath('data.total_quantity', 2);
        $addResponse->assertJsonPath('data.subtotal', 120000); // 2 * 60,000 = 120,000

        // 2. Checkout quote
        $quoteResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->postJson('/api/v1/checkout/quote', [
                'pincode' => '560001',
            ]);
        $quoteResponse->assertStatus(200);
        $quoteResponse->assertJsonPath('data.subtotal', 120000);
        $quoteResponse->assertJsonPath('data.delivery_fee', 0); // Free shipping because 120,000 >= 50,000

        // 3. Place order via API
        $orderResponse = $this->withHeader('X-Session-Token', $sessionToken)
            ->actingAs($user)
            ->postJson('/api/v1/orders', [
                'shipping_address' => [
                    'recipient_name' => 'Jane Smith',
                    'phone' => '9876543210',
                    'address_line_1' => 'Tower A, Floor 12',
                    'city' => 'Bengaluru',
                    'state' => 'Karnataka',
                    'pincode' => '560001',
                ],
                'payment_gateway' => 'cod',
            ]);
        $orderResponse->assertStatus(201);
        $orderNumber = $orderResponse->json('meta.order_number');
        $this->assertNotEmpty($orderNumber);

        // 4. Track order via API
        $trackResponse = $this->actingAs($user)->getJson("/api/v1/orders/{$orderNumber}");
        $trackResponse->assertStatus(200);
        $trackResponse->assertJsonPath('data.order_number', $orderNumber);
        $trackResponse->assertJsonPath('data.grand_total', 120000);
    }
}
