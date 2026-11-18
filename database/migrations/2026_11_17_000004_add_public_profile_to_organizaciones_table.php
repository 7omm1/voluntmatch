<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ficha publica de la ONG: datos que necesita el directorio y el mapa.
 * El slug identifica la URL publica (/ongs/mi-organizacion) sin exponer
 * el id secuencial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizaciones', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nombre');
            $table->text('descripcion')->nullable()->after('mision');
            $table->text('como_participar')->nullable()->after('descripcion');
            $table->string('ciudad')->nullable()->after('como_participar');
            $table->string('direccion')->nullable()->after('ciudad');
            $table->decimal('latitud', 10, 7)->nullable()->after('direccion');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->string('telefono', 30)->nullable()->after('longitud');
            $table->string('sitio_web')->nullable()->after('telefono');
            $table->string('logo_url')->nullable()->after('sitio_web');
            $table->string('portada_url')->nullable()->after('logo_url');
            $table->unsignedSmallInteger('anio_fundacion')->nullable()->after('portada_url');
        });

        // Rellenamos las filas existentes para que sus enlaces publicos
        // funcionen desde el primer despliegue.
        $usados = [];

        DB::table('organizaciones')->select('id', 'nombre')->orderBy('id')->get()
            ->each(function (object $fila) use (&$usados) {
                $base = Str::slug($fila->nombre) ?: 'ong';
                $slug = $base;
                $sufijo = 2;

                while (in_array($slug, $usados, true)) {
                    $slug = $base.'-'.$sufijo++;
                }

                $usados[] = $slug;

                DB::table('organizaciones')->where('id', $fila->id)->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        Schema::table('organizaciones', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'descripcion',
                'como_participar',
                'ciudad',
                'direccion',
                'latitud',
                'longitud',
                'telefono',
                'sitio_web',
                'logo_url',
                'portada_url',
                'anio_fundacion',
            ]);
        });
    }
};
