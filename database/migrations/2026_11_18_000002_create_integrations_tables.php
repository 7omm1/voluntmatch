<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integraciones: bitácora de entregas, CRM local y notificaciones.
 *
 * Cada intento de correo, calendario, CRM, mesa de ayuda o notificación
 * deja una fila en integration_deliveries para poder auditarlo y reintentar
 * sin guardar credenciales ni cuerpos completos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 30);
            $table->string('action', 60);
            $table->string('driver', 30)->default('local');
            $table->string('status', 20)->default('sent')->index();
            $table->string('target', 180)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->string('related_type', 80)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('created_at');
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre', 160);
            $table->string('email', 180);
            $table->string('telefono', 30)->nullable();
            $table->string('organizacion_nombre', 160)->nullable();
            $table->string('etapa', 30)->default('nuevo')->index();
            $table->string('fuente', 60)->nullable();
            $table->text('notas')->nullable();
            $table->string('external_ref', 120)->nullable();
            $table->timestamps();

            $table->index('email');
            $table->unique(['owner_id', 'email']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('integration_deliveries');
    }
};
