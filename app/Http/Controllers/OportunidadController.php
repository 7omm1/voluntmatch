<?php

namespace App\Http\Controllers;

use App\Models\Oportunidad;
use App\Support\OportunidadesDemo;
use Illuminate\Http\Request;

class OportunidadController extends Controller
{
    /**
     * Oportunidades para las vistas: primero la tabla real
     * `oportunidades`; si la base de datos esta vacia (instalacion
     * sin sembrar), usa el catalogo de demostracion de respaldo.
     *
     * @return list<array<string, mixed>>
     */
    public static function demo(): array
    {
        $deLaBase = Oportunidad::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Oportunidad $oportunidad) => self::mapear($oportunidad))
            ->all();

        return $deLaBase !== [] ? $deLaBase : OportunidadesDemo::todas();
    }

    /**
     * Convierte una fila de la tabla al formato de array que consumen
     * las vistas (mismas claves que OportunidadesDemo).
     *
     * @return array<string, mixed>
     */
    private static function mapear(Oportunidad $oportunidad): array
    {
        return [
            'id' => $oportunidad->id,
            'slug' => $oportunidad->slug,
            'title' => $oportunidad->titulo,
            'org' => $oportunidad->organizacion?->nombre ?? 'Organización verificada',
            'cause' => $oportunidad->causa,
            'skill' => $oportunidad->habilidad,
            'time' => $oportunidad->dedicacion,
            'modalidad' => $oportunidad->modalidad,
            'match' => $oportunidad->afinidad,
            'image' => $oportunidad->imagen,
            'summary' => $oportunidad->resumen,
            'aprenderas' => $oportunidad->aprenderas,
            'porque' => $oportunidad->porque ?? [],
        ];
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'opportunities');

        if (! in_array($tab, ['opportunities', 'participate', 'organizations'], true)) {
            $tab = 'opportunities';
        }

        return view('oportunidades', [
            'tab' => $tab,
            'oportunidades' => self::demo(),
        ]);
    }

    public function show(Oportunidad $oportunidad)
    {
        if ($oportunidad->exists) {
            $oportunidad->registrarVista();
        }

        return view('detalle-oportunidad', [
            'op' => self::mapear($oportunidad),
        ]);
    }
}
