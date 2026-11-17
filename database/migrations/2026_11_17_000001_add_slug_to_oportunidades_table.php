<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Las fichas publicas usan slug en la URL en lugar del id secuencial.
     * Se rellenan las filas existentes para no romper una base ya sembrada.
     */
    public function up(): void
    {
        Schema::table('oportunidades', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('titulo');
        });

        $usados = [];

        foreach (DB::table('oportunidades')->orderBy('id')->get() as $fila) {
            $base = Str::slug($fila->titulo) ?: 'oportunidad-'.$fila->id;
            $slug = $base;
            $sufijo = 2;

            while (in_array($slug, $usados, true)) {
                $slug = $base.'-'.$sufijo++;
            }

            $usados[] = $slug;
            DB::table('oportunidades')->where('id', $fila->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('oportunidades', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
