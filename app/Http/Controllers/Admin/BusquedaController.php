<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Reporte;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Busqueda global del panel: cuentas, organizaciones, oportunidades,
 * reportes y casos de soporte en una sola consulta.
 */
class BusquedaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $hayBusqueda = mb_strlen($q) >= 2;

        return view('admin.busqueda.index', [
            'q' => $q,
            'hayBusqueda' => $hayBusqueda,
            'usuarios' => $hayBusqueda
                ? User::where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('dni', 'like', "%{$q}%")
                    ->orWhere('ruc', 'like', "%{$q}%")
                    ->limit(8)
                    ->get()
                : collect(),
            'organizaciones' => $hayBusqueda
                ? Organizacion::where('nombre', 'like', "%{$q}%")
                    ->orWhere('ruc', 'like', "%{$q}%")
                    ->orWhere('sector', 'like', "%{$q}%")
                    ->limit(8)
                    ->get()
                : collect(),
            'oportunidades' => $hayBusqueda
                ? Oportunidad::where('titulo', 'like', "%{$q}%")
                    ->orWhere('habilidad', 'like', "%{$q}%")
                    ->orWhere('causa', 'like', "%{$q}%")
                    ->limit(8)
                    ->get()
                : collect(),
            'reportes' => $hayBusqueda
                ? Reporte::where('motivo', 'like', "%{$q}%")->orWhere('detalle', 'like', "%{$q}%")->limit(5)->get()
                : collect(),
            'tickets' => $hayBusqueda
                ? Ticket::where('asunto', 'like', "%{$q}%")->orWhere('mensaje', 'like', "%{$q}%")->limit(5)->get()
                : collect(),
        ]);
    }
}
