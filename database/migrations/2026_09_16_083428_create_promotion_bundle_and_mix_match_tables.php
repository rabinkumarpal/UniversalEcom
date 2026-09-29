<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('required_quantity')->default(1);
            $table->timestamps();

            $table->index('promotion_id');
        });

        Schema::create('promotion_mix_match_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->timestamps();

            $table->index('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_mix_match_items');
        Schema::dropIfExists('promotion_bundle_items');
    }
};
