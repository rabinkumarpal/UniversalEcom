<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token')->index();
            $table->string('status')->default('active')->index(); // active, abandoned, converted
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->json('custom_options')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending_payment')->index();
            // draft, pending_payment, paid, confirmed, picking, packed, dispatched, out_for_delivery, delivered, cancelled, refunded
            $table->string('payment_status')->default('pending')->index();
            // pending, authorized, captured, failed, refunded
            $table->string('fulfillment_status')->default('unfulfilled')->index();
            // unfulfilled, partial, fulfilled, returned
            $table->string('currency')->default('INR');
            $table->integer('subtotal'); // stored in minor units
            $table->integer('discount_total')->default(0);
            $table->integer('tax_total')->default(0);
            $table->integer('delivery_fee')->default(0);
            $table->integer('grand_total');
            $table->json('billing_address_snapshot');
            $table->json('shipping_address_snapshot');
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku_snapshot');
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot');
            $table->integer('unit_price'); // in minor units
            $table->integer('quantity');
            $table->integer('discount')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('line_total');
            $table->json('metadata')->nullable(); // contains applied promotion snapshots, vendor reference, etc.
            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status');
            $table->string('new_status');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->index(); // cod, razorpay, stripe, etc.
            $table->string('transaction_id')->nullable()->index();
            $table->integer('amount'); // minor units
            $table->string('currency')->default('INR');
            $table->string('status')->default('pending')->index(); // pending, authorized, captured, failed, refunded
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->integer('amount'); // minor units
            $table->string('status')->default('issued')->index(); // issued, paid, cancelled
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('entity_type')->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
