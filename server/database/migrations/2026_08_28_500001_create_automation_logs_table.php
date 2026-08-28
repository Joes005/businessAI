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
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('trigger_type', 40); // LOW_STOCK_ALERT, DEBT_REMINDER, DAILY_SUMMARY
            $table->string('recipient')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('SENT'); // SENT, FAILED, PENDING
            $table->timestamps();

            $table->index(['business_id', 'trigger_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
