<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_xml_sitemap_returns_valid_structure_and_urls(): void
    {
        $category = Category::create(['name' => 'Cement & Aggregates', 'slug' => 'cement-aggregates']);
        $brand = Brand::create(['name' => 'UltraTech', 'slug' => 'ultratech']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Super PPC Cement 50kg',
            'slug' => 'super-ppc-cement-50kg',
            'status' => 'published',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CEM-PPC-50',
            'name' => '50kg Bag',
            'mrp' => 45000,
            'selling_price' => 38000,
            'status' => 'active',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('/catalog', $content);
        $this->assertStringContainsString('super-ppc-cement-50kg', $content);
        $this->assertStringContainsString('cement-aggregates', $content);
    }

    public function test_product_detail_page_contains_schema_org_json_ld_structured_data(): void
    {
        $category = Category::create(['name' => 'Paints', 'slug' => 'paints']);
        $brand = Brand::create(['name' => 'Asian Paints', 'slug' => 'asian-paints']);
        $product = Product::create([
            'primary_category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Apex Ultima Exterior Emulsion',
            'slug' => 'apex-ultima-exterior-emulsion',
            'description' => 'High durability weatherproof exterior paint for construction projects.',
            'status' => 'published',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'APEX-20L',
            'name' => '20L Drum',
            'mrp' => 600000,
            'selling_price' => 540000,
            'status' => 'active',
        ]);

        $response = $this->get(route('storefront.product', $product->slug));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "Product"', false);
        $response->assertSee('Apex Ultima Exterior Emulsion', false);
        $response->assertSee('APEX-20L', false);
        $response->assertSee('"priceCurrency": "INR"', false);
        $response->assertSee('"price": "5400.00"', false);
    }
}
