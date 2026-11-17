<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Oportunidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Curaduria del catalogo: publicar, pausar, archivar, destacar o
 * retirar oportunidades, con el conteo real de postulaciones.
 */
class OportunidadController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $estado = (string) $request->query('estado');

        $oportunidades = Oportunidad::query()
            ->with('organizacion:id,nombre,verificada')
            ->withCount('postulaciones')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('titulo', 'like', "%{$q}%")
                        ->orWhere('habilidad', 'like', "%{$q}%")
                        ->orWhere('causa', 'like', "%{$q}%")
                        ->orWhereHas('organizacion', fn ($o) => $o->where('nombre', 'like', "%{$q}%"));
                });
            })
            ->when(in_array($estado, ['borrador', 'publicada', 'archivada'], true), fn ($query) => $query->where('estado', $estado))
            ->when($estado === 'destacadas', fn ($query) => $query->where('destacada', true))
            ->orderByDesc('destacada')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.oportunidades.index', [
            'oportunidades' => $oportunidades,
            'q' => $q,
            'estado' => $estado,
            'totales' => [
                'todas' => Oportunidad::count(),
                'publicadas' => Oportunidad::where('estado', 'publicada')->count(),
                'borradores' => Oportunidad::where('estado', 'borrador')->count(),
                'destacadas' => Oportunidad::where('destacada', true)->count(),
            ],
        ]);
    }

    public function cambiarEstado(Request $request, Oportunidad $oportunidad)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['borrador', 'publicada', 'archivada'])],
        ]);

        $oportunidad->forceFill(['estado' => $datos['estado']])->save();

        AuditLog::registrar('oportunidad.estado', $oportunidad, $datos['estado']);

        return back()->with('status', "«{$oportunidad->titulo}» quedó en estado {$datos['estado']}.");
    }

    public function destacar(Oportunidad $oportunidad)
    {
        $oportunidad->forceFill(['destacada' => ! $oportunidad->destacada])->save();

        AuditLog::registrar(
            $oportunidad->destacada ? 'oportunidad.destacada' : 'oportunidad.no_destacada',
            $oportunidad,
        );

        return back()->with(
            'status',
            $oportunidad->destacada
                ? "«{$oportunidad->titulo}» ahora aparece destacada."
                : "«{$oportunidad->titulo}» dejó de estar destacada.",
        );
    }

    public function destroy(Oportunidad $oportunidad)
    {
        $titulo = $oportunidad->titulo;

        AuditLog::registrar('oportunidad.eliminada', $oportunidad, $titulo);
        $oportunidad->delete();

        return back()->with('status', "Se eliminó la oportunidad «{$titulo}» y sus postulaciones.");
    }

    public function exportar(): StreamedResponse
    {
        $oportunidades = Oportunidad::with('organizacion:id,nombre')
            ->withCount('postulaciones')
            ->orderBy('titulo')
            ->get();

        return response()->streamDownload(function () use ($oportunidades) {
            $salida = fopen('php://output', 'wb');
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'ID', 'Título', 'Organización', 'Causa', 'Habilidad', 'Modalidad',
                'Estado', 'Destacada', 'Postulaciones', 'Vistas', 'Registro',
            ]);

            foreach ($oportunidades as $oportunidad) {
                fputcsv($salida, [
                    $oportunidad->id,
                    $oportunidad->titulo,
                    $oportunidad->organizacion?->nombre,
                    $oportunidad->causa,
                    $oportunidad->habilidad,
                    $oportunidad->modalidad,
                    $oportunidad->estado,
                    $oportunidad->destacada ? 'Sí' : 'No',
                    $oportunidad->postulaciones_count,
                    $oportunidad->vistas,
                    $oportunidad->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($salida);
        }, 'voluntmatch-oportunidades-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
