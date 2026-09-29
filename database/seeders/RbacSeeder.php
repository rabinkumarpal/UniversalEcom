<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Catalog
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'catalog'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'group' => 'catalog'],
            ['name' => 'Update Products', 'slug' => 'products.update', 'group' => 'catalog'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'group' => 'catalog'],
            ['name' => 'Publish Products', 'slug' => 'products.publish', 'group' => 'catalog'],

            // Pricing & Promotions
            ['name' => 'View Prices', 'slug' => 'prices.view', 'group' => 'pricing'],
            ['name' => 'Update Prices', 'slug' => 'prices.update', 'group' => 'pricing'],
            ['name' => 'Manage Promotions', 'slug' => 'promotions.manage', 'group' => 'pricing'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Adjust Inventory', 'slug' => 'inventory.adjust', 'group' => 'inventory'],

            // Orders & Refunds
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders'],
            ['name' => 'Update Orders', 'slug' => 'orders.update', 'group' => 'orders'],
            ['name' => 'Cancel Orders', 'slug' => 'orders.cancel', 'group' => 'orders'],
            ['name' => 'Create Refunds', 'slug' => 'refunds.create', 'group' => 'orders'],
            ['name' => 'Manage Invoices', 'slug' => 'invoices.manage', 'group' => 'orders'],

            // Delivery & Fleet
            ['name' => 'Manage Delivery', 'slug' => 'delivery.manage', 'group' => 'delivery'],
            ['name' => 'Manage Shipments', 'slug' => 'shipments.manage', 'group' => 'delivery'],
            ['name' => 'Record POD', 'slug' => 'pod.record', 'group' => 'delivery'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers'],
            ['name' => 'Update Customers', 'slug' => 'customers.update', 'group' => 'customers'],

            // Content & SEO
            ['name' => 'Manage Content', 'slug' => 'content.manage', 'group' => 'content'],
            ['name' => 'Manage SEO', 'slug' => 'seo.manage', 'group' => 'content'],

            // Operations & Audit
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'group' => 'audit'],
        ];

        $permModels = [];
        foreach ($permissions as $p) {
            $permModels[$p['slug']] = Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // Roles definition
        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Full administrative access and bypass permissions across the entire platform.',
                'permissions' => array_keys($permModels),
            ],
            'catalog-manager' => [
                'name' => 'Catalog Manager',
                'description' => 'Manages products, variants, categories, brands, attributes, and catalog data imports.',
                'permissions' => ['products.view', 'products.create', 'products.update', 'products.delete', 'products.publish'],
            ],
            'pricing-manager' => [
                'name' => 'Pricing Manager',
                'description' => 'Manages prices, quantity tiers, promotional discounts, and coupons.',
                'permissions' => ['prices.view', 'prices.update', 'promotions.manage', 'products.view'],
            ],
            'inventory-manager' => [
                'name' => 'Inventory Manager',
                'description' => 'Manages warehouses, stock reservations, and inventory adjustments.',
                'permissions' => ['inventory.view', 'inventory.adjust', 'products.view'],
            ],
            'order-manager' => [
                'name' => 'Order Manager',
                'description' => 'Manages order processing, fulfillment, cancellations, and refunds.',
                'permissions' => ['orders.view', 'orders.update', 'orders.cancel', 'refunds.create', 'invoices.manage'],
            ],
            'delivery-manager' => [
                'name' => 'Delivery Manager',
                'description' => 'Manages delivery zones, fleet dispatch, driver tracking, and Proof of Delivery (POD).',
                'permissions' => ['delivery.manage', 'shipments.manage', 'pod.record', 'orders.view'],
            ],
            'customer-support' => [
                'name' => 'Customer Support',
                'description' => 'Handles customer inquiries, order status lookups, and customer profiles.',
                'permissions' => ['customers.view', 'customers.update', 'orders.view'],
            ],
            'content-manager' => [
                'name' => 'Content Manager',
                'description' => 'Manages homepage banners, knowledge hub articles, FAQs, and SEO metadata.',
                'permissions' => ['content.manage', 'seo.manage'],
            ],
            'customer' => [
                'name' => 'Customer',
                'description' => 'Storefront retail customer account.',
                'permissions' => [],
            ],
        ];

        foreach ($roles as $slug => $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $roleData['name'], 'description' => $roleData['description']]
            );

            $permIds = [];
            foreach ($roleData['permissions'] as $pSlug) {
                if (isset($permModels[$pSlug])) {
                    $permIds[] = $permModels[$pSlug]->id;
                }
            }

            $role->permissions()->sync($permIds);
        }
    }
}
