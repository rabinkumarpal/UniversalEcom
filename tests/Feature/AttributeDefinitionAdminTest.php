<?php

namespace Tests\Feature;

use App\Models\AttributeDefinition;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeDefinitionAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $adminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $this->admin = User::factory()->create([
            'email' => 'attr-admin@universal-ecom.test',
        ]);
        $this->admin->roles()->attach($adminRole);
    }

    // ─── Attribute Definition CRUD ───────────────────────────────────────────

    public function test_admin_can_list_attribute_definitions(): void
    {
        AttributeDefinition::create([
            'name' => 'Compressive Strength',
            'code' => 'compressive_strength',
            'type' => 'measurement',
        ]);

        AttributeDefinition::create([
            'name' => 'Grade',
            'code' => 'grade',
            'type' => 'select',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.catalog.attributes.index'));

        $response->assertOk();
        $response->assertSee('Compressive Strength');
        $response->assertSee('Grade');
    }

    public function test_admin_can_create_a_text_attribute_definition(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.catalog.attributes.store'), [
            'name' => 'Manufacturer Standard',
            'code' => 'manufacturer_standard',
            'type' => 'text',
            'is_filterable' => '1',
            'is_variant_attribute' => '0',
            'sort_order' => 10,
        ]);

        $response->assertRedirect(route('admin.catalog.attributes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attribute_definitions', [
            'name' => 'Manufacturer Standard',
            'code' => 'manufacturer_standard',
            'type' => 'text',
            'is_filterable' => true,
            'is_variant_attribute' => false,
            'sort_order' => 10,
        ]);
    }

    public function test_admin_can_create_select_attribute_with_predefined_values(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.catalog.attributes.store'), [
            'name' => 'Cement Grade',
            'code' => 'cement_grade',
            'type' => 'select',
            'is_filterable' => '1',
            'is_variant_attribute' => '0',
            'sort_order' => 5,
            'values' => [
                ['value' => 'OPC33', 'label' => 'OPC Grade 33', 'sort_order' => 0],
                ['value' => 'OPC43', 'label' => 'OPC Grade 43', 'sort_order' => 1],
                ['value' => 'OPC53', 'label' => 'OPC Grade 53', 'sort_order' => 2],
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.attributes.index'));

        $attr = AttributeDefinition::where('code', 'cement_grade')->first();
        $this->assertNotNull($attr);
        $this->assertCount(3, $attr->values);
        $this->assertDatabaseHas('attribute_values', [
            'attribute_definition_id' => $attr->id,
            'value' => 'OPC43',
            'label' => 'OPC Grade 43',
        ]);
    }

    public function test_admin_can_update_attribute_definition_and_add_value(): void
    {
        $attr = AttributeDefinition::create([
            'name' => 'Color',
            'code' => 'color',
            'type' => 'select',
        ]);

        $existingVal = AttributeValue::create([
            'attribute_definition_id' => $attr->id,
            'value' => 'grey',
            'label' => 'Grey',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.catalog.attributes.update', $attr->id), [
            'name' => 'Product Color',
            'code' => 'color',
            'type' => 'select',
            'is_filterable' => '1',
            'is_variant_attribute' => '0',
            'sort_order' => 0,
            'values' => [
                ['id' => $existingVal->id, 'value' => 'grey', 'label' => 'Steel Grey', 'sort_order' => 0],
                ['value' => 'white', 'label' => 'White', 'sort_order' => 1],
            ],
        ]);

        $response->assertRedirect(route('admin.catalog.attributes.index'));

        $this->assertDatabaseHas('attribute_definitions', [
            'id' => $attr->id,
            'name' => 'Product Color',
        ]);
        $this->assertDatabaseHas('attribute_values', [
            'id' => $existingVal->id,
            'label' => 'Steel Grey',
        ]);
        $this->assertDatabaseHas('attribute_values', [
            'attribute_definition_id' => $attr->id,
            'value' => 'white',
            'label' => 'White',
        ]);
    }

    public function test_admin_can_delete_an_attribute_value_during_update(): void
    {
        $attr = AttributeDefinition::create([
            'name' => 'Finish',
            'code' => 'finish',
            'type' => 'select',
        ]);

        $toDelete = AttributeValue::create([
            'attribute_definition_id' => $attr->id,
            'value' => 'matte',
            'label' => 'Matte',
            'sort_order' => 0,
        ]);

        $toKeep = AttributeValue::create([
            'attribute_definition_id' => $attr->id,
            'value' => 'glossy',
            'label' => 'Glossy',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.catalog.attributes.update', $attr->id), [
            'name' => 'Finish',
            'code' => 'finish',
            'type' => 'select',
            'is_filterable' => '0',
            'is_variant_attribute' => '0',
            'sort_order' => 0,
            'values' => [
                ['id' => $toKeep->id, 'value' => 'glossy', 'label' => 'Glossy', 'sort_order' => 0],
            ],
            'delete_value_ids' => [$toDelete->id],
        ]);

        $response->assertRedirect(route('admin.catalog.attributes.index'));

        $this->assertDatabaseMissing('attribute_values', ['id' => $toDelete->id]);
        $this->assertDatabaseHas('attribute_values', ['id' => $toKeep->id]);
    }

    public function test_admin_can_delete_attribute_definition(): void
    {
        $attr = AttributeDefinition::create([
            'name' => 'Odor Level',
            'code' => 'odor_level',
            'type' => 'number',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.catalog.attributes.destroy', $attr->id));

        $response->assertRedirect(route('admin.catalog.attributes.index'));
        $this->assertDatabaseMissing('attribute_definitions', ['id' => $attr->id]);
    }

    public function test_attribute_code_must_be_unique_on_create(): void
    {
        AttributeDefinition::create([
            'name' => 'Existing Attr',
            'code' => 'duplicate_code',
            'type' => 'text',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.catalog.attributes.store'), [
            'name' => 'New Attr',
            'code' => 'duplicate_code',
            'type' => 'text',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    // ─── Product Attribute Sync ───────────────────────────────────────────────

    public function test_admin_can_save_product_level_text_attribute_on_update(): void
    {
        $category = Category::create([
            'name' => 'Paints',
            'slug' => 'paints',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Asian Paints Apex',
            'slug' => 'asian-paints-apex',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'AP-APEX-20L',
            'name' => '20L Bucket',
            'unit' => 'bucket',
            'mrp' => 350000,
            'selling_price' => 320000,
            'status' => 'active',
        ]);

        $attr = AttributeDefinition::create([
            'name' => 'Coverage',
            'code' => 'coverage',
            'type' => 'measurement',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.catalog.products.update', $product->id), [
            'name' => 'Asian Paints Apex',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'variants' => [
                [
                    'id' => ProductVariant::where('product_id', $product->id)->first()->id,
                    'name' => '20L Bucket',
                    'sku' => 'AP-APEX-20L',
                    'mrp' => '3500.00',
                    'selling_price' => '3200.00',
                    'unit' => 'bucket',
                    'status' => 'active',
                ],
            ],
            'product_attributes' => [
                [
                    'attribute_definition_id' => $attr->id,
                    'value_number' => '140',
                    'value_text' => 'sq ft/litre',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_definition_id' => $attr->id,
            'value_number' => '140.0000',
            'value_text' => 'sq ft/litre',
        ]);
    }

    public function test_admin_updating_attributes_replaces_all_previous_values(): void
    {
        $category = Category::create([
            'name' => 'Cement',
            'slug' => 'cement',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'UltraTech OPC 53',
            'slug' => 'ultratech-opc-53',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'UT-OPC53-50KG',
            'name' => '50kg Bag',
            'unit' => 'bag',
            'mrp' => 45000,
            'selling_price' => 42000,
            'status' => 'active',
        ]);

        $attr1 = AttributeDefinition::create(['name' => 'Strength', 'code' => 'strength_mpa', 'type' => 'number']);
        $attr2 = AttributeDefinition::create(['name' => 'Setting Time', 'code' => 'setting_time', 'type' => 'text']);

        // First: save both attributes
        $this->actingAs($this->admin)->put(route('admin.catalog.products.update', $product->id), [
            'name' => 'UltraTech OPC 53',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'variants' => [['id' => ProductVariant::where('product_id', $product->id)->first()->id, 'name' => '50kg Bag', 'sku' => 'UT-OPC53-50KG', 'mrp' => '450.00', 'selling_price' => '420.00', 'unit' => 'bag', 'status' => 'active']],
            'product_attributes' => [
                ['attribute_definition_id' => $attr1->id, 'value_number' => '53'],
                ['attribute_definition_id' => $attr2->id, 'value_text' => '30 minutes initial'],
            ],
        ]);

        $this->assertDatabaseCount('product_attribute_values', 2);

        // Second: re-save with only one attribute — the other should be gone
        $this->actingAs($this->admin)->put(route('admin.catalog.products.update', $product->id), [
            'name' => 'UltraTech OPC 53',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'variants' => [['id' => ProductVariant::where('product_id', $product->id)->first()->id, 'name' => '50kg Bag', 'sku' => 'UT-OPC53-50KG', 'mrp' => '450.00', 'selling_price' => '420.00', 'unit' => 'bag', 'status' => 'active']],
            'product_attributes' => [
                ['attribute_definition_id' => $attr1->id, 'value_number' => '53'],
            ],
        ]);

        // Only attr1 should remain
        $this->assertDatabaseCount('product_attribute_values', 1);
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_definition_id' => $attr1->id,
        ]);
        $this->assertDatabaseMissing('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_definition_id' => $attr2->id,
        ]);
    }

    public function test_admin_can_assign_select_attribute_value_to_product(): void
    {
        $category = Category::create([
            'name' => 'Tiles',
            'slug' => 'tiles',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Kajaria Floor Tile',
            'slug' => 'kajaria-floor-tile',
            'primary_category_id' => $category->id,
            'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KAJ-FLR-2X2',
            'name' => '2x2 ft',
            'unit' => 'sqft',
            'mrp' => 8000,
            'selling_price' => 7500,
            'status' => 'active',
        ]);

        $attr = AttributeDefinition::create(['name' => 'Surface Finish', 'code' => 'surface_finish', 'type' => 'select']);
        $val1 = AttributeValue::create(['attribute_definition_id' => $attr->id, 'value' => 'matte', 'label' => 'Matte', 'sort_order' => 0]);
        $val2 = AttributeValue::create(['attribute_definition_id' => $attr->id, 'value' => 'glossy', 'label' => 'Glossy', 'sort_order' => 1]);

        $response = $this->actingAs($this->admin)->put(route('admin.catalog.products.update', $product->id), [
            'name' => 'Kajaria Floor Tile',
            'primary_category_id' => $category->id,
            'status' => 'published',
            'variants' => [['id' => ProductVariant::where('product_id', $product->id)->first()->id, 'name' => '2x2 ft', 'sku' => 'KAJ-FLR-2X2', 'mrp' => '80.00', 'selling_price' => '75.00', 'unit' => 'sqft', 'status' => 'active']],
            'product_attributes' => [
                ['attribute_definition_id' => $attr->id, 'attribute_value_id' => $val2->id],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_definition_id' => $attr->id,
            'attribute_value_id' => $val2->id,
        ]);
    }

    public function test_attribute_index_filters_by_type(): void
    {
        AttributeDefinition::create(['name' => 'Weight', 'code' => 'weight_kg', 'type' => 'number']);
        AttributeDefinition::create(['name' => 'Grade', 'code' => 'grade_type', 'type' => 'select']);
        AttributeDefinition::create(['name' => 'Description', 'code' => 'spec_desc', 'type' => 'text']);

        $response = $this->actingAs($this->admin)->get(route('admin.catalog.attributes.index', ['type' => 'number']));

        $response->assertOk();
        $response->assertSee('Weight');
        $response->assertDontSee('Grade');
        $response->assertDontSee('Description');
    }
}
