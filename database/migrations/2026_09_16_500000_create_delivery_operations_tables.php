<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('shipment_number')->unique();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->index();
            // pending, picking, packed, in_transit, out_for_delivery, delivered, failed
            $table->string('carrier_or_driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Proof of Delivery (POD) fields
            $table->string('pod_recipient_name')->nullable();
            $table->text('pod_signature')->nullable();
            $table->string('pod_otp')->nullable();
            $table->string('pod_photo_path')->nullable();
            $table->decimal('pod_latitude', 10, 7)->nullable();
            $table->decimal('pod_longitude', 10, 7)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('exception_code')->index();
            // CUSTOMER_UNAVAILABLE, WRONG_ADDRESS, PINCODE_NOT_SERVICEABLE, STOCK_SHORTAGE, VEHICLE_ISSUE
            $table->text('notes');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_exceptions');
        Schema::dropIfExists('shipments');
    }
};
