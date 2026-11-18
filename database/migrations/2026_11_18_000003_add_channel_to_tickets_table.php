<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La mesa de ayuda recibe casos desde la web, el asistente y sistemas
 * externos. El canal y la referencia externa permiten saber de dónde vino
 * cada caso y reconciliarlo con el proveedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('canal', 30)->default('web')->index();
            $table->string('external_ref', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['canal', 'external_ref']);
        });
    }
};
