<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('license_number');
            $table->string('vehicle_type')->default('Flatbed Truck');
            $table->string('vehicle_number')->index();
            $table->string('status')->default('active')->index(); // active, off_duty, suspended
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('warehouse_id')->constrained('drivers')->nullOnDelete();
            $table->string('delivery_otp', 6)->nullable()->after('status')->index();
            $table->longText('pod_signature_data')->nullable()->after('pod_signature');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'delivery_otp', 'pod_signature_data']);
        });

        Schema::dropIfExists('drivers');
    }
};
