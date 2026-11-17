<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nota interna que la ONG guarda sobre una postulacion. Es privada del
 * equipo y nunca se muestra al estudiante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulaciones', function (Blueprint $table) {
            $table->text('nota_interna')->nullable()->after('nota');
        });
    }

    public function down(): void
    {
        Schema::table('postulaciones', function (Blueprint $table) {
            $table->dropColumn('nota_interna');
        });
    }
};
