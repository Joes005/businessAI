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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable(); // Foreign key to customers in Phase 5
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_number');
            $table->string('customer_name')->default('Walk-in Customer');
            $table->string('customer_phone')->nullable();
            $table->date('date');
            $table->decimal('subtotal', 12, 2);
            $table->string('discount_type', 10)->default('flat'); // flat, percent
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_percent', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('balance_due', 12, 2);
            $table->string('payment_method', 30)->default('CASH'); // CASH, CARD, UPI, BANK_TRANSFER, CREDIT
            $table->string('payment_status', 20)->default('PAID'); // PAID, PARTIAL, UNPAID
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'invoice_number']);
            $table->index(['business_id', 'date']);
            $table->index(['business_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
