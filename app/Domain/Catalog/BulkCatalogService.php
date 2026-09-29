<?php

namespace App\Domain\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuantityPriceTier;
use App\Models\TaxClass;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkCatalogService
{
    public const REQUIRED_COLUMNS = [
        'sku',
        'product_name',
        'variant_name',
        'brand',
        'category',
        'subcategory',
        'description',
        'unit',
        'pack_size',
        'mrp',
        'selling_price',
        'tax_class',
        'stock',
        'warehouse',
        'grade',
        'size',
        'color',
        'status',
    ];

    /**
     * Generate the official CSV sample template.
     */
    public function generateSampleCsv(): string
    {
        $headers = implode(',', self::REQUIRED_COLUMNS);
        $sampleRows = [
            'SKU-OPC-53-BAG,UltraTech Premium Cement,50kg HDPE Bag,UltraTech,Cement & Concrete,Structural Cement,"Top-grade 53 OPC for residential and commercial slabs.",bag,50,420.00,385.00,GST 18%,500,Central Yard,OPC 53,50kg,Grey,active',
            'SKU-TMT-550D-12MM,Tata Tiscon High Yield Rebar,12mm 12-Meter Rod,Tata Tiscon,Steel & TMT,Reinforcement Steel,"Thermo-mechanically treated earthquake resistant rebar.",piece,1,720.00,640.00,GST 18%,350,South Depot,Fe-550D,12mm,Metallic,active',
        ];

        return $headers."\n".implode("\n", $sampleRows);
    }

    /**
     * Export active catalog to CSV string.
     */
    public function exportCatalogCsv(): string
    {
        $variants = ProductVariant::with(['product.brand', 'product.primaryCategory.parent', 'taxClass', 'inventoryItems.warehouse'])
            ->get();

        $rows = [implode(',', self::REQUIRED_COLUMNS)];

        foreach ($variants as $variant) {
            $product = $variant->product;
            $stock = $variant->inventoryItems->sum('available');
            $warehouseName = $variant->inventoryItems->first()?->warehouse?->name ?? 'Central Logistics Yard';

            $category = $product?->primaryCategory;
            $categoryName = $category?->parent ? $category->parent->name : ($category?->name ?? 'General Goods');
            $subcategoryName = $category?->parent ? $category->name : '';

            $line = [
                $this->escapeCsv($variant->sku),
                $this->escapeCsv($product?->name ?? 'Unnamed Product'),
                $this->escapeCsv($variant->name),
                $this->escapeCsv($product?->brand?->name ?? 'Universal'),
                $this->escapeCsv($categoryName),
                $this->escapeCsv($subcategoryName),
                $this->escapeCsv($product?->description ?? ''),
                $this->escapeCsv($variant->unit ?? 'unit'),
                $this->escapeCsv($variant->pack_size ?? 1),
                $this->escapeCsv(number_format($variant->mrp / 100, 2, '.', '')),
                $this->escapeCsv(number_format($variant->selling_price / 100, 2, '.', '')),
                $this->escapeCsv($variant->taxClass?->name ?? 'GST 18%'),
                $stock,
                $this->escapeCsv($warehouseName),
                $this->escapeCsv(''),
                $this->escapeCsv(''),
                $this->escapeCsv(''),
                $variant->status ?? 'active',
            ];

            $rows[] = implode(',', $line);
        }

        return implode("\n", $rows);
    }

    /**
     * Preview and validate uploaded CSV content.
     *
     * @return array{total_rows: int, valid_count: int, invalid_count: int, new_count: int, update_count: int, valid_rows: array, invalid_rows: array}
     */
    public function previewCsv(string $csvContent): array
    {
        if (trim($csvContent) === '') {
            return [
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
                'new_count' => 0,
                'update_count' => 0,
                'valid_rows' => [],
                'invalid_rows' => [['row' => 0, 'sku' => 'N/A', 'error' => 'Uploaded CSV file is empty.', 'data' => []]],
            ];
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $rawHeaders = fgetcsv($stream);
        if (! is_array($rawHeaders) || empty(array_filter($rawHeaders))) {
            fclose($stream);

            return [
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 1,
                'new_count' => 0,
                'update_count' => 0,
                'valid_rows' => [],
                'invalid_rows' => [['row' => 1, 'sku' => 'N/A', 'error' => 'Missing essential header columns.', 'data' => []]],
            ];
        }

        // Clean headers: strip UTF-8 BOM, trim whitespace, convert to lowercase
        $headers = array_map(function ($h) {
            return strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF"));
        }, $rawHeaders);

        // Check required columns
        $missingColumns = array_diff(['sku', 'product_name', 'variant_name', 'selling_price'], $headers);
        if (! empty($missingColumns)) {
            fclose($stream);

            return [
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 1,
                'new_count' => 0,
                'update_count' => 0,
                'valid_rows' => [],
                'invalid_rows' => [['row' => 1, 'sku' => 'N/A', 'error' => 'Missing essential header columns: '.implode(', ', $missingColumns), 'data' => []]],
            ];
        }

        $headerMap = array_flip($headers);

        $validRows = [];
        $invalidRows = [];
        $newCount = 0;
        $updateCount = 0;
        $rowNumber = 1;

        while (($data = fgetcsv($stream)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($data, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $rowAssoc = [];
            foreach (self::REQUIRED_COLUMNS as $col) {
                $pos = $headerMap[$col] ?? null;
                $rowAssoc[$col] = $pos !== null && isset($data[$pos]) ? trim((string) $data[$pos]) : '';
            }

            // Validation rules
            $errors = [];
            if (empty($rowAssoc['sku'])) {
                $errors[] = 'SKU is required.';
            }

            if (empty($rowAssoc['product_name'])) {
                $errors[] = 'Product name is required.';
            }

            if (empty($rowAssoc['variant_name'])) {
                $errors[] = 'Variant name is required.';
            }

            if (! is_numeric($rowAssoc['selling_price']) || (float) $rowAssoc['selling_price'] < 0) {
                $errors[] = 'Selling price must be a positive number.';
            }

            if (! empty($rowAssoc['mrp']) && (! is_numeric($rowAssoc['mrp']) || (float) $rowAssoc['mrp'] < 0)) {
                $errors[] = 'MRP must be a positive number.';
            }

            if (! empty($rowAssoc['stock']) && (! is_numeric($rowAssoc['stock']) || (int) $rowAssoc['stock'] < 0)) {
                $errors[] = 'Stock must be an integer >= 0.';
            }

            if (! empty($errors)) {
                $invalidRows[] = [
                    'row' => $rowNumber,
                    'sku' => $rowAssoc['sku'] ?: 'N/A',
                    'error' => implode('; ', $errors),
                    'data' => $rowAssoc,
                ];

                continue;
            }

            // Check if SKU exists
            $exists = ProductVariant::where('sku', $rowAssoc['sku'])->exists();
            $rowAssoc['is_update'] = $exists;

            if ($exists) {
                $updateCount++;
            } else {
                $newCount++;
            }

            $validRows[] = $rowAssoc;
        }

        fclose($stream);

        return [
            'total_rows' => count($validRows) + count($invalidRows),
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'new_count' => $newCount,
            'update_count' => $updateCount,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];
    }

    /**
     * Atomically commit validated rows to database.
     */
    public function commitCsv(array $validRows): array
    {
        return DB::transaction(function () use ($validRows) {
            $defaultTaxClass = TaxClass::firstOrCreate(
                ['name' => 'GST 18%'],
                ['rate_percentage' => 18.00]
            );

            $defaultWarehouse = Warehouse::firstOrCreate(
                ['code' => 'CENTRAL-YARD'],
                ['name' => 'Central Logistics Yard', 'is_active' => true]
            );

            $processedCount = 0;

            foreach ($validRows as $row) {
                // 1. Resolve Brand
                $brandName = ! empty($row['brand']) ? $row['brand'] : 'Universal';
                $brand = Brand::firstOrCreate(
                    ['slug' => Str::slug($brandName)],
                    ['name' => $brandName, 'is_active' => true]
                );

                // 2. Resolve Category & Subcategory
                $categoryName = ! empty($row['category']) ? trim($row['category']) : 'General Goods';
                $category = Category::firstOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    ['name' => $categoryName, 'status' => 'active']
                );

                $targetCategory = $category;
                if (! empty($row['subcategory'])) {
                    $subcategoryName = trim($row['subcategory']);
                    $subcategory = Category::firstOrCreate(
                        ['slug' => Str::slug($categoryName.'-'.$subcategoryName)],
                        [
                            'name' => $subcategoryName,
                            'parent_id' => $category->id,
                            'status' => 'active',
                        ]
                    );
                    $targetCategory = $subcategory;
                }

                // 3. Resolve Product
                $productSlug = Str::slug($row['product_name']);
                $product = Product::firstOrCreate(
                    ['slug' => $productSlug],
                    [
                        'name' => $row['product_name'],
                        'brand_id' => $brand->id,
                        'primary_category_id' => $targetCategory->id,
                        'description' => $row['description'] ?: $row['product_name'],
                        'status' => 'active',
                        'published_at' => now(),
                    ]
                );

                // Attach category if not attached
                if (! $product->categories()->where('categories.id', $targetCategory->id)->exists()) {
                    $product->categories()->attach($targetCategory->id);
                }
                if ($targetCategory->id !== $category->id && ! $product->categories()->where('categories.id', $category->id)->exists()) {
                    $product->categories()->attach($category->id);
                }

                // 4. Resolve Tax Class
                $taxClass = $defaultTaxClass;
                if (! empty($row['tax_class'])) {
                    $taxClassName = trim($row['tax_class']);
                    $matchedTax = TaxClass::where('name', $taxClassName)->first();
                    if (! $matchedTax) {
                        preg_match('/(\d+(?:\.\d+)?)/', $taxClassName, $matches);
                        $rate = isset($matches[1]) ? (float) $matches[1] : 18.00;
                        $taxClass = TaxClass::firstOrCreate(
                            ['name' => $taxClassName],
                            ['rate_percentage' => $rate]
                        );
                    } else {
                        $taxClass = $matchedTax;
                    }
                }

                // 5. Resolve Variant
                $mrpPaise = ! empty($row['mrp']) ? (int) round(((float) $row['mrp']) * 100) : (int) round(((float) $row['selling_price']) * 100);
                $sellingPaise = (int) round(((float) $row['selling_price']) * 100);

                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $row['sku']],
                    [
                        'product_id' => $product->id,
                        'tax_class_id' => $taxClass->id,
                        'name' => $row['variant_name'],
                        'unit' => ! empty($row['unit']) ? $row['unit'] : 'unit',
                        'pack_size' => ! empty($row['pack_size']) ? (int) $row['pack_size'] : 1,
                        'mrp' => $mrpPaise,
                        'selling_price' => $sellingPaise,
                        'status' => in_array($row['status'], ['active', 'inactive', 'archived']) ? $row['status'] : 'active',
                    ]
                );

                // 6. Setup base price tier
                QuantityPriceTier::updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'min_quantity' => 1,
                    ],
                    [
                        'unit_price' => $sellingPaise,
                    ]
                );

                // 7. Resolve Warehouse & Setup warehouse inventory balance
                $warehouse = $defaultWarehouse;
                if (! empty($row['warehouse'])) {
                    $whName = trim($row['warehouse']);
                    $whCode = strtoupper(Str::slug($whName));
                    $warehouse = Warehouse::where('code', $whCode)
                        ->orWhere('name', $whName)
                        ->first();

                    if (! $warehouse) {
                        $warehouse = Warehouse::create([
                            'code' => $whCode,
                            'name' => $whName,
                            'is_active' => true,
                        ]);
                    }
                }

                $stockQty = ! empty($row['stock']) ? (int) $row['stock'] : 0;
                $inv = InventoryItem::firstOrNew([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouse->id,
                ]);

                if (! $inv->exists) {
                    $inv->on_hand = $stockQty;
                    $inv->reserved = 0;
                    $inv->available = $stockQty;
                    $inv->reorder_level = 10;
                    $inv->save();
                } else {
                    $diff = $stockQty - $inv->available;
                    if ($diff !== 0) {
                        $inv->on_hand += $diff;
                        $inv->available += $diff;
                        $inv->save();
                    }
                }

                $processedCount++;
            }

            return [
                'success' => true,
                'processed_count' => $processedCount,
            ];
        });
    }

    protected function escapeCsv(mixed $value): string
    {
        $str = (string) $value;
        if (str_contains($str, ',') || str_contains($str, '"') || str_contains($str, "\n")) {
            return '"'.str_replace('"', '""', $str).'"';
        }

        return $str;
    }
}
