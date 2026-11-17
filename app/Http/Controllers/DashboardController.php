<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'organizacion', 403);

        return view('dashboard/organizacion', [
            'oportunidades' => OportunidadController::demo(),
            // TODO: reemplazar por consultas reales cuando existan
            // las tablas de entregables, entrevistas y calendario.
            'pendientes' => [
                [
                    'icon' => 'check',
                    'title' => 'Tienes 3 entregables por aprobar',
                    'detail' => 'Rediseño web accesible · Entregas de esta semana',
                    'action' => 'Revisar ahora',
                    'goto' => 'projects',
                ],
                [
                    'icon' => 'users',
                    'title' => '2 entrevistas programadas hoy',
                    'detail' => 'Sofía Martín 11:00 · Álvaro Vega 16:00',
                    'action' => 'Ver mensajes',
                    'goto' => 'messages',
                ],
                [
                    'icon' => 'heart',
                    'title' => '1 oportunidad sigue en borrador',
                    'detail' => 'Análisis de encuestas · Completa y publícala',
                    'action' => 'Continuar',
                    'goto' => 'projects',
                ],
            ],
            'hitos' => [
                [
                    'day' => 'LUN',
                    'date' => '17',
                    'title' => 'Entrevista con Sofía Martín',
                    'meta' => '11:00 · Videollamada',
                    'type' => 'reunion',
                ],
                [
                    'day' => 'JUE',
                    'date' => '20',
                    'title' => 'Reunión de seguimiento',
                    'meta' => '17:00 · Rediseño web accesible',
                    'type' => 'reunion',
                ],
                [
                    'day' => 'VIE',
                    'date' => '21',
                    'title' => 'Fecha límite de entrega',
                    'meta' => '23:59 · Campaña de educación rural',
                    'type' => 'entrega',
                ],
            ],
        ]);
    }

    public function admin()
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        return view('dashboard/admin', [
            'oportunidades' => OportunidadController::demo(),
        ]);
    }
}
