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
            'proyectos' => [
                // TODO: reemplazar por Oportunidad::withCount('postulaciones') cuando
                // las tablas tengan columnas de estado, vistas y entregables.
                [
                    'title' => 'Campaña de educación rural',
                    'meta' => '8 candidatos · Actualizado hoy',
                    'status' => 'activo',
                    'status_label' => 'Activo',
                    'cause' => 'Educación',
                    'skill' => 'Docencia',
                    'time' => 'Proyecto puntual',
                    'modal_id' => 3,
                    'views' => 128,
                    'conversion' => 6.3,
                    'progress' => ['label' => 'Entregables', 'done' => 2, 'total' => 4],
                ],
                [
                    'title' => 'Rediseño web accesible',
                    'meta' => '6 candidatos · Actualizado hoy',
                    'status' => 'reclutando',
                    'status_label' => 'Reclutando',
                    'cause' => 'Inclusión',
                    'skill' => 'UX/UI',
                    'time' => '4 h/semana',
                    'modal_id' => 1,
                    'views' => 96,
                    'conversion' => 6.3,
                    'progress' => null,
                ],
                [
                    'title' => 'Taller de huerta comunitaria',
                    'meta' => '5 candidatos · Actualizado hace 2 días',
                    'status' => 'activo',
                    'status_label' => 'Activo',
                    'cause' => 'Medio ambiente',
                    'skill' => 'Agricultura',
                    'time' => 'Semanal',
                    'modal_id' => 3,
                    'views' => 74,
                    'conversion' => 6.8,
                    'progress' => ['label' => 'Sesiones', 'done' => 3, 'total' => 6],
                ],
                [
                    'title' => 'Mentoría de marketing digital',
                    'meta' => '14 candidatos · Finalizado en marzo',
                    'status' => 'finalizado',
                    'status_label' => 'Finalizado',
                    'cause' => 'Comunicación',
                    'skill' => 'Marketing',
                    'time' => '2 h/semana',
                    'modal_id' => 2,
                    'views' => 210,
                    'conversion' => 6.7,
                    'progress' => ['label' => 'Entregables', 'done' => 4, 'total' => 4],
                ],
                [
                    'title' => 'Análisis de encuestas',
                    'meta' => '0 candidatos · Sin publicar',
                    'status' => 'borrador',
                    'status_label' => 'Borrador',
                    'cause' => 'Educación',
                    'skill' => 'Análisis de datos',
                    'time' => '2 h/semana',
                    'modal_id' => 2,
                    'views' => 0,
                    'conversion' => null,
                    'progress' => null,
                ],
            ],
            'talentos' => [
                // TODO: reemplazar por User::where('role', 'voluntario') con
                // cálculo de afinidad y disponibilidad desde el perfil.
                [
                    'name' => 'Sofía Martín',
                    'career' => 'Diseño',
                    'detail' => 'Diseño · 4º curso',
                    'skills' => ['Figma', 'UI Kit', 'Trabajo en equipo'],
                    'match' => 96,
                    'img' => 47,
                    'hours' => 8,
                ],
                [
                    'name' => 'Álvaro Vega',
                    'career' => 'Informática',
                    'detail' => 'Informática · 3º curso',
                    'skills' => ['React', 'Node.js', 'APIs REST'],
                    'match' => 92,
                    'img' => 12,
                    'hours' => 10,
                ],
                [
                    'name' => 'Nora Campos',
                    'career' => 'Comunicación',
                    'detail' => 'Comunicación · 4º curso',
                    'skills' => ['Estrategia', 'Copywriting'],
                    'match' => 89,
                    'img' => 32,
                    'hours' => 5,
                ],
                [
                    'name' => 'Leo Santos',
                    'career' => 'Estadística',
                    'detail' => 'Estadística · 2º curso',
                    'skills' => ['Python', 'SQL', 'Análisis de datos'],
                    'match' => 87,
                    'img' => 11,
                    'hours' => 4,
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
