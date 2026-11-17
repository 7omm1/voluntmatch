<?php

namespace App\Support;

/**
 * Datos curados de demostracion para el catalogo de oportunidades,
 * portados del prototipo React y usados por OportunidadSeeder para
 * llenar la tabla real `oportunidades` (y por OportunidadController
 * como respaldo si la base de datos aun esta vacia).
 *
 * Las claves imitan la forma que consumen las vistas:
 * title, org, cause, skill, time, modalidad, match, image, summary,
 * aprenderas y porque.
 */
class OportunidadesDemo
{
    /** @return list<array<string, mixed>> */
    public static function todas(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'rediseno-web-accesible',
                'title' => 'Rediseño web accesible',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'UX/UI',
                'time' => '4 h/semana',
                'modalidad' => 'Remoto',
                'match' => 92,
                'image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&w=900&q=60',
                'summary' => 'Rediseñar el portal de la fundación para que sea usable por personas con discapacidad visual.',
                'aprenderas' => 'Accesibilidad web (WCAG), pruebas con lectores de pantalla y diseño con usuarios reales.',
                'porque' => [
                    'Tu perfil UX/UI coincide con la habilidad que buscan.',
                    '4 h/semana encaja con tu disponibilidad declarada.',
                    'Inclusión está entre las causas que te importan.',
                ],
            ],
            [
                'id' => 2,
                'slug' => 'mentoria-de-matematicas',
                'title' => 'Mentoría de matemáticas',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Docencia',
                'time' => '2 h/semana',
                'modalidad' => 'Híbrido',
                'match' => 88,
                'image' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=900&q=60',
                'summary' => 'Acompañar a escolares de secundaria con refuerzo de matemáticas dos veces por semana.',
                'aprenderas' => 'Pedagogía, planificación de sesiones y comunicación con adolescentes.',
                'porque' => [
                    'Buscaban alguien con paciencia para explicar y tu perfil lo refleja.',
                    'Tu disponibilidad de 6 a 10 h/semana cubre el reto.',
                    'Educación es una de tus causas principales.',
                ],
            ],
            [
                'id' => 3,
                'slug' => 'campana-de-reforestacion',
                'title' => 'Campaña de reforestación',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Comunicación',
                'time' => 'Proyecto puntual',
                'modalidad' => 'Mixto',
                'match' => 84,
                'image' => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=900&q=60',
                'summary' => 'Diseñar y difundir la campaña de reforestación de la cuenca del Cusco.',
                'aprenderas' => 'Comunicación digital con causa, gestión de redes y coordinación con comunidades.',
                'porque' => [
                    'Tu fortaleza en comunicación encaja con el reto.',
                    'Es puntual: puedes aportar sin comprometer tu ciclo académico.',
                    'Medio ambiente está entre tus intereses.',
                ],
            ],
        ];
    }
}
