<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gst_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('financial_year')->index();
            $table->unsignedInteger('sequence_number')->index();
            $table->timestamp('invoice_date');
            $table->string('supply_type')->default('INTRA_STATE')->index(); // INTRA_STATE, INTER_STATE
            $table->boolean('is_b2b')->default(false)->index();
            $table->boolean('reverse_charge')->default(false);

            // Supplier / Seller Details
            $table->string('seller_name')->default('Universal Commerce Pvt Ltd');
            $table->string('seller_gstin')->default('29AAAAA0000A1Z5');
            $table->text('seller_address')->nullable();
            $table->string('seller_city')->default('Bengaluru');
            $table->string('seller_state')->default('Karnataka');
            $table->string('seller_state_code', 2)->default('29');
            $table->string('seller_pincode')->default('560001');

            // Buyer / Customer Details
            $table->string('buyer_name');
            $table->string('buyer_gstin')->nullable()->index();
            $table->string('buyer_phone')->nullable();
            $table->json('buyer_billing_address')->nullable();
            $table->json('buyer_shipping_address')->nullable();
            $table->string('place_of_supply_state');
            $table->string('place_of_supply_state_code', 2);

            // Statutory Totals (in minor integer units: paise)
            $table->integer('taxable_amount')->default(0);
            $table->integer('cgst_amount')->default(0);
            $table->integer('sgst_amount')->default(0);
            $table->integer('igst_amount')->default(0);
            $table->integer('cess_amount')->default(0);
            $table->integer('round_off_amount')->default(0);
            $table->integer('total_amount');

            // Verification & E-Invoicing
            $table->string('irn_hash', 64)->nullable()->index();
            $table->text('qr_code_payload')->nullable();
            $table->string('status')->default('issued')->index(); // issued, cancelled
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();
        });

        Schema::create('gst_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gst_invoice_id')->constrained('gst_invoices')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('item_description');
            $table->string('hsn_code', 10)->default('9999')->index();
            $table->integer('quantity');
            $table->string('unit')->default('unit');
            $table->integer('unit_price'); // minor units
            $table->integer('discount_amount')->default(0);
            $table->integer('taxable_value'); // (unit_price * qty) - discount
            $table->decimal('gst_rate', 5, 2)->default(18.00);

            // Taxes
            $table->decimal('cgst_rate', 5, 2)->default(0.00);
            $table->integer('cgst_amount')->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0.00);
            $table->integer('sgst_amount')->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0.00);
            $table->integer('igst_amount')->default(0);

            $table->integer('total_amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gst_invoice_items');
        Schema::dropIfExists('gst_invoices');
    }
};
