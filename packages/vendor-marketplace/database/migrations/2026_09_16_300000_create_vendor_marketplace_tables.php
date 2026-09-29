<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('status')->default('pending')->index(); // pending, active, suspended
            $table->string('approval_status')->default('pending')->index(); // pending, approved, rejected
            $table->decimal('commission_rate_percentage', 5, 2)->default(10.00);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('owner'); // owner, staff
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['vendor_id', 'user_id']);
        });

        Schema::create('vendor_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('vendor_sku')->nullable();
            $table->integer('vendor_price'); // minor units
            $table->integer('vendor_mrp')->nullable();
            $table->string('status')->default('approved')->index(); // pending, approved, rejected, inactive
            $table->timestamps();

            $table->unique(['vendor_id', 'product_variant_id']);
        });

        Schema::create('vendor_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('available')->default(0);
            $table->timestamps();

            $table->unique(['vendor_id', 'product_variant_id', 'warehouse_id'], 'vendor_inv_unique');
        });

        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('vendor_order_number')->unique();
            $table->string('status')->default('confirmed')->index(); // confirmed, picking, packed, dispatched, delivered, cancelled
            $table->integer('subtotal'); // minor units
            $table->integer('commission_amount'); // minor units
            $table->integer('vendor_payout'); // minor units
            $table->timestamps();
        });

        Schema::create('vendor_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('unit_price');
            $table->integer('commission_amount');
            $table->integer('payout_amount');
            $table->timestamps();
        });

        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('payout_number')->unique();
            $table->integer('amount'); // minor units
            $table->string('status')->default('pending')->index(); // pending, approved, paid
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('vendor_order_items');
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('vendor_inventory');
        Schema::dropIfExists('vendor_offers');
        Schema::dropIfExists('vendor_users');
        Schema::dropIfExists('vendors');
    }
};
