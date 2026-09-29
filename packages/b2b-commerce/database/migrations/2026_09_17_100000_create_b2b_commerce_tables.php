<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->string('name');
            $table->string('company_code')->unique();
            $table->string('tax_id')->nullable();
            $table->string('status')->default('active')->index();
            $table->integer('credit_limit')->default(0);
            $table->integer('credit_balance')->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->timestamps();
        });

        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('buyer')->index(); // admin, approver, buyer
            $table->integer('spending_limit')->nullable(); // in minor units paise; null = no spending limit
            $table->boolean('is_active')->default(true);
            $table->unique(['company_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('contract_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->cascadeOnDelete();
            $table->string('name');
            $table->string('currency')->default('INR');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('contract_price_lists')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('custom_price'); // in minor units paise
            $table->integer('min_quantity')->default(1);
            $table->index(['price_list_id', 'product_variant_id']);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('po_number')->index();
            $table->string('status')->default('pending_approval')->index(); // pending_approval, approved, rejected, invoiced, paid
            $table->integer('amount'); // minor units paise
            $table->integer('payment_terms_days')->default(30);
            $table->date('due_date')->nullable();
            $table->foreignId('requester_user_id')->constrained('users');
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('contract_variant_prices');
        Schema::dropIfExists('contract_price_lists');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('customer_groups');
    }
};
