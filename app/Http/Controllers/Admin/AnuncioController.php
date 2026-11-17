<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Comunicados internos: la administracion publica avisos para todos,
 * solo voluntarios o solo organizaciones.
 */
class AnuncioController extends Controller
{
    public function index()
    {
        return view('admin.anuncios.index', [
            'anuncios' => Anuncio::latest()->paginate(10),
            'totales' => [
                'todos' => Anuncio::count(),
                'activos' => Anuncio::where('activo', true)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'titulo' => 'required|string|max:160',
            'cuerpo' => 'required|string|max:3000',
            'audiencia' => ['required', Rule::in(Anuncio::AUDIENCIAS)],
        ]);

        $anuncio = Anuncio::create($datos + [
            'activo' => true,
            'created_by' => auth()->id(),
        ]);

        AuditLog::registrar('anuncio.creado', $anuncio, $anuncio->titulo);

        return back()->with('status', 'Anuncio publicado.');
    }

    public function toggle(Anuncio $anuncio)
    {
        $anuncio->forceFill(['activo' => ! $anuncio->activo])->save();

        AuditLog::registrar(
            $anuncio->activo ? 'anuncio.activado' : 'anuncio.desactivado',
            $anuncio,
        );

        return back()->with('status', $anuncio->activo ? 'Anuncio activado.' : 'Anuncio desactivado.');
    }

    public function destroy(Anuncio $anuncio)
    {
        AuditLog::registrar('anuncio.eliminado', $anuncio, $anuncio->titulo);
        $anuncio->delete();

        return back()->with('status', 'Anuncio eliminado.');
    }
}
