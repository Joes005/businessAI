<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. AI Conversations
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        // 2. AI Messages
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->enum('sender', ['user', 'copilot', 'system']);
            $table->text('text');
            $table->json('metrics')->nullable();
            $table->string('data_type')->nullable();
            $table->json('data')->nullable();
            $table->json('suggested_actions')->nullable();
            $table->json('tool_calls')->nullable();
            $table->timestamps();
        });

        // 3. AI Memories (Business context & operational preferences)
        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('category')->default('business'); // business, user_preference, operational
            $table->string('key');
            $table->text('value');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'category']);
        });

        // 4. AI Proactive Insights
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('type'); // STOCK_WARNING, DEBT_WARNING, SALES_DROP, EXPENSE_SPIKE, OPPORTUNITY
            $table->string('title');
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->text('problem');
            $table->text('impact');
            $table->text('recommendation');
            $table->json('supporting_data')->nullable();
            $table->enum('status', ['active', 'dismissed', 'resolved'])->default('active');
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        // 5. AI Actions & Action Confirmation System
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('action_type'); // create_invoice, send_reminder, update_stock, etc.
            $table->text('description');
            $table->json('payload');
            $table->enum('risk_level', ['SAFE_READ', 'LOW_RISK_WRITE', 'HIGH_RISK_WRITE'])->default('SAFE_READ');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'EXECUTED', 'FAILED'])->default('PENDING');
            $table->text('execution_result')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        // 6. AI Tool Logs (Audit Logging & Multi-Tenant Security)
        Schema::create('ai_tool_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('tool_name');
            $table->json('arguments')->nullable();
            $table->integer('execution_time_ms')->default(0);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'tool_name']);
        });

        // 7. AI Knowledge Base Documents & Chunks (RAG Layer)
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('title');
            $table->string('category')->default('general'); // policy, sop, product_info, faq
            $table->timestamps();
        });

        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('ai_knowledge_documents')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
        });

        // 8. AI Evaluation Benchmarks
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // Q&A, ANALYSIS, TOOL_SELECTION, SAFETY, TANGLISH, HALLUCINATION
            $table->text('user_prompt');
            $table->string('expected_intent')->nullable();
            $table->string('expected_tool')->nullable();
            $table->text('expected_outcome_summary')->nullable();
            $table->boolean('passed')->nullable();
            $table->text('actual_response')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluations');
        Schema::dropIfExists('ai_knowledge_chunks');
        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_tool_logs');
        Schema::dropIfExists('ai_actions');
        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('ai_memories');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
