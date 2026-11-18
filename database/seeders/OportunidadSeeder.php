<?php

namespace Database\Seeders;

use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Support\OportunidadesDemo;
use App\Support\Peru;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Siembra el catalogo de demostracion: tres ONG y diez oportunidades
 * distintas por ONG en la tabla real `oportunidades` (y las
 * organizaciones que las publican en `organizaciones`).
 *
 * Es idempotente: puede correrse varias veces sin duplicar filas.
 */
class OportunidadSeeder extends Seeder
{
    public function run(): void
    {
        $organizaciones = [];

        foreach (OportunidadesDemo::organizaciones() as $datos) {
            $organizaciones[$datos['nombre']] = Organizacion::updateOrCreate(
                ['ruc' => $datos['ruc']],
                [
                    'nombre' => $datos['nombre'],
                    'slug' => Str::slug($datos['nombre']),
                    'sector' => $datos['sector'],
                    'mision' => $datos['mision'],
                    'descripcion' => $datos['descripcion'] ?? null,
                    'como_participar' => $datos['como_participar'] ?? null,
                    'ciudad' => $datos['ciudad'] ?? null,
                    'direccion' => $datos['direccion'] ?? null,
                    'latitud' => $datos['latitud'] ?? null,
                    'longitud' => $datos['longitud'] ?? null,
                    'telefono' => $datos['telefono'] ?? null,
                    'sitio_web' => $datos['sitio_web'] ?? null,
                    'portada_url' => $datos['portada_url'] ?? null,
                    'anio_fundacion' => $datos['anio_fundacion'] ?? null,
                    'verificada' => true,
                ],
            );

            if (! Peru::rucValido($datos['ruc'])) {
                $this->command?->warn("El RUC de demostracion {$datos['ruc']} no pasa la validacion; revisa OportunidadSeeder.");
            }
        }

        foreach (OportunidadesDemo::todas() as $demo) {
            Oportunidad::updateOrCreate(
                ['slug' => $demo['slug']],
                [
                    'organizacion_id' => $organizaciones[$demo['org']]->id,
                    'titulo' => $demo['title'],
                    'causa' => $demo['cause'],
                    'habilidad' => $demo['skill'],
                    'dedicacion' => $demo['time'],
                    'modalidad' => $demo['modalidad'],
                    'afinidad' => $demo['match'],
                    'imagen' => $demo['image'],
                    'resumen' => $demo['summary'],
                    'aprenderas' => $demo['aprenderas'],
                    'porque' => $demo['porque'],
                    'estado' => 'publicada',
                ],
            );
        }
    }
}
