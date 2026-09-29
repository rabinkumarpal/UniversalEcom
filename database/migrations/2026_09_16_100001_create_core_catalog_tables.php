<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('active')->index(); // active, inactive
            $table->integer('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate_percentage', 5, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('select'); // text, number, select, multi_select, boolean, measurement
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_variant_attribute')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('label')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('primary_category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('status')->default('draft')->index(); // draft, published, archived
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'category_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->string('unit')->default('piece'); // piece, kg, metre, litre, etc.
            $table->string('pack_size')->default('1');
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->json('dimensions')->nullable(); // length, width, height
            $table->integer('mrp'); // stored in minor units (cents / paise)
            $table->integer('selling_price'); // stored in minor units (cents / paise)
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->nullOnDelete();
            $table->string('value_text')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();
        });

        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->nullOnDelete();
            $table->string('value_text')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('path')->nullable();
            $table->string('mime_type')->default('image/jpeg');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('path')->nullable();
            $table->string('file_type')->default('application/pdf');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('variant_attribute_values');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_definitions');
        Schema::dropIfExists('tax_classes');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
