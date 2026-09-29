<?php

namespace Tests\Unit;

use App\Domain\Pricing\PricingPipeline;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuantityPriceTier;
use App\Models\TaxClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_authoritative_quantity_tier_pricing_calculation(): void
    {
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $taxClass = TaxClass::create(['name' => 'GST 18%', 'rate_percentage' => 18.0]);

        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Universal Widget',
            'slug' => 'universal-widget',
            'status' => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $taxClass->id,
            'sku' => 'WIDGET-01',
            'name' => 'Standard Pack',
            'unit' => 'piece',
            'pack_size' => '1',
            'mrp' => 50000, // ₹500.00
            'selling_price' => 45000, // ₹450.00 (1-9 pcs)
            'status' => 'active',
        ]);

        // Tiers: 10-49 @ 42000 (₹420), 50+ @ 40000 (₹400)
        QuantityPriceTier::create([
            'product_variant_id' => $variant->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'unit_price' => 42000,
        ]);
        QuantityPriceTier::create([
            'product_variant_id' => $variant->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'unit_price' => 40000,
        ]);

        // Cart with 1 unit -> ₹450
        $cart1 = Cart::create(['session_token' => 'test-1']);
        CartItem::create(['cart_id' => $cart1->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

        $pipeline = app(PricingPipeline::class);
        $result1 = $pipeline->calculate($cart1);
        $this->assertEquals(45000, $result1['subtotal']);
        $this->assertEquals(8100, $result1['tax_total']); // 18% of 45000 = 8100

        // Cart with 20 units -> 20 * 42000 = 840,000
        $cart2 = Cart::create(['session_token' => 'test-2']);
        CartItem::create(['cart_id' => $cart2->id, 'product_variant_id' => $variant->id, 'quantity' => 20]);
        $result2 = $pipeline->calculate($cart2);
        $this->assertEquals(840000, $result2['subtotal']);

        // Cart with 50 units -> 50 * 40000 = 2,000,000
        $cart3 = Cart::create(['session_token' => 'test-3']);
        CartItem::create(['cart_id' => $cart3->id, 'product_variant_id' => $variant->id, 'quantity' => 50]);
        $result3 = $pipeline->calculate($cart3);
        $this->assertEquals(2000000, $result3['subtotal']);
    }
}
