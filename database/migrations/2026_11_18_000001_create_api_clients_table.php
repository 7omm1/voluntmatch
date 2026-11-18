<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consumidores de la API del asistente y del servidor MCP.
 *
 * El token nunca se guarda en claro: se persiste su hash SHA-256 y un
 * prefijo visible para poder identificarlo en la lista del panel. Cada
 * cliente queda atado a un rol y, si accede a datos privados, a una cuenta
 * concreta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('key_prefix', 16)->index();
            $table->string('key_hash', 64)->unique();
            $table->string('role', 20)->default('public');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('scopes');
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
