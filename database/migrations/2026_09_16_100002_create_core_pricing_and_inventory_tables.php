<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quantity_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->integer('unit_price'); // minor units (cents / paise)
            $table->timestamps();

            $table->index(['product_variant_id', 'min_quantity', 'max_quantity'], 'variant_qty_tier_index');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('available')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_variant_id']);
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('cart_token')->nullable()->index();
            $table->integer('quantity');
            $table->string('status')->default('active')->index(); // active, released, consumed
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index(); // in, out, reservation, release, adjustment
            $table->integer('quantity'); // positive for additions, negative for reductions
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('quantity_price_tiers');
    }
};
