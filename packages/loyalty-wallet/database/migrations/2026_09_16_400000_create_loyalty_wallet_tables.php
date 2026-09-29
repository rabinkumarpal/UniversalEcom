<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customer Wallet Accounts
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->bigInteger('balance')->default(0); // in minor units (paise/cents)
            $table->string('currency', 3)->default('INR');
            $table->string('status', 20)->default('active'); // active, frozen, closed
            $table->timestamps();
        });

        // 2. Append-Only Financial Ledger for Wallets
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_account_id')->constrained('wallet_accounts')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->bigInteger('amount'); // absolute minor units
            $table->bigInteger('balance_after');
            $table->string('reference_type', 50); // order_payment, cashback, refund, manual_adjustment, topup
            $table->string('reference_id', 100)->nullable();
            $table->string('description', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wallet_account_id', 'created_at']);
        });

        // 3. Product Reviews & Ratings with Verified Buyer Status
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1 to 5
            $table->string('title', 150)->nullable();
            $table->text('comment');
            $table->boolean('is_verified_buyer')->default(false);
            $table->string('status', 20)->default('approved'); // approved, pending, rejected
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        // 4. Customer Wishlists
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('wallet_ledger_entries');
        Schema::dropIfExists('wallet_accounts');
    }
};
