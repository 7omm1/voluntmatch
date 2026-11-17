<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organizacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cola de verificacion de ONG: revisar la documentacion declarada y
 * aprobar o rechazar la insignia de verificacion.
 */
class OrganizacionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $estado = (string) $request->query('estado');

        $organizaciones = Organizacion::query()
            ->with(['user:id,name,email,region,city,phone', 'verificadaPor:id,name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('ruc', 'like', "%{$q}%")
                        ->orWhere('sector', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%"));
                });
            })
            ->when($estado === 'pendientes', fn ($query) => $query->where('verificada', false))
            ->when($estado === 'verificadas', fn ($query) => $query->where('verificada', true))
            ->orderBy('verificada')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.organizaciones.index', [
            'organizaciones' => $organizaciones,
            'q' => $q,
            'estado' => $estado,
            'totales' => [
                'todas' => Organizacion::count(),
                'pendientes' => Organizacion::pendientes()->count(),
                'verificadas' => Organizacion::where('verificada', true)->count(),
            ],
        ]);
    }

    public function revisar(Request $request, Organizacion $organizacion)
    {
        $datos = $request->validate([
            'accion' => ['required', Rule::in(['verificar', 'rechazar'])],
            'nota' => 'nullable|string|max:255',
        ]);

        $verificar = $datos['accion'] === 'verificar';

        $organizacion->forceFill([
            'verificada' => $verificar,
            'verificada_at' => $verificar ? now() : null,
            'verificada_por' => auth()->id(),
            'verificacion_nota' => $datos['nota'] ?? null,
        ])->save();

        AuditLog::registrar(
            $verificar ? 'organizacion.verificada' : 'organizacion.rechazada',
            $organizacion,
            $datos['nota'] ?? null,
        );

        return back()->with(
            'status',
            $verificar
                ? "{$organizacion->nombre} quedó verificada."
                : "Se rechazó la verificación de {$organizacion->nombre}.",
        );
    }

    public function exportar(): StreamedResponse
    {
        $organizaciones = Organizacion::with('user:id,name,email')->orderBy('nombre')->get();

        return response()->streamDownload(function () use ($organizaciones) {
            $salida = fopen('php://output', 'wb');
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, ['ID', 'Organización', 'RUC', 'Sector', 'Estado', 'Cuenta', 'Correo', 'Nota', 'Registro']);

            foreach ($organizaciones as $organizacion) {
                fputcsv($salida, [
                    $organizacion->id,
                    $organizacion->nombre,
                    $organizacion->ruc,
                    $organizacion->sector,
                    $organizacion->verificada ? 'Verificada' : 'Pendiente',
                    $organizacion->user?->name,
                    $organizacion->user?->email,
                    $organizacion->verificacion_nota,
                    $organizacion->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($salida);
        }, 'voluntmatch-organizaciones-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
