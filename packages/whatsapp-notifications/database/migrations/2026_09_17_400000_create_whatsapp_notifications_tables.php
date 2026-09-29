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
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique();
            $table->string('channel')->default('whatsapp'); // whatsapp, sms
            $table->string('name');
            $table->text('content');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone');
            $table->string('recipient_name')->nullable();
            $table->string('channel')->default('whatsapp'); // whatsapp, sms
            $table->string('template_name')->nullable();
            $table->json('parameters')->nullable();
            $table->text('rendered_message');
            $table->string('gateway_message_id')->nullable()->index();
            $table->string('status')->default('sent'); // queued, sent, delivered, read, failed
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('communication_templates');
    }
};
