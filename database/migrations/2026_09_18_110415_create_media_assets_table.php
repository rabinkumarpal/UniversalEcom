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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('filename')->nullable();
            $table->string('path')->nullable();
            $table->string('url');
            $table->string('disk')->default('public');
            $table->string('mime_type')->default('image/jpeg');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('dimensions')->nullable();
            $table->string('folder')->default('general')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
