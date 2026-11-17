<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use App\Models\Oportunidad;
use App\Support\OportunidadesDemo;
use App\Support\Peru;
use Illuminate\Database\Seeder;

/**
 * Siembra el catalogo de oportunidades de demostracion en la tabla
 * real `oportunidades` (y las organizaciones que las publican en
 * `organizaciones`). Es idempotente: puede correrse varias veces.
 */
class OportunidadSeeder extends Seeder
{
    public function run(): void
    {
        $rucs = [
            'Fundación Horizonte' => '20100047218',
            'Aula Abierta' => '20533284362',
            'Raíz Colectiva' => '20123456786',
        ];

        foreach (OportunidadesDemo::todas() as $demo) {
            $organizacion = Organizacion::updateOrCreate(
                ['ruc' => $rucs[$demo['org']]],
                [
                    'nombre' => $demo['org'],
                    'sector' => $demo['cause'],
                    'mision' => $demo['summary'],
                    'verificada' => true,
                ],
            );

            Oportunidad::updateOrCreate(
                ['titulo' => $demo['title']],
                [
                    'organizacion_id' => $organizacion->id,
                    'causa' => $demo['cause'],
                    'habilidad' => $demo['skill'],
                    'dedicacion' => $demo['time'],
                    'modalidad' => $demo['modalidad'],
                    'afinidad' => $demo['match'],
                    'imagen' => $demo['image'],
                    'resumen' => $demo['summary'],
                    'aprenderas' => $demo['aprenderas'],
                    'porque' => $demo['porque'],
                ],
            );
        }

        // Sanidad: los RUC sembrados pasan el digito verificador.
        foreach ($rucs as $ruc) {
            if (! Peru::rucValido($ruc)) {
                $this->command?->warn("El RUC de demostracion {$ruc} no pasa la validacion; revisa OportunidadSeeder.");
            }
        }
    }
}
