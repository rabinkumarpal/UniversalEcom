<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('base_fee')->default(0); // minor units
            $table->integer('min_order_free_shipping')->nullable(); // minor units
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('delivery_zone_pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->string('pincode')->index();
            $table->timestamps();

            $table->unique(['delivery_zone_id', 'pincode']);
        });

        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Morning 9 AM - 1 PM", "Express Same-Day"
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_orders_per_day')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_slots');
        Schema::dropIfExists('delivery_zone_pincodes');
        Schema::dropIfExists('delivery_zones');
    }
};
