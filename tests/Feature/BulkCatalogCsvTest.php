<?php

namespace Tests\Feature;

use App\Domain\Catalog\BulkCatalogService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BulkCatalogCsvTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected BulkCatalogService $bulkCatalogService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->adminUser = User::factory()->create([
            'email' => 'admin@universal-ecom.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->bulkCatalogService = app(BulkCatalogService::class);
    }

    public function test_sample_csv_download(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.catalog.bulk.sample'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->getContent();
        $this->assertStringContainsString('sku,product_name,variant_name,brand,category', $content);
        $this->assertStringContainsString('SKU-OPC-53-BAG', $content);
    }

    public function test_preview_and_commit_valid_csv(): void
    {
        $csvContent = implode("\n", [
            'sku,product_name,variant_name,brand,category,subcategory,description,unit,pack_size,mrp,selling_price,tax_class,stock,warehouse,grade,size,color,status',
            'TEST-CEMENT-OPC,Standard Portland Cement,50kg Bag,UltraTech,Cement,Structural,High grade cement,bag,50,450.00,410.00,GST 18%,250,Central Yard,OPC 53,50kg,Grey,active',
            'TEST-STEEL-REBAR,Thermo Mechanically Treated Rebar,16mm Bar,Kamdhenu,Steel,Reinforcement,Structural steel rod,piece,1,950.00,880.00,GST 18%,120,South Depot,Fe-500D,16mm,Metallic,active',
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('catalog.csv', $csvContent);

        // 1. Preview step
        $previewResponse = $this->actingAs($this->adminUser)->post(route('admin.catalog.bulk.preview'), [
            'csv_file' => $uploadedFile,
        ]);

        $previewResponse->assertOk();
        $previewResponse->assertSee('TEST-CEMENT-OPC');
        $previewResponse->assertSee('TEST-STEEL-REBAR');
        $previewResponse->assertSessionHas('bulk_import_valid_rows');

        // 2. Commit step
        $commitResponse = $this->actingAs($this->adminUser)->post(route('admin.catalog.bulk.commit'));
        $commitResponse->assertRedirect(route('admin.catalog.bulk'));
        $commitResponse->assertSessionHas('success');

        // 3. Database verification
        $this->assertDatabaseHas('products', [
            'name' => 'Standard Portland Cement',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'TEST-CEMENT-OPC',
            'selling_price' => 41000,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'TEST-STEEL-REBAR',
            'selling_price' => 88000,
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'available' => 250,
        ]);
    }

    public function test_csv_validation_rejects_invalid_rows(): void
    {
        $csvContent = implode("\n", [
            'sku,product_name,variant_name,brand,category,subcategory,description,unit,pack_size,mrp,selling_price,tax_class,stock,warehouse,grade,size,color,status',
            ',Missing SKU Product,Single Variant,Generic,General,,Desc,unit,1,100,80,GST 18%,10,Yard,,,,active',
            'VALID-SKU-001,Valid Product,Single Variant,Generic,General,,Desc,unit,1,100,invalid-price,GST 18%,10,Yard,,,,active',
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('invalid_catalog.csv', $csvContent);

        $response = $this->actingAs($this->adminUser)->post(route('admin.catalog.bulk.preview'), [
            'csv_file' => $uploadedFile,
        ]);

        $response->assertOk();
        $response->assertSee('Validation Errors Detected in 2 Row(s)');
        $response->assertSee('SKU is required.');
        $response->assertSee('Selling price must be a positive number.');
    }

    public function test_export_active_catalog_as_csv(): void
    {
        $brand = Brand::create(['name' => 'Birla Pivot', 'slug' => 'birla-pivot', 'is_active' => true]);
        $cat = Category::create(['name' => 'Bricks & Masonry', 'slug' => 'bricks-masonry', 'is_active' => true]);
        $tax = TaxClass::firstOrCreate(['name' => 'GST 18%'], ['rate_percentage' => 18.00]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'primary_category_id' => $cat->id,
            'name' => 'Wirecut Red Clay Bricks',
            'slug' => 'wirecut-red-clay-bricks',
            'status' => 'active',
            'published_at' => now(),
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $tax->id,
            'sku' => 'EXP-BRICK-001',
            'name' => 'Standard Modular',
            'unit' => 'piece',
            'pack_size' => 1,
            'mrp' => 1200,
            'selling_price' => 950,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.catalog.bulk.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('EXP-BRICK-001', $response->streamedContent());
    }

    public function test_csv_with_utf8_bom_and_warehouse_and_subcategory(): void
    {
        // Simulate Microsoft Excel UTF-8 BOM prefix "\xEF\xBB\xBF"
        $bom = "\xEF\xBB\xBF";
        $csvContent = $bom.implode("\n", [
            'sku,product_name,variant_name,brand,category,subcategory,description,unit,pack_size,mrp,selling_price,tax_class,stock,warehouse,grade,size,color,status',
            'BOM-STEEL-500,Premium Fe500 Rebar,12mm Bundle,Tata Tiscon,Steel,TMT Bars,High strength rebar,bundle,10,1200.00,1050.00,GST 18%,80,East Port Warehouse,Fe-500,12mm,Metallic,active',
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('excel_export.csv', $csvContent);

        // Preview should succeed despite UTF-8 BOM
        $previewResponse = $this->actingAs($this->adminUser)->post(route('admin.catalog.bulk.preview'), [
            'csv_file' => $uploadedFile,
        ]);

        $previewResponse->assertOk();
        $previewResponse->assertSee('BOM-STEEL-500');
        $previewResponse->assertSee('East Port Warehouse');

        // Commit step
        $commitResponse = $this->actingAs($this->adminUser)->post(route('admin.catalog.bulk.commit'));
        $commitResponse->assertRedirect(route('admin.catalog.bulk'));

        // Verify subcategory was created with correct parent relationship
        $parentCat = Category::where('name', 'Steel')->first();
        $this->assertNotNull($parentCat);

        $subCat = Category::where('name', 'TMT Bars')->where('parent_id', $parentCat->id)->first();
        $this->assertNotNull($subCat);

        // Verify product points to subcategory
        $this->assertDatabaseHas('products', [
            'name' => 'Premium Fe500 Rebar',
            'primary_category_id' => $subCat->id,
        ]);

        // Verify custom warehouse was created and inventory assigned
        $this->assertDatabaseHas('warehouses', [
            'name' => 'East Port Warehouse',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'available' => 80,
        ]);
    }
}
