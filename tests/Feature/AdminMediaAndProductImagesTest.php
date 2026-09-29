<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaAndProductImagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RbacSeeder::class);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Tester',
            'email' => 'admin@universal-ecom.test',
        ]);
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->adminUser->roles()->attach($superAdminRole);
    }

    public function test_media_index_screen_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.media.index'));
        $response->assertOk();
        $response->assertSee('Media & Asset Library');
        $response->assertSee('Upload New Assets');
    }

    public function test_media_assets_can_be_uploaded_and_stored(): void
    {
        $fakeFile = UploadedFile::fake()->image('cement-photo.jpg', 800, 600);

        $response = $this->actingAs($this->adminUser)->post(route('admin.media.store'), [
            'files' => [$fakeFile],
            'folder' => 'products',
        ]);

        $response->assertRedirect(route('admin.media.index'));
        $this->assertDatabaseHas('media_assets', [
            'name' => 'cement-photo.jpg',
            'folder' => 'products',
        ]);

        $asset = MediaAsset::firstOrFail();
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_media_asset_can_be_deleted(): void
    {
        $fakeFile = UploadedFile::fake()->image('delete-me.jpg', 400, 300);
        $path = $fakeFile->store('media/general', 'public');

        $asset = MediaAsset::create([
            'name' => 'delete-me.jpg',
            'filename' => basename($path),
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'folder' => 'general',
            'user_id' => $this->adminUser->id,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.media.destroy', $asset->id));
        $response->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_media_api_returns_json_asset_list(): void
    {
        MediaAsset::create([
            'name' => 'api-image.jpg',
            'filename' => 'api-image.jpg',
            'path' => 'media/products/api-image.jpg',
            'url' => '/storage/media/products/api-image.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'folder' => 'products',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('admin.media.api', ['only_images' => 1]));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'api-image.jpg']);
    }

    public function test_product_can_be_created_with_uploaded_images(): void
    {
        $category = Category::create(['name' => 'Aggregates', 'slug' => 'aggregates', 'status' => 'active']);
        $fakeImage = UploadedFile::fake()->image('gravel-sample.jpg', 600, 400);

        $response = $this->actingAs($this->adminUser)->post(route('admin.catalog.products.store'), [
            'name' => 'Crushed Gravel 20mm',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'images' => [$fakeImage],
            'variants' => [
                [
                    'sku' => 'GRAVEL-20MM-TON',
                    'name' => '1 Ton Bulk Bag',
                    'mrp' => 1500,
                    'selling_price' => 1350,
                    'unit' => 'ton',
                    'status' => 'active',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.products.index'));

        $product = Product::where('name', 'Crushed Gravel 20mm')->firstOrFail();
        $this->assertCount(1, $product->media);

        $media = $product->media->first();
        $this->assertTrue($media->is_primary);
        Storage::disk('public')->assertExists($media->path);

        // Also registered into MediaAsset library
        $this->assertDatabaseHas('media_assets', [
            'name' => 'gravel-sample.jpg',
            'folder' => 'products',
        ]);
    }

    public function test_product_primary_image_can_be_toggled(): void
    {
        $category = Category::create(['name' => 'Cement', 'slug' => 'cement', 'status' => 'active']);
        $product = Product::create([
            'name' => 'OPC 53 Grade',
            'slug' => 'opc-53-grade',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $media1 = ProductMedia::create([
            'product_id' => $product->id,
            'url' => '/storage/products/img1.jpg',
            'is_primary' => true,
        ]);

        $media2 = ProductMedia::create([
            'product_id' => $product->id,
            'url' => '/storage/products/img2.jpg',
            'is_primary' => false,
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.catalog.products.media.primary', [$product->id, $media2->id])
        );

        $response->assertRedirect();
        $this->assertFalse($media1->fresh()->is_primary);
        $this->assertTrue($media2->fresh()->is_primary);
    }

    public function test_product_image_can_be_removed(): void
    {
        $category = Category::create(['name' => 'Steel', 'slug' => 'steel', 'status' => 'active']);
        $product = Product::create([
            'name' => 'TMT Rebar 12mm',
            'slug' => 'tmt-rebar-12mm',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        $media = ProductMedia::create([
            'product_id' => $product->id,
            'url' => '/storage/products/rebar.jpg',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(
            route('admin.catalog.products.media.destroy', [$product->id, $media->id])
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('product_media', ['id' => $media->id]);
    }
}
