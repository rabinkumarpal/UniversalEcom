<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('method')->default('cod'); // razorpay, cod, advance_deposit, credit_terms
            $table->integer('expected_amount'); // minor units / paise
            $table->integer('collected_amount')->default(0);
            $table->integer('advance_amount')->default(0);
            $table->integer('balance_amount')->default(0);
            $table->string('settlement_status')->default('pending'); // pending, partial, settled, discrepancy
            $table->foreignId('collected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('discrepancy_reason')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_id')->unique(); // razorpay, advanced_cod
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_test_mode')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
        Schema::dropIfExists('payment_reconciliations');
    }
};
