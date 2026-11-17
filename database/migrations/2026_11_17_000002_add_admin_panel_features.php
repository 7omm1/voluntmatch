<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funcionalidades del panel de administracion: moderacion de cuentas,
 * verificacion de ONG, curaduria de oportunidades, reportes, soporte,
 * anuncios, bitacora de acciones y configuracion persistente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->index('blocked_at');
        });

        Schema::table('organizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('verificada_por')->nullable();
            $table->timestamp('verificada_at')->nullable();
            $table->string('verificacion_nota')->nullable();
        });

        Schema::table('oportunidades', function (Blueprint $table) {
            // borrador | publicada | archivada
            $table->string('estado')->default('publicada');
            $table->boolean('destacada')->default(false);
            $table->unsignedInteger('vistas')->default(0);

            $table->index('estado');
        });

        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('oportunidad_id')->nullable()->constrained('oportunidades')->nullOnDelete();
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo');
            $table->text('detalle')->nullable();
            // pendiente | resuelto | descartado
            $table->string('estado')->default('pendiente');
            $table->text('resolucion')->nullable();
            $table->unsignedBigInteger('resuelto_por')->nullable();
            $table->timestamps();

            $table->index('estado');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('asunto');
            $table->text('mensaje');
            // alta | media | baja
            $table->string('prioridad')->default('media');
            // abierto | en_proceso | cerrado
            $table->string('estado')->default('abierto');
            $table->text('respuesta')->nullable();
            $table->unsignedBigInteger('atendido_por')->nullable();
            $table->timestamps();

            $table->index(['estado', 'prioridad']);
        });

        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('cuerpo');
            // todos | voluntario | organizacion
            $table->string('audiencia')->default('todos');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->string('entidad')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->text('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['entidad', 'entidad_id']);
        });

        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->json('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('anuncios');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('reportes');

        Schema::table('oportunidades', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn(['estado', 'destacada', 'vistas']);
        });

        Schema::table('organizaciones', function (Blueprint $table) {
            $table->dropColumn(['verificada_por', 'verificada_at', 'verificacion_nota']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['blocked_at']);
            $table->dropColumn(['blocked_at', 'blocked_reason', 'last_login_at', 'admin_notes']);
        });
    }
};
