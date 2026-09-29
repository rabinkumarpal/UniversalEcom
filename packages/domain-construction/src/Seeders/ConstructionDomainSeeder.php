<?php

namespace Packages\DomainConstruction\Seeders;

use App\Models\Address;
use App\Models\AttributeDefinition;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\QuantityPriceTier;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\User;
use App\Models\VariantAttributeValue;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Packages\B2BCommerce\Models\Company;
use Packages\B2BCommerce\Models\CompanyUser;
use Packages\PromotionEngine\Models\Promotion;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOffer;
use Packages\VendorMarketplace\Models\VendorUser;

class ConstructionDomainSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Core Roles & Permissions
        $roles = [
            'super-admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'catalog-manager' => 'Catalog Manager',
            'order-manager' => 'Order Manager',
            'inventory-manager' => 'Inventory Manager',
            'customer' => 'Customer / Contractor',
            'vendor_owner' => 'Vendor Owner',
        ];

        foreach ($roles as $slug => $name) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@ecom-laravel.test'],
            [
                'name' => 'System Administrator',
                'phone' => '9999999999',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $adminRoleIds = Role::whereIn('slug', ['super-admin'])->pluck('id')->toArray();
        $admin->roles()->sync($adminRoleIds);

        // Create Contractor Customer User
        $contractor = User::firstOrCreate(
            ['email' => 'contractor@acmebuild.test'],
            [
                'name' => 'Rajesh Sharma (Acme Builders)',
                'phone' => '9888877777',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $contractor->roles()->sync([Role::where('slug', 'customer')->first()->id]);

        // Contractor addresses (Office and Construction Site)
        Address::firstOrCreate(
            ['user_id' => $contractor->id, 'label' => 'Head Office'],
            [
                'recipient_name' => 'Rajesh Sharma',
                'phone' => '9888877777',
                'address_line_1' => 'Suite 401, Prestige Trade Tower',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'IN',
                'pincode' => '560001',
                'is_default' => true,
                'is_site_address' => false,
            ]
        );

        Address::firstOrCreate(
            ['user_id' => $contractor->id, 'label' => 'Site A - Brigade Gateway Project'],
            [
                'recipient_name' => 'Site Supervisor Suresh',
                'phone' => '9777766666',
                'address_line_1' => 'Plot 44, Industrial Layout, Phase 2',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'IN',
                'pincode' => '560002',
                'is_default' => false,
                'is_site_address' => true,
            ]
        );

        // 2. Delivery Zones & Pincodes
        $zone = DeliveryZone::firstOrCreate(
            ['name' => 'Bengaluru Metro Core'],
            ['base_fee' => 35000, 'min_order_free_shipping' => 1500000, 'is_active' => true] // ₹350 fee, free delivery above ₹15,000
        );

        foreach (['560001', '560002', '560025', '560038', '560068'] as $pin) {
            DeliveryZonePincode::firstOrCreate(['delivery_zone_id' => $zone->id, 'pincode' => $pin]);
        }

        DeliverySlot::firstOrCreate(
            ['delivery_zone_id' => $zone->id, 'name' => 'Early Morning (6 AM - 10 AM)'],
            ['start_time' => '06:00:00', 'end_time' => '10:00:00', 'max_orders_per_day' => 30, 'is_active' => true]
        );
        DeliverySlot::firstOrCreate(
            ['delivery_zone_id' => $zone->id, 'name' => 'Same-Day Afternoon (1 PM - 6 PM)'],
            ['start_time' => '13:00:00', 'end_time' => '18:00:00', 'max_orders_per_day' => 40, 'is_active' => true]
        );

        // 3. Warehouses
        $wh1 = Warehouse::firstOrCreate(['code' => 'WH-BLR-01'], ['name' => 'Bengaluru Central Hub', 'is_active' => true]);
        $wh2 = Warehouse::firstOrCreate(['code' => 'WH-BLR-02'], ['name' => 'North Depot Logistics Yard', 'is_active' => true]);

        // 4. Tax Classes
        $gst18 = TaxClass::firstOrCreate(['name' => 'GST 18%'], ['rate_percentage' => 18.0]);
        $gst28 = TaxClass::firstOrCreate(['name' => 'GST 28% (Cement & Paints)'], ['rate_percentage' => 28.0]);

        // 5. Brands
        $brandUltratech = Brand::firstOrCreate(['slug' => 'ultratech'], ['name' => 'UltraTech Cement', 'status' => 'active']);
        $brandTata = Brand::firstOrCreate(['slug' => 'tata-tiscon'], ['name' => 'Tata Tiscon', 'status' => 'active']);
        $brandSupreme = Brand::firstOrCreate(['slug' => 'supreme'], ['name' => 'Supreme Industries', 'status' => 'active']);
        $brandAsian = Brand::firstOrCreate(['slug' => 'asian-paints'], ['name' => 'Asian Paints', 'status' => 'active']);
        $brandGreenply = Brand::firstOrCreate(['slug' => 'greenply'], ['name' => 'Greenply', 'status' => 'active']);

        // 6. Categories (Hierarchical)
        $civil = Category::firstOrCreate(['slug' => 'civil-interiors'], ['name' => 'Civil & Interiors', 'status' => 'active', 'sort_order' => 1]);
        $cementCat = Category::firstOrCreate(['slug' => 'cement', 'parent_id' => $civil->id], ['name' => 'Cement & Aggregates', 'status' => 'active']);
        $steelCat = Category::firstOrCreate(['slug' => 'steel-rebar', 'parent_id' => $civil->id], ['name' => 'Steel & TMT Rebars', 'status' => 'active']);
        $plywoodCat = Category::firstOrCreate(['slug' => 'plywood-boards', 'parent_id' => $civil->id], ['name' => 'Plywood & Boards', 'status' => 'active']);

        $plumbing = Category::firstOrCreate(['slug' => 'plumbing-sanitary'], ['name' => 'Plumbing & Sanitary', 'status' => 'active', 'sort_order' => 2]);
        $pipesCat = Category::firstOrCreate(['slug' => 'pipes-fittings', 'parent_id' => $plumbing->id], ['name' => 'Pipes & Fittings', 'status' => 'active']);

        $paints = Category::firstOrCreate(['slug' => 'paints-coatings'], ['name' => 'Paints & Waterproofing', 'status' => 'active', 'sort_order' => 3]);

        // 7. Dynamic Attribute Definitions
        $attrGrade = AttributeDefinition::firstOrCreate(['code' => 'grade'], ['name' => 'Material Grade', 'type' => 'select', 'is_filterable' => true, 'is_variant_attribute' => true]);
        $attrPack = AttributeDefinition::firstOrCreate(['code' => 'pack_size'], ['name' => 'Pack Size / Unit', 'type' => 'select', 'is_filterable' => true, 'is_variant_attribute' => true]);
        $attrThickness = AttributeDefinition::firstOrCreate(['code' => 'thickness'], ['name' => 'Thickness', 'type' => 'select', 'is_filterable' => true, 'is_variant_attribute' => true]);

        $valPPC = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrGrade->id, 'value' => 'PPC'], ['label' => 'Portland Pozzolana Cement (PPC)']);
        $valOPC53 = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrGrade->id, 'value' => 'OPC 53'], ['label' => 'Ordinary Portland Cement 53 Grade']);
        $valFe550 = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrGrade->id, 'value' => 'Fe 550D'], ['label' => 'High Ductility Fe 550D']);

        $val50kg = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrPack->id, 'value' => '50 KG Bag'], ['label' => '50 KG Bag']);
        $val12m = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrPack->id, 'value' => '12 Metre Bar'], ['label' => '12 Metre Length']);
        $val20L = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrPack->id, 'value' => '20 Litre Drum'], ['label' => '20 Litre Drum']);

        $val12mm = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrThickness->id, 'value' => '12mm'], ['label' => '12 mm']);
        $val16mm = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrThickness->id, 'value' => '16mm'], ['label' => '16 mm']);
        $val19mm = AttributeValue::firstOrCreate(['attribute_definition_id' => $attrThickness->id, 'value' => '19mm'], ['label' => '19 mm Marine Grade']);

        // 8. Products & Variants with Bulk Quantity Tiers
        // Product 1: UltraTech Super Cement
        $prod1 = Product::firstOrCreate(
            ['slug' => 'ultratech-super-cement'],
            [
                'brand_id' => $brandUltratech->id,
                'primary_category_id' => $cementCat->id,
                'name' => 'UltraTech Super PPC High Strength Cement',
                'short_description' => 'Engineered Portland Pozzolana Cement for heavy RCC casting and durable plastering.',
                'description' => 'UltraTech Super is formulated with finely dispersed reactive silica for dense concrete, low heat of hydration, and crack-free structures.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $var1 = ProductVariant::firstOrCreate(
            ['sku' => 'UT-PPC-50KG'],
            [
                'product_id' => $prod1->id,
                'tax_class_id' => $gst28->id,
                'name' => '50 KG Sealed Bag',
                'unit' => 'bag',
                'pack_size' => '50 KG',
                'weight_kg' => 50.00,
                'mrp' => 48000, // ₹480.00
                'selling_price' => 45000, // ₹450.00 (1-9 bags)
                'status' => 'active',
            ]
        );

        // Bulk Quantity Tiers for Cement
        QuantityPriceTier::firstOrCreate(
            ['product_variant_id' => $var1->id, 'min_quantity' => 10, 'max_quantity' => 49],
            ['unit_price' => 43500] // ₹435.00
        );
        QuantityPriceTier::firstOrCreate(
            ['product_variant_id' => $var1->id, 'min_quantity' => 50, 'max_quantity' => null],
            ['unit_price' => 42000] // ₹420.00 (Truckload / site price)
        );

        // Variant Attributes
        VariantAttributeValue::firstOrCreate([
            'product_variant_id' => $var1->id,
            'attribute_definition_id' => $attrGrade->id,
            'attribute_value_id' => $valPPC->id,
        ]);
        VariantAttributeValue::firstOrCreate([
            'product_variant_id' => $var1->id,
            'attribute_definition_id' => $attrPack->id,
            'attribute_value_id' => $val50kg->id,
        ]);

        // Stock for Cement
        InventoryItem::firstOrCreate(
            ['warehouse_id' => $wh1->id, 'product_variant_id' => $var1->id],
            ['on_hand' => 1000, 'reserved' => 0, 'available' => 1000, 'reorder_level' => 100]
        );

        // Media
        ProductMedia::firstOrCreate(
            ['product_id' => $prod1->id, 'url' => 'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?w=600'],
            ['is_primary' => true, 'sort_order' => 1]
        );

        // Product 2: Tata Tiscon Fe 550D TMT Rebar
        $prod2 = Product::firstOrCreate(
            ['slug' => 'tata-tiscon-fe-550d-tmt-rebar'],
            [
                'brand_id' => $brandTata->id,
                'primary_category_id' => $steelCat->id,
                'name' => 'Tata Tiscon 550D High Ductility Earthquake Resistant Rebar',
                'short_description' => 'BIS certified Fe 550D primary steel rebar engineered for superior bendability and seismic resistance.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $var2 = ProductVariant::firstOrCreate(
            ['sku' => 'TATA-TMT-12MM'],
            [
                'product_id' => $prod2->id,
                'tax_class_id' => $gst18->id,
                'name' => '12mm Diameter × 12m Standard Length',
                'unit' => 'bar',
                'pack_size' => '12m Length',
                'weight_kg' => 10.65,
                'mrp' => 92000, // ₹920.00
                'selling_price' => 86000, // ₹860.00
                'status' => 'active',
            ]
        );

        QuantityPriceTier::firstOrCreate(
            ['product_variant_id' => $var2->id, 'min_quantity' => 25, 'max_quantity' => null],
            ['unit_price' => 81000] // ₹810.00
        );

        VariantAttributeValue::firstOrCreate([
            'product_variant_id' => $var2->id,
            'attribute_definition_id' => $attrGrade->id,
            'attribute_value_id' => $valFe550->id,
        ]);
        VariantAttributeValue::firstOrCreate([
            'product_variant_id' => $var2->id,
            'attribute_definition_id' => $attrThickness->id,
            'attribute_value_id' => $val12mm->id,
        ]);

        InventoryItem::firstOrCreate(
            ['warehouse_id' => $wh2->id, 'product_variant_id' => $var2->id],
            ['on_hand' => 500, 'reserved' => 0, 'available' => 500]
        );

        ProductMedia::firstOrCreate(
            ['product_id' => $prod2->id, 'url' => 'https://images.unsplash.com/photo-1504917599217-d4dc5ebe6122?w=600'],
            ['is_primary' => true, 'sort_order' => 1]
        );

        // Product 3: Supreme PVC Soil & Waste Drainage Pipe
        $prod3 = Product::firstOrCreate(
            ['slug' => 'supreme-pvc-drainage-pipe-4-inch'],
            [
                'brand_id' => $brandSupreme->id,
                'primary_category_id' => $pipesCat->id,
                'name' => 'Supreme 4-Inch Heavy Duty SWR Drainage Pipe',
                'short_description' => 'UV stabilized high impact PVC drainage pipe with ring-fit leakproof joint.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $var3 = ProductVariant::firstOrCreate(
            ['sku' => 'SUP-PIPE-4IN-10FT'],
            [
                'product_id' => $prod3->id,
                'tax_class_id' => $gst18->id,
                'name' => '110mm (4 Inch) × 10ft Length',
                'unit' => 'piece',
                'pack_size' => '10ft',
                'mrp' => 45000,
                'selling_price' => 39000, // ₹390.00
                'status' => 'active',
            ]
        );

        InventoryItem::firstOrCreate(
            ['warehouse_id' => $wh1->id, 'product_variant_id' => $var3->id],
            ['on_hand' => 300, 'reserved' => 0, 'available' => 300]
        );

        // Product 4: Asian Paints Apex WeatherProof Exterior Paint
        $prod4 = Product::firstOrCreate(
            ['slug' => 'asian-paints-apex-exterior-emulsion-20l'],
            [
                'brand_id' => $brandAsian->id,
                'primary_category_id' => $paints->id,
                'name' => 'Asian Paints Apex Weatherproof Exterior Emulsion 20L',
                'short_description' => 'Modified acrylic exterior wall finish with silicon additives for extreme climate protection.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $var4 = ProductVariant::firstOrCreate(
            ['sku' => 'AP-APEX-20L-WHT'],
            [
                'product_id' => $prod4->id,
                'tax_class_id' => $gst28->id,
                'name' => '20 Litre Bucket (Brilliant White)',
                'unit' => 'bucket',
                'pack_size' => '20 Litre',
                'mrp' => 540000, // ₹5,400.00
                'selling_price' => 480000, // ₹4,800.00
                'status' => 'active',
            ]
        );

        InventoryItem::firstOrCreate(
            ['warehouse_id' => $wh1->id, 'product_variant_id' => $var4->id],
            ['on_hand' => 150, 'reserved' => 0, 'available' => 150]
        );

        // Product 5: Greenply Marine Grade Plywood
        $prod5 = Product::firstOrCreate(
            ['slug' => 'greenply-marine-plywood-19mm'],
            [
                'brand_id' => $brandGreenply->id,
                'primary_category_id' => $plywoodCat->id,
                'name' => 'Greenply Marine Grade BWP 710 Plywood 19mm',
                'short_description' => 'Boiling waterproof marine plywood treated with organic preservatives for wet areas and furniture.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $var5 = ProductVariant::firstOrCreate(
            ['sku' => 'GP-PLY-19MM-8X4'],
            [
                'product_id' => $prod5->id,
                'tax_class_id' => $gst18->id,
                'name' => '8ft × 4ft Sheet (19mm Thickness)',
                'unit' => 'sheet',
                'pack_size' => '8x4 ft',
                'mrp' => 380000, // ₹3,800.00
                'selling_price' => 335000, // ₹3,350.00
                'status' => 'active',
            ]
        );

        InventoryItem::firstOrCreate(
            ['warehouse_id' => $wh2->id, 'product_variant_id' => $var5->id],
            ['on_hand' => 120, 'reserved' => 0, 'available' => 120]
        );

        // 9. Multi-Vendor Setup
        $vendor1 = Vendor::firstOrCreate(
            ['slug' => 'southern-steel-corp'],
            [
                'legal_name' => 'Southern Steel & Infrastructure Ltd',
                'display_name' => 'Southern Steel Corporation',
                'email' => 'sales@southernsteel.test',
                'phone' => '9880011223',
                'status' => 'active',
                'approval_status' => 'approved',
                'commission_rate_percentage' => 8.00, // 8% commission on rebar
                'approved_at' => now(),
            ]
        );

        // Vendor offer on Rebar
        VendorOffer::firstOrCreate(
            ['vendor_id' => $vendor1->id, 'product_variant_id' => $var2->id],
            ['vendor_price' => 86000, 'vendor_mrp' => 92000, 'status' => 'approved']
        );

        $vendorUser = User::firstOrCreate(
            ['email' => 'vendor@southernsteel.test'],
            [
                'name' => 'Prakash Rao (Southern Steel)',
                'phone' => '9880011223',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        $vendorUser->roles()->sync([Role::where('slug', 'vendor_owner')->first()->id]);
        VendorUser::firstOrCreate(['vendor_id' => $vendor1->id, 'user_id' => $vendorUser->id], ['role' => 'owner']);

        // 10. Promotional Campaigns for Construction Reference Domain
        // A. Contractor Bulk Cast Campaign: 5% off when buying 50+ bags
        Promotion::firstOrCreate(
            ['slug' => 'contractor-slab-pour-campaign'],
            [
                'name' => 'RCC Slab Casting Discount (5% Off)',
                'type' => 'percentage',
                'status' => 'active',
                'priority' => 5,
                'stackable' => true,
                'configuration' => [
                    'discount_percentage' => 5.0,
                ],
            ]
        );

        // B. Project Spending Goal: Spend >= ₹50,000 to unlock Free Site Crane / Forklift Delivery
        Promotion::firstOrCreate(
            ['slug' => 'heavy-delivery-spending-goal'],
            [
                'name' => 'Spend ₹50,000 Get Free Crane Unloading & Delivery',
                'type' => 'spending_goal',
                'status' => 'active',
                'priority' => 10,
                'stackable' => true,
                'configuration' => [
                    'goal_amount' => 5000000, // ₹50,000.00
                    'reward_type' => 'free_shipping',
                ],
            ]
        );

        // 11. B2B Corporate Enterprise Account & Credit Line
        if (class_exists(Company::class)) {
            $company = Company::firstOrCreate(
                ['company_code' => 'ACME-BLD'],
                [
                    'name' => 'Acme Builders Infrastructure Ltd',
                    'tax_id' => '29XYZAB1234C1Z9',
                    'credit_limit' => 100000000, // ₹10,00,000.00
                    'credit_balance' => 85000000, // ₹8,50,000.00
                    'payment_terms_days' => 30,
                    'status' => 'active',
                ]
            );

            CompanyUser::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $contractor->id],
                ['role' => 'buyer', 'is_active' => true, 'spending_limit' => 25000000]
            );

            CompanyUser::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $admin->id],
                ['role' => 'admin', 'is_active' => true, 'spending_limit' => 100000000]
            );
        }
    }
}
