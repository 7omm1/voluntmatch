<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\Reporte;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Vista general del panel: metricas reales, tareas pendientes,
 * crecimiento semanal y ultimos movimientos de la comunidad.
 */
class DashboardController extends Controller
{
    /** Valor semanal aproximado de cada rango de disponibilidad. */
    private const HORAS_POR_RANGO = [
        '1-5' => 3,
        '6-10' => 8,
        '11-15' => 13,
        '16-20' => 18,
        '20+' => 20,
    ];

    public function index()
    {
        $semanas = collect(range(7, 0))->map(function (int $atras) {
            $inicio = now()->subWeeks($atras)->startOfWeek();
            $fin = $inicio->copy()->endOfWeek();

            return [
                'label' => $inicio->translatedFormat('d M'),
                'total' => User::whereBetween('created_at', [$inicio, $fin])->count(),
            ];
        });

        $maxSemana = max(1, $semanas->max('total'));

        $aceptadas = Postulacion::where('estado', 'aceptado')->with('user:id,availability')->get();

        $horas = $aceptadas->sum(
            fn (Postulacion $postulacion) => self::HORAS_POR_RANGO[$postulacion->user?->availability] ?? 0
        );

        $totalPostulaciones = Postulacion::count();

        return view('admin.dashboard', [
            'metricas' => [
                'voluntarios' => User::where('role', 'voluntario')->count(),
                'organizaciones' => Organizacion::count(),
                'oportunidades' => Oportunidad::where('estado', 'publicada')->count(),
                'postulaciones' => $totalPostulaciones,
                'matches' => $aceptadas->count(),
                'horas' => $horas,
                'bloqueados' => User::whereNotNull('blocked_at')->count(),
                'nuevosMes' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'pendientes' => [
                'organizaciones' => Organizacion::pendientes()->count(),
                'reportes' => Reporte::pendientes()->count(),
                'tickets' => Ticket::abiertos()->count(),
            ],
            'tasaAceptacion' => $totalPostulaciones > 0
                ? round($aceptadas->count() / $totalPostulaciones * 100)
                : 0,
            'semanas' => $semanas,
            'maxSemana' => $maxSemana,
            'topOportunidades' => Oportunidad::query()
                ->with('organizacion:id,nombre')
                ->withCount('postulaciones')
                ->orderByDesc('postulaciones_count')
                ->limit(5)
                ->get(),
            'ultimosUsuarios' => User::latest()->limit(5)->get(),
            'actividad' => AuditLog::with('user:id,name')->latest()->limit(8)->get(),
            'estadosPostulacion' => self::conteoPorEstado(),
        ]);
    }

    /**
     * @return Collection<string, int>
     */
    private static function conteoPorEstado(): Collection
    {
        return Postulacion::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');
    }
}
