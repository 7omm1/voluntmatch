<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Bitacora de auditoria: que administrador hizo que cambio y cuando.
 */
class ActividadController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $entidad = (string) $request->query('entidad');

        $actividad = AuditLog::query()
            ->with('user:id,name,email')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('accion', 'like', "%{$q}%")
                        ->orWhere('detalle', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($entidad !== '', fn ($query) => $query->where('entidad', $entidad))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.actividad.index', [
            'actividad' => $actividad,
            'q' => $q,
            'entidad' => $entidad,
            'entidades' => AuditLog::query()->whereNotNull('entidad')->distinct()->orderBy('entidad')->pluck('entidad'),
        ]);
    }
}
