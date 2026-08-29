<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. AI Controlled Task Engine & State Machine
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('goal');
            $table->enum('status', [
                'PENDING',
                'PLANNING',
                'WAITING_FOR_CONFIRMATION',
                'EXECUTING',
                'VERIFYING',
                'COMPLETED',
                'FAILED',
                'CANCELLED'
            ])->default('PENDING');
            $table->json('plan')->nullable();
            $table->integer('current_step_index')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        // 2. AI Task Steps
        Schema::create('ai_task_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_task_id')->constrained('ai_tasks')->onDelete('cascade');
            $table->integer('step_number');
            $table->string('tool_name');
            $table->json('arguments')->nullable();
            $table->enum('risk_level', ['SAFE_READ', 'LOW_RISK_WRITE', 'HIGH_RISK_WRITE'])->default('SAFE_READ');
            $table->enum('status', ['PENDING', 'EXECUTING', 'VERIFYING', 'COMPLETED', 'FAILED', 'SKIPPED'])->default('PENDING');
            $table->text('result')->nullable();
            $table->text('error')->nullable();
            $table->enum('verification_status', ['UNVERIFIED', 'PASSED', 'FAILED'])->default('UNVERIFIED');
            $table->timestamps();

            $table->index(['ai_task_id', 'step_number']);
        });

        // 3. AI Business Profiles (Brain Snapshots)
        Schema::create('ai_business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->json('profile_data');
            $table->timestamps();
        });

        // 4. AI Business Goals
        Schema::create('ai_business_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('title');
            $table->string('metric_key'); // e.g. monthly_sales, uncollected_debt
            $table->decimal('baseline_value', 15, 2)->default(0);
            $table->decimal('target_value', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->enum('status', ['ACTIVE', 'ACHIEVED', 'FAILED', 'CANCELLED'])->default('ACTIVE');
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        // 5. AI Predictions & Forecasts
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('type'); // SALES_FORECAST, STOCKOUT_PREDICTION, CHURN_RISK
            $table->string('target');
            $table->json('prediction_data');
            $table->decimal('confidence_score', 5, 2)->default(80.00); // %
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'type']);
        });

        // 6. AI Business Events
        Schema::create('ai_business_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('event_type'); // SALE_CREATED, INVENTORY_LOW, PAYMENT_OVERDUE
            $table->json('payload')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'processed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_business_events');
        Schema::dropIfExists('ai_predictions');
        Schema::dropIfExists('ai_business_goals');
        Schema::dropIfExists('ai_business_profiles');
        Schema::dropIfExists('ai_task_steps');
        Schema::dropIfExists('ai_tasks');
    }
};
