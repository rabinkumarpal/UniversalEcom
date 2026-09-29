<?php

namespace App\Http\Controllers\Web;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Models\AttributeDefinition;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Models\TaxClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCatalogWebController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(Request $request): View
    {
        $query = Product::with(['primaryCategory', 'brand', 'variants', 'tags', 'primaryMedia', 'variants.inventoryItems', 'variants.quantityTiers']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('primary_category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('stock_status')) {
            $query->where(function ($q) use ($request) {
                if ($request->stock_status === 'in_stock') {
                    $q->whereHas('variants.inventoryItems', function ($inv) {
                        $inv->where('available', '>', 0);
                    })->whereRaw('(SELECT COALESCE(SUM(ii.available), 0) FROM inventory_items ii INNER JOIN product_variants pv ON pv.id = ii.product_variant_id WHERE pv.product_id = products.id) > 10');
                } elseif ($request->stock_status === 'low_stock') {
                    $q->whereRaw('(SELECT COALESCE(SUM(ii.available), 0) FROM inventory_items ii INNER JOIN product_variants pv ON pv.id = ii.product_variant_id WHERE pv.product_id = products.id) BETWEEN 1 AND 10');
                } elseif ($request->stock_status === 'out_of_stock') {
                    $q->where(function ($sub) {
                        $sub->whereDoesntHave('variants.inventoryItems')
                            ->orWhereRaw('(SELECT COALESCE(SUM(ii.available), 0) FROM inventory_items ii INNER JOIN product_variants pv ON pv.id = ii.product_variant_id WHERE pv.product_id = products.id) = 0');
                    });
                }
            });
        }

        $products = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('admin.catalog.products.index', compact('products', 'categories', 'brands'));
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:publish,draft,archive,delete,assign_category',
            'category_id' => 'required_if:action,assign_category|nullable|exists:categories,id',
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();
        $count = $products->count();

        switch ($request->action) {
            case 'publish':
                Product::whereIn('id', $request->product_ids)->update(['status' => 'published', 'published_at' => now()]);
                $message = "{$count} products published.";
                break;
            case 'draft':
                Product::whereIn('id', $request->product_ids)->update(['status' => 'draft']);
                $message = "{$count} products moved to draft.";
                break;
            case 'archive':
                Product::whereIn('id', $request->product_ids)->update(['status' => 'archived']);
                $message = "{$count} products archived.";
                break;
            case 'delete':
                foreach ($products as $product) {
                    $hasOrders = $product->variants()->whereHas('orderItems')->exists();
                    if ($hasOrders) {
                        $product->update(['status' => 'archived']);
                    } else {
                        $product->delete();
                    }
                }
                $message = "{$count} products deleted (products with orders were archived instead).";
                break;
            case 'assign_category':
                Product::whereIn('id', $request->product_ids)->update(['primary_category_id' => $request->category_id]);
                $message = "{$count} products reassigned to new category.";
                break;
            default:
                $message = 'Unknown action.';
        }

        return redirect()->route('admin.catalog.products.index')->with('success', $message);
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $taxClasses = TaxClass::orderBy('name')->get();
        $product = null;
        $recentAudits = collect();
        $attributeDefinitions = AttributeDefinition::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.catalog.products.form', compact('product', 'categories', 'brands', 'tags', 'taxClasses', 'recentAudits', 'attributeDefinitions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'primary_category_id' => 'required|integer|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:10240',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'integer|exists:media_assets,id',
            'variants' => 'required|array|min:1',
            'variants.*.sku' => 'required|string|max:100|distinct|unique:product_variants,sku',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.mrp' => 'required|numeric|min:0',
            'variants.*.selling_price' => 'required|numeric|min:0',
            'variants.*.unit' => 'required|string|max:50',
            'variants.*.pack_size' => 'nullable|string|max:100',
            'variants.*.weight_kg' => 'nullable|numeric|min:0',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.tax_class_id' => 'nullable|integer|exists:tax_classes,id',
            'variants.*.status' => 'required|in:active,inactive,archived',
            'variants.*.tiers' => 'nullable|array',
            'variants.*.tiers.*.min_quantity' => 'nullable|integer|min:1',
            'variants.*.tiers.*.max_quantity' => 'nullable|integer',
            'variants.*.tiers.*.unit_price' => 'nullable|numeric|min:0',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:20480',
            'document_titles' => 'nullable|array',
            'product_attributes' => 'nullable|array',
            'product_attributes.*.attribute_definition_id' => 'required_with:product_attributes|integer|exists:attribute_definitions,id',
            'product_attributes.*.attribute_value_id' => 'nullable|integer|exists:attribute_values,id',
            'product_attributes.*.value_text' => 'nullable|string|max:1000',
            'product_attributes.*.value_number' => 'nullable|numeric',
            'product_attributes.*.value_boolean' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'primary_category_id' => $validated['primary_category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'published_at' => ($validated['status'] === 'published') ? now() : null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        foreach ($validated['variants'] as $v) {
            $createdVariant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $v['sku'],
                'name' => $v['name'],
                'mrp' => (int) round((float) $v['mrp'] * 100),
                'selling_price' => (int) round((float) $v['selling_price'] * 100),
                'unit' => $v['unit'],
                'pack_size' => ! empty($v['pack_size']) ? $v['pack_size'] : $v['unit'],
                'weight_kg' => isset($v['weight_kg']) && is_numeric($v['weight_kg']) ? (float) $v['weight_kg'] : null,
                'barcode' => ! empty($v['barcode']) ? $v['barcode'] : null,
                'tax_class_id' => ! empty($v['tax_class_id']) ? (int) $v['tax_class_id'] : null,
                'status' => $v['status'],
            ]);

            if (! empty($v['tiers']) && is_array($v['tiers'])) {
                foreach ($v['tiers'] as $tier) {
                    if (! empty($tier['min_quantity']) && isset($tier['unit_price']) && is_numeric($tier['unit_price'])) {
                        $createdVariant->quantityTiers()->create([
                            'min_quantity' => (int) $tier['min_quantity'],
                            'max_quantity' => ! empty($tier['max_quantity']) ? (int) $tier['max_quantity'] : null,
                            'unit_price' => (int) round((float) $tier['unit_price'] * 100),
                        ]);
                    }
                }
            }
        }

        if (! empty($validated['tags'])) {
            $product->tags()->sync($validated['tags']);
        }

        if (isset($validated['category_ids'])) {
            $categoriesToSync = array_unique(array_filter(array_merge(
                [$validated['primary_category_id']],
                $validated['category_ids']
            )));
            $product->categories()->sync($categoriesToSync);
        }

        // Process direct image uploads
        $isPrimary = true;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imgFile) {
                $path = $imgFile->store('products', 'public');
                $url = Storage::disk('public')->url($path);
                $mime = $imgFile->getClientMimeType() ?: 'image/jpeg';
                $size = $imgFile->getSize() ?: 0;
                $dimensions = null;
                $info = @getimagesize($imgFile->getRealPath());
                if ($info) {
                    $dimensions = "{$info[0]}x{$info[1]}";
                }

                ProductMedia::create([
                    'product_id' => $product->id,
                    'url' => $url,
                    'path' => $path,
                    'mime_type' => $mime,
                    'is_primary' => $isPrimary,
                    'sort_order' => 0,
                ]);

                // Also register into central MediaAsset library
                MediaAsset::create([
                    'name' => $imgFile->getClientOriginalName(),
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => $url,
                    'disk' => 'public',
                    'mime_type' => $mime,
                    'size' => $size,
                    'dimensions' => $dimensions,
                    'folder' => 'products',
                    'user_id' => auth()->id(),
                ]);

                $isPrimary = false;
            }
        }

        // Process assets selected from media library
        if (! empty($validated['media_asset_ids'])) {
            foreach ($validated['media_asset_ids'] as $assetId) {
                $asset = MediaAsset::find($assetId);
                if ($asset) {
                    ProductMedia::create([
                        'product_id' => $product->id,
                        'url' => $asset->url,
                        'path' => $asset->path,
                        'mime_type' => $asset->mime_type,
                        'is_primary' => $isPrimary,
                        'sort_order' => 0,
                    ]);
                    $isPrimary = false;
                }
            }
        }

        // Process documents
        if ($request->hasFile('documents')) {
            $docTitles = $request->input('document_titles', []);
            foreach ($request->file('documents') as $idx => $docFile) {
                $path = $docFile->store('products/documents', 'public');
                $url = Storage::disk('public')->url($path);
                $title = $docTitles[$idx] ?? $docFile->getClientOriginalName();
                $mime = $docFile->getClientMimeType() ?: 'application/pdf';

                ProductDocument::create([
                    'product_id' => $product->id,
                    'title' => $title,
                    'url' => $url,
                    'path' => $path,
                    'file_type' => $mime,
                    'sort_order' => 0,
                ]);
            }
        }

        $this->audit->productCreated($product, $product->toArray());

        // Sync product-level attribute values
        if (! empty($validated['product_attributes'])) {
            $this->syncProductAttributes($product, $validated['product_attributes']);
        }

        return redirect()->route('admin.catalog.products.index')
            ->with('success', "Product [{$product->name}] created successfully.");
    }

    public function edit(int $id): View
    {
        $product = Product::with([
            'variants.quantityTiers',
            'variants.inventoryItems.warehouse',
            'variants.taxClass',
            'tags',
            'media',
            'documents',
            'categories',
            'brand',
            'primaryCategory',
            'attributeValues.definition',
            'attributeValues.predefinedValue',
        ])->findOrFail($id);

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $taxClasses = TaxClass::orderBy('name')->get();
        $attributeDefinitions = AttributeDefinition::orderBy('sort_order')->orderBy('name')->get();

        $variantIds = $product->variants->pluck('id')->filter();
        $recentAudits = AuditLog::where(function ($q) use ($product) {
            $q->where('entity_type', Product::class)->where('entity_id', $product->id);
        })->orWhere(function ($q) use ($variantIds) {
            if ($variantIds->isNotEmpty()) {
                $q->where('entity_type', ProductVariant::class)->whereIn('entity_id', $variantIds);
            }
        })->with('user')->latest()->limit(10)->get();

        return view('admin.catalog.products.form', compact(
            'product',
            'categories',
            'brands',
            'tags',
            'taxClasses',
            'recentAudits',
            'attributeDefinitions'
        ));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'primary_category_id' => 'required|integer|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.name' => 'required_with:variants|string|max:255',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.mrp' => 'required_with:variants|numeric|min:0',
            'variants.*.selling_price' => 'required_with:variants|numeric|min:0',
            'variants.*.unit' => 'required_with:variants|string|max:50',
            'variants.*.pack_size' => 'nullable|string|max:100',
            'variants.*.weight_kg' => 'nullable|numeric|min:0',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.tax_class_id' => 'nullable|integer|exists:tax_classes,id',
            'variants.*.status' => 'required_with:variants|in:active,inactive,archived',
            'variants.*.tiers' => 'nullable|array',
            'variants.*.tiers.*.min_quantity' => 'nullable|integer|min:1',
            'variants.*.tiers.*.max_quantity' => 'nullable|integer',
            'variants.*.tiers.*.unit_price' => 'nullable|numeric|min:0',
            'delete_variant_ids' => 'nullable|array',
            'delete_variant_ids.*' => 'integer|exists:product_variants,id',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:10240',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'integer|exists:media_assets,id',
            'primary_media_id' => 'nullable|integer|exists:product_media,id',
            'delete_media_ids' => 'nullable|array',
            'delete_media_ids.*' => 'integer|exists:product_media,id',
            'media_sort_orders' => 'nullable|array',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:20480',
            'document_titles' => 'nullable|array',
            'delete_document_ids' => 'nullable|array',
            'delete_document_ids.*' => 'integer|exists:product_documents,id',
            'product_attributes' => 'nullable|array',
            'product_attributes.*.attribute_definition_id' => 'required_with:product_attributes|integer|exists:attribute_definitions,id',
            'product_attributes.*.attribute_value_id' => 'nullable|integer|exists:attribute_values,id',
            'product_attributes.*.value_text' => 'nullable|string|max:1000',
            'product_attributes.*.value_number' => 'nullable|numeric',
            'product_attributes.*.value_boolean' => 'nullable|boolean',
        ]);

        $product->update([
            'name' => $validated['name'],
            'primary_category_id' => $validated['primary_category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'published_at' => ($validated['status'] === 'published' && ! $product->published_at) ? now() : $product->published_at,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        $product->tags()->sync($validated['tags'] ?? []);

        if (isset($validated['category_ids'])) {
            $categoriesToSync = array_unique(array_filter(array_merge(
                [$validated['primary_category_id']],
                $validated['category_ids']
            )));
            $product->categories()->sync($categoriesToSync);
        }

        // Sync product-level attribute values
        $this->syncProductAttributes($product, $validated['product_attributes'] ?? []);

        // Delete specified media
        if (! empty($validated['delete_media_ids'])) {
            $toDelete = ProductMedia::where('product_id', $product->id)
                ->whereIn('id', $validated['delete_media_ids'])
                ->get();

            foreach ($toDelete as $media) {
                if ($media->path && Storage::disk('public')->exists($media->path)) {
                    Storage::disk('public')->delete($media->path);
                }
                $media->delete();
            }
        }

        // Update media sort orders
        if (! empty($request->input('media_sort_orders'))) {
            foreach ($request->input('media_sort_orders') as $mId => $sortOrder) {
                ProductMedia::where('product_id', $product->id)
                    ->where('id', (int) $mId)
                    ->update(['sort_order' => (int) $sortOrder]);
            }
        }

        // Upload new images
        $hasExistingMedia = $product->media()->exists();
        $isFirst = ! $hasExistingMedia;

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imgFile) {
                $path = $imgFile->store('products', 'public');
                $url = Storage::disk('public')->url($path);
                $mime = $imgFile->getClientMimeType() ?: 'image/jpeg';
                $size = $imgFile->getSize() ?: 0;
                $dimensions = null;
                $info = @getimagesize($imgFile->getRealPath());
                if ($info) {
                    $dimensions = "{$info[0]}x{$info[1]}";
                }

                ProductMedia::create([
                    'product_id' => $product->id,
                    'url' => $url,
                    'path' => $path,
                    'mime_type' => $mime,
                    'is_primary' => $isFirst,
                    'sort_order' => 0,
                ]);

                MediaAsset::create([
                    'name' => $imgFile->getClientOriginalName(),
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => $url,
                    'disk' => 'public',
                    'mime_type' => $mime,
                    'size' => $size,
                    'dimensions' => $dimensions,
                    'folder' => 'products',
                    'user_id' => auth()->id(),
                ]);

                $isFirst = false;
            }
        }

        // Attach media from library
        if (! empty($validated['media_asset_ids'])) {
            foreach ($validated['media_asset_ids'] as $assetId) {
                $asset = MediaAsset::find($assetId);
                if ($asset) {
                    ProductMedia::create([
                        'product_id' => $product->id,
                        'url' => $asset->url,
                        'path' => $asset->path,
                        'mime_type' => $asset->mime_type,
                        'is_primary' => $isFirst,
                        'sort_order' => 0,
                    ]);
                    $isFirst = false;
                }
            }
        }

        // Update primary image selection
        if (! empty($validated['primary_media_id'])) {
            ProductMedia::where('product_id', $product->id)->update(['is_primary' => false]);
            ProductMedia::where('product_id', $product->id)
                ->where('id', $validated['primary_media_id'])
                ->update(['is_primary' => true]);
        } elseif ($product->media()->count() > 0 && ! $product->media()->where('is_primary', true)->exists()) {
            $product->media()->first()?->update(['is_primary' => true]);
        }

        // Handle document deletions
        if (! empty($validated['delete_document_ids'])) {
            $toDeleteDocs = ProductDocument::where('product_id', $product->id)
                ->whereIn('id', $validated['delete_document_ids'])
                ->get();

            foreach ($toDeleteDocs as $doc) {
                if ($doc->path && Storage::disk('public')->exists($doc->path)) {
                    Storage::disk('public')->delete($doc->path);
                }
                $doc->delete();
            }
        }

        // Handle document uploads
        if ($request->hasFile('documents')) {
            $docTitles = $request->input('document_titles', []);
            foreach ($request->file('documents') as $idx => $docFile) {
                $path = $docFile->store('products/documents', 'public');
                $url = Storage::disk('public')->url($path);
                $title = $docTitles[$idx] ?? $docFile->getClientOriginalName();
                $mime = $docFile->getClientMimeType() ?: 'application/pdf';

                ProductDocument::create([
                    'product_id' => $product->id,
                    'title' => $title,
                    'url' => $url,
                    'path' => $path,
                    'file_type' => $mime,
                    'sort_order' => 0,
                ]);
            }
        }

        // Handle variant deletions/archivals
        if (! empty($validated['delete_variant_ids'])) {
            foreach ($validated['delete_variant_ids'] as $delId) {
                $variantToDelete = ProductVariant::where('product_id', $product->id)->find($delId);
                if ($variantToDelete) {
                    if ($variantToDelete->orderItems()->exists() || $variantToDelete->inventoryItems()->exists()) {
                        $variantToDelete->update(['status' => 'archived']);
                    } else {
                        $variantToDelete->delete();
                    }
                }
            }
        }

        // Handle variant updates / creations
        if (! empty($validated['variants'])) {
            foreach ($validated['variants'] as $v) {
                $variantData = [
                    'name' => $v['name'],
                    'sku' => $v['sku'],
                    'mrp' => (int) round((float) $v['mrp'] * 100),
                    'selling_price' => (int) round((float) $v['selling_price'] * 100),
                    'unit' => $v['unit'],
                    'pack_size' => ! empty($v['pack_size']) ? $v['pack_size'] : $v['unit'],
                    'weight_kg' => isset($v['weight_kg']) && is_numeric($v['weight_kg']) ? (float) $v['weight_kg'] : null,
                    'barcode' => ! empty($v['barcode']) ? $v['barcode'] : null,
                    'tax_class_id' => ! empty($v['tax_class_id']) ? (int) $v['tax_class_id'] : null,
                    'status' => $v['status'] ?? 'active',
                ];

                if (! empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)->find($v['id']);
                    if ($variant) {
                        $oldPrice = ['mrp' => $variant->mrp, 'selling_price' => $variant->selling_price];
                        $variant->update($variantData);
                        if ($oldPrice['selling_price'] !== $variantData['selling_price'] || $oldPrice['mrp'] !== $variantData['mrp']) {
                            $this->audit->priceChanged($variant, $oldPrice, [
                                'mrp' => $variantData['mrp'],
                                'selling_price' => $variantData['selling_price'],
                            ]);
                        }
                    }
                } else {
                    $variant = ProductVariant::create(array_merge($variantData, [
                        'product_id' => $product->id,
                    ]));
                }

                // Sync quantity price tiers if provided
                if ($variant && array_key_exists('tiers', $v)) {
                    $variant->quantityTiers()->delete();
                    if (is_array($v['tiers'])) {
                        foreach ($v['tiers'] as $tier) {
                            if (! empty($tier['min_quantity']) && isset($tier['unit_price']) && is_numeric($tier['unit_price'])) {
                                $variant->quantityTiers()->create([
                                    'min_quantity' => (int) $tier['min_quantity'],
                                    'max_quantity' => ! empty($tier['max_quantity']) ? (int) $tier['max_quantity'] : null,
                                    'unit_price' => (int) round((float) $tier['unit_price'] * 100),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        if ($request->input('action') === 'save_and_continue') {
            return redirect()->route('admin.catalog.products.edit', $product->id)
                ->with('success', "Product [{$product->name}] saved successfully.");
        }

        return redirect()->route('admin.catalog.products.index')
            ->with('success', "Product [{$product->name}] updated successfully.");
    }

    /**
     * Replace all product-level attribute values with the submitted set.
     *
     * @param  array<int, array{attribute_definition_id: int, attribute_value_id: ?int, value_text: ?string, value_number: ?float|string, value_boolean: ?bool|string}>  $attributeData
     */
    private function syncProductAttributes(Product $product, array $attributeData): void
    {
        // Delete all existing product attribute values
        $product->attributeValues()->delete();

        foreach ($attributeData as $attr) {
            $definitionId = (int) $attr['attribute_definition_id'];
            if (! $definitionId) {
                continue;
            }

            $product->attributeValues()->create([
                'attribute_definition_id' => $definitionId,
                'attribute_value_id' => ! empty($attr['attribute_value_id']) ? (int) $attr['attribute_value_id'] : null,
                'value_text' => $attr['value_text'] ?? null,
                'value_number' => isset($attr['value_number']) && is_numeric($attr['value_number']) ? (float) $attr['value_number'] : null,
                'value_boolean' => isset($attr['value_boolean']) ? (bool) $attr['value_boolean'] : null,
            ]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->delete();

        return redirect()->route('admin.catalog.products.index')
            ->with('success', "Product [{$name}] deleted.");
    }

    public function destroyMedia(int $productId, int $mediaId): RedirectResponse|JsonResponse
    {
        $media = ProductMedia::where('product_id', $productId)->where('id', $mediaId)->firstOrFail();
        $wasPrimary = $media->is_primary;

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();

        if ($wasPrimary) {
            ProductMedia::where('product_id', $productId)->first()?->update(['is_primary' => true]);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Image removed.']);
        }

        return back()->with('success', 'Product image removed.');
    }

    public function setPrimaryMedia(int $productId, int $mediaId): RedirectResponse|JsonResponse
    {
        ProductMedia::where('product_id', $productId)->update(['is_primary' => false]);
        ProductMedia::where('product_id', $productId)->where('id', $mediaId)->update(['is_primary' => true]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Primary image updated.']);
        }

        return back()->with('success', 'Primary image updated.');
    }
}
