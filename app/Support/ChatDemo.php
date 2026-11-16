<?php

namespace App\Support;

/**
 * Conversaciones de ejemplo del panel del estudiante.
 * TODO: reemplazar por mensajes reales cuando exista la tabla de chats.
 */
class ChatDemo
{
    public static function conversaciones(): array
    {
        return [
            [
                'id' => 'aula-abierta',
                'initials' => 'AA',
                'name' => 'Aula Abierta',
                'project' => 'Mentoría de matemáticas',
                'projectId' => 2,
                'status' => 'En línea · Proyecto Mentoría',
                'unread' => 2,
                'messages' => [
                    ['from' => 'them', 'text' => '¡Hola! Tu perfil encaja muy bien con el proyecto.', 'time' => '16:20'],
                    ['from' => 'me', 'text' => 'Muchas gracias. ¿Cuándo podríamos hacer la primera reunión?', 'time' => '16:21'],
                    ['from' => 'them', 'text' => '¿Te viene bien el jueves a las 17:00?', 'time' => '16:22'],
                ],
                'replies' => [
                    'Perfecto, te comparto el enlace de la reunión por aquí.',
                    'Gracias por responder tan rápido, ya avisé al equipo.',
                    '¿Necesitas algún material antes de empezar?',
                ],
                'context' => [
                    ['label' => 'Próxima reunión', 'value' => 'Jueves · 17:00'],
                    ['label' => 'Entregables', 'value' => '2 de 4 listos'],
                ],
            ],
            [
                'id' => 'fundacion-horizonte',
                'initials' => 'FH',
                'name' => 'Fundación Horizonte',
                'project' => 'Rediseño web accesible',
                'projectId' => 1,
                'status' => 'En línea · Proyecto Rediseño web',
                'unread' => 0,
                'messages' => [
                    ['from' => 'them', 'text' => 'Vimos tu Skills Passport y nos encantó tu perfil UX.', 'time' => 'AYER 11:04'],
                    ['from' => 'me', 'text' => '¡Gracias! Me interesa mucho la accesibilidad.', 'time' => 'AYER 11:10'],
                    ['from' => 'them', 'text' => 'Perfecto, muchas gracias.', 'time' => 'AYER 11:12'],
                ],
                'replies' => [
                    'Te envío el brief del rediseño para que lo revises.',
                    '¿Podemos agendar una llamada de 20 minutos?',
                    'Ya subimos los wireframes a la carpeta compartida.',
                ],
                'context' => [
                    ['label' => 'Próxima reunión', 'value' => 'Viernes · 10:00'],
                    ['label' => 'Entregables', 'value' => '1 de 5 listos'],
                ],
            ],
            [
                'id' => 'raiz-colectiva',
                'initials' => 'RC',
                'name' => 'Raíz Colectiva',
                'project' => 'Campaña de reforestación',
                'projectId' => 3,
                'status' => 'Última conexión hace 2 h',
                'unread' => 0,
                'messages' => [
                    ['from' => 'them', 'text' => 'Tienes un archivo nuevo en la carpeta de la campaña.', 'time' => 'LUN 09:15'],
                    ['from' => 'me', 'text' => 'Lo reviso hoy y te comento.', 'time' => 'LUN 09:40'],
                ],
                'replies' => [
                    'Gracias, avísame si algo no se entiende.',
                    'El sábado tenemos jornada en campo, ¿te sumas?',
                    'Te comparto las fotos de la última actividad.',
                ],
                'context' => [
                    ['label' => 'Próxima reunión', 'value' => 'Sábado · 08:00'],
                    ['label' => 'Entregables', 'value' => '3 de 6 listos'],
                ],
            ],
        ];
    }

    public static function noLeidos(): int
    {
        return array_sum(array_column(self::conversaciones(), 'unread'));
    }
}