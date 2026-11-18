<?php

namespace App\Http\Controllers;

use App\Models\Organizacion;
use App\Support\MapaOngs;
use Illuminate\Http\Request;

/**
 * Directorio publico de ONG: mapa interactivo de Cusco, listado
 * filtrable y ficha publica de cada organizacion.
 */
class DirectorioOngController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->query('q', ''));
        $sector = trim((string) $request->query('sector', ''));

        $organizaciones = Organizacion::query()
            ->verificadas()
            ->withCount(['oportunidadesPublicadas as oportunidades_count'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('mision', 'like', "%{$busqueda}%")
                        ->orWhere('sector', 'like', "%{$busqueda}%")
                        ->orWhere('ciudad', 'like', "%{$busqueda}%");
                });
            })
            ->when($sector !== '', fn ($query) => $query->where('sector', $sector))
            ->orderByDesc('verificada')
            ->orderBy('nombre')
            ->get();

        $sectores = Organizacion::query()
            ->verificadas()
            ->whereNotNull('sector')
            ->distinct()
            ->orderBy('sector')
            ->pluck('sector');

        return view('ongs.index', [
            'organizaciones' => $organizaciones,
            'marcadores' => MapaOngs::marcadores($organizaciones),
            'sectores' => $sectores,
            'busqueda' => $busqueda,
            'sectorActivo' => $sector,
            'cusco' => MapaOngs::CUSCO,
        ]);
    }

    public function show(Request $request, Organizacion $organizacion)
    {
        $user = $request->user();

        $esDueno = $user !== null && $organizacion->user_id === $user->id;
        $esAdmin = $user !== null && $user->role === 'admin';

        abort_unless($organizacion->verificada || $esDueno || $esAdmin, 404);

        $organizacion->load([
            'user:id,name,email,phone,website,address,city,region',
            'oportunidadesPublicadas' => fn ($query) => $query->orderByDesc('destacada')->latest(),
        ]);

        $organizacion->setAttribute('oportunidades_count', $organizacion->oportunidadesPublicadas->count());

        $relacionadas = Organizacion::query()
            ->verificadas()
            ->whereKeyNot($organizacion->getKey())
            ->where('sector', $organizacion->sector)
            ->orderBy('nombre')
            ->limit(3)
            ->get();

        return view('ongs.show', [
            'ong' => $organizacion,
            'oportunidades' => $organizacion->oportunidadesPublicadas,
            'relacionadas' => $relacionadas,
            'marcadores' => MapaOngs::marcadores([$organizacion]),
            'cusco' => MapaOngs::CUSCO,
        ]);
    }
}
