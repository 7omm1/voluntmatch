<?php

namespace App\Http\Controllers;

use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Support\MapaOngs;

class HomeController extends Controller
{
    public function index()
    {
        $ongsMapa = Organizacion::query()
            ->verificadas()
            ->conUbicacion()
            ->where('ciudad', 'like', 'Cusco%')
            ->withCount(['oportunidadesPublicadas as oportunidades_count'])
            ->orderBy('nombre')
            ->get();

        return view('index', [
            'ongsMapa' => $ongsMapa,
            'marcadores' => MapaOngs::marcadores($ongsMapa),
            'totalOngs' => Organizacion::verificadas()->count(),
            'totalOportunidades' => Oportunidad::publicadas()->count(),
            'cusco' => MapaOngs::CUSCO,
        ]);
    }
}
