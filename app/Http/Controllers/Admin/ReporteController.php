<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Bandeja de reportes: revisar denuncias sobre cuentas u oportunidades
 * y dejar constancia de la decision tomada.
 */
class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $estado = (string) $request->query('estado');

        $reportes = Reporte::query()
            ->with([
                'user:id,name,email',
                'oportunidad:id,titulo',
                'reportadoPor:id,name',
                'resueltoPor:id,name',
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('motivo', 'like', "%{$q}%")
                        ->orWhere('detalle', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('oportunidad', fn ($o) => $o->where('titulo', 'like', "%{$q}%"));
                });
            })
            ->when(in_array($estado, ['pendiente', 'resuelto', 'descartado'], true), fn ($query) => $query->where('estado', $estado))
            ->orderByRaw("case when estado = 'pendiente' then 0 else 1 end")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.reportes.index', [
            'reportes' => $reportes,
            'q' => $q,
            'estado' => $estado,
            'totales' => [
                'todos' => Reporte::count(),
                'pendientes' => Reporte::pendientes()->count(),
                'resueltos' => Reporte::where('estado', 'resuelto')->count(),
                'descartados' => Reporte::where('estado', 'descartado')->count(),
            ],
        ]);
    }

    /** Un administrador registra una incidencia sobre una cuenta. */
    public function store(Request $request, User $user)
    {
        $datos = $request->validate([
            'motivo' => 'required|string|max:120',
            'detalle' => 'nullable|string|max:2000',
        ]);

        $reporte = Reporte::create([
            'user_id' => $user->id,
            'reportado_por' => auth()->id(),
            'motivo' => $datos['motivo'],
            'detalle' => $datos['detalle'] ?? null,
        ]);

        AuditLog::registrar('reporte.creado', $reporte, $datos['motivo']);

        return back()->with('status', 'Incidencia registrada para revisión.');
    }

    public function resolver(Request $request, Reporte $reporte)
    {
        return $this->cerrar($request, $reporte, 'resuelto');
    }

    public function descartar(Request $request, Reporte $reporte)
    {
        return $this->cerrar($request, $reporte, 'descartado');
    }

    private function cerrar(Request $request, Reporte $reporte, string $estado)
    {
        $datos = $request->validate([
            'nota' => 'nullable|string|max:2000',
        ]);

        $reporte->forceFill([
            'estado' => $estado,
            'resolucion' => $datos['nota'] ?? null,
            'resuelto_por' => auth()->id(),
        ])->save();

        AuditLog::registrar('reporte.'.$estado, $reporte, $datos['nota'] ?? null);

        return back()->with('status', 'Reporte actualizado.');
    }
}
