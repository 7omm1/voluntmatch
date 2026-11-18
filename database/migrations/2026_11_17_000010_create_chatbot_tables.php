<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base de datos del asistente virtual.
 *
 * Se separa en cuatro bloques para poder auditar cada capa:
 * - conversaciones y mensajes: la experiencia de usuario;
 * - trazas y llamadas a herramientas: observabilidad;
 * - documentos y fragmentos: el corpus RAG.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_key', 64)->index();
            $table->string('role', 20)->default('guest');
            $table->string('channel', 20)->default('web');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('escalated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('role', 20);
            $table->text('content');
            $table->string('intent', 40)->nullable();
            $table->string('route', 40)->nullable();
            $table->json('citations')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('guardrails')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        Schema::create('chatbot_traces', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->unique();
            $table->foreignId('conversation_id')->nullable()->constrained('chatbot_conversations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience', 20)->default('public');
            $table->string('intent', 40)->nullable()->index();
            $table->string('route', 40)->nullable()->index();
            $table->string('model', 80)->nullable();
            $table->boolean('cache_hit')->default(false);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->json('retrieved')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('guardrails')->nullable();
            $table->string('status', 20)->default('ok')->index();
            $table->text('error')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('chatbot_tool_calls', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->index();
            $table->foreignId('conversation_id')->nullable()->constrained('chatbot_conversations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tool', 100);
            $table->string('server', 50)->default('local');
            $table->json('arguments')->nullable();
            $table->string('status', 20)->default('ok')->index();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->text('result_summary')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('chatbot_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 180);
            $table->longText('body');
            $table->json('audiences');
            $table->json('tags')->nullable();
            $table->string('source', 180)->nullable();
            $table->string('version', 40)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('chatbot_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('chatbot_knowledge_documents')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('content');
            $table->unsignedInteger('tokens')->default(0);
            $table->json('terms')->nullable();
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge_chunks');
        Schema::dropIfExists('chatbot_knowledge_documents');
        Schema::dropIfExists('chatbot_tool_calls');
        Schema::dropIfExists('chatbot_traces');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
    }
};
