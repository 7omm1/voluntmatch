<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use App\Models\Oportunidad;
use App\Support\OportunidadesDemo;
use App\Support\Peru;
use Illuminate\Database\Seeder;

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
                    'sector' => $datos['sector'],
                    'mision' => $datos['mision'],
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
