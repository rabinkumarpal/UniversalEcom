<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->nullable()->unique();
            $table->string('type')->index(); // percentage, fixed, bogo, bundle, spending_goal, free_shipping
            $table->string('status')->default('active')->index(); // draft, active, expired, paused
            $table->integer('priority')->default(10);
            $table->boolean('stackable')->default(false);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_customer')->nullable();
            $table->json('configuration')->nullable(); // discount_percentage, fixed_amount, buy_qty, get_qty, reward_variant_id, goal_amount, etc.
            $table->timestamps();
        });

        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->string('rule_type'); // min_subtotal, required_category, required_variant, customer_group
            $table->string('operator')->default('='); // =, >=, <=, in
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('discount_amount');
            $table->timestamps();
        });

        Schema::create('promotion_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reward_type'); // next_order_coupon, cashback, free_product
            $table->json('reward_payload');
            $table->string('status')->default('active'); // active, redeemed, expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('max_uses')->default(1);
            $table->integer('uses_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotion_rewards');
        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('promotions');
    }
};
