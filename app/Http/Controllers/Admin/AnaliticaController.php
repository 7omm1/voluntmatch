<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Analiticas del ecosistema calculadas sobre datos reales: crecimiento,
 * demanda de habilidades, causas, regiones y conversion de postulaciones.
 */
class AnaliticaController extends Controller
{
    public function index()
    {
        $totalPostulaciones = Postulacion::count();
        $aceptadas = Postulacion::where('estado', 'aceptado')->count();

        return view('admin.analiticas.index', [
            'usuariosPorRol' => User::query()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
            'postulacionesPorEstado' => Postulacion::query()
                ->selectRaw('estado, count(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'porCausa' => Oportunidad::query()
                ->selectRaw('causa, count(*) as total')
                ->groupBy('causa')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),
            'habilidades' => Oportunidad::query()
                ->selectRaw('habilidad, count(*) as total')
                ->groupBy('habilidad')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),
            'regiones' => User::query()
                ->where('role', 'voluntario')
                ->whereNotNull('region')
                ->selectRaw('region, count(*) as total')
                ->groupBy('region')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),
            'meses' => $this->crecimientoMensual(),
            'organizaciones' => [
                'verificadas' => Organizacion::where('verificada', true)->count(),
                'pendientes' => Organizacion::pendientes()->count(),
                'total' => Organizacion::count(),
            ],
            'conversion' => [
                'postulaciones' => $totalPostulaciones,
                'aceptadas' => $aceptadas,
                'tasa' => $totalPostulaciones > 0 ? round($aceptadas / $totalPostulaciones * 100) : 0,
            ],
            'topOportunidades' => Oportunidad::withCount('postulaciones')
                ->orderByDesc('postulaciones_count')
                ->orderByDesc('vistas')
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * Altas por mes de los ultimos seis meses.
     *
     * @return Collection<int, array{label: string, total: int}>
     */
    private function crecimientoMensual(): Collection
    {
        $desde = now()->startOfMonth()->subMonths(5);

        $altas = User::where('created_at', '>=', $desde)
            ->pluck('created_at')
            ->countBy(fn ($fecha) => $fecha->format('Y-m'));

        return collect(range(0, 5))->map(function (int $mes) use ($desde, $altas) {
            $fecha = $desde->copy()->addMonths($mes);

            return [
                'label' => ucfirst($fecha->translatedFormat('M Y')),
                'total' => (int) ($altas[$fecha->format('Y-m')] ?? 0),
            ];
        });
    }
}
