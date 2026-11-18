<?php

namespace App\Support;

/**
 * Datos curados de demostracion para el catalogo de oportunidades,
 * usados por OportunidadSeeder para llenar la tabla real
 * `oportunidades` (y por OportunidadController como respaldo si la
 * base de datos aun esta vacia).
 *
 * El catalogo tiene tres ONG y diez retos distintos por ONG, de modo
 * que cada organizacion demuestra el flujo completo de su panel.
 *
 * Las claves imitan la forma que consumen las vistas:
 * title, org, cause, skill, time, modalidad, match, image, summary,
 * aprenderas y porque.
 */
class OportunidadesDemo
{
    /**
     * Las tres organizaciones de demostracion. Cada una recibe su
     * cuenta de acceso en ComunidadDemoSeeder.
     *
     * @return list<array<string, string>>
     */
    public static function organizaciones(): array
    {
        return [
            [
                'nombre' => 'Fundación Horizonte',
                'email' => 'horizonte@voluntmatch.test',
                'ruc' => '20100047218',
                'sector' => 'Inclusión y discapacidad',
                'mision' => 'Construimos una ciudad accesible donde las personas con discapacidad participan sin barreras.',
                'region' => 'Lima',
                'ciudad' => 'Lima',
            ],
            [
                'nombre' => 'Aula Abierta',
                'email' => 'aula@voluntmatch.test',
                'ruc' => '20533284362',
                'sector' => 'Educación',
                'mision' => 'Acompañamos a escolares de zonas vulnerables con refuerzo, mentoría y materiales educativos.',
                'region' => 'Lima',
                'ciudad' => 'Lima',
            ],
            [
                'nombre' => 'Raíz Colectiva',
                'email' => 'raiz@voluntmatch.test',
                'ruc' => '20123456786',
                'sector' => 'Medio ambiente',
                'mision' => 'Recuperamos áreas verdes, cuidamos cuencas y formamos brigadas ambientales comunitarias.',
                'region' => 'Cusco',
                'ciudad' => 'Cusco',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function todas(): array
    {
        $imagenes = [
            'https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=900&q=60',
            'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=900&q=60',
        ];

        $catalogo = array_merge(
            self::horizonte(),
            self::aulaAbierta(),
            self::raizColectiva(),
        );

        return array_map(function (array $item, int $indice) use ($imagenes) {
            $item['id'] = $indice + 1;
            $item['image'] = $imagenes[$indice % count($imagenes)];
            $item['aprenderas'] ??= 'Experiencia real, acompañamiento del equipo y certificado digital para tu portafolio.';
            $item['porque'] ??= [
                "Tu talento en {$item['skill']} es justo lo que busca {$item['org']}.",
                "Dedicar {$item['time']} es compatible con tu ciclo académico.",
                "{$item['cause']} está entre las causas que te importan.",
            ];

            return $item;
        }, $catalogo, array_keys($catalogo));
    }

    /** @return list<array<string, mixed>> */
    private static function horizonte(): array
    {
        return [
            [
                'slug' => 'rediseno-web-accesible',
                'title' => 'Rediseño web accesible',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'UX/UI',
                'time' => '4 h/semana',
                'modalidad' => 'Remoto',
                'match' => 92,
                'summary' => 'Rediseñar el portal de la fundación para que sea usable por personas con discapacidad visual.',
                'aprenderas' => 'Accesibilidad web (WCAG), pruebas con lectores de pantalla y diseño con usuarios reales.',
                'porque' => [
                    'Tu perfil UX/UI coincide con la habilidad que buscan.',
                    '4 h/semana encaja con tu disponibilidad declarada.',
                    'Inclusión está entre las causas que te importan.',
                ],
            ],
            [
                'slug' => 'interprete-de-lengua-de-senas',
                'title' => 'Intérprete de lengua de señas',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Lengua de señas',
                'time' => '3 h/semana',
                'modalidad' => 'Híbrido',
                'match' => 90,
                'summary' => 'Acompañar talleres y reuniones con interpretación en lengua de señas peruana.',
                'aprenderas' => 'Interpretación comunitaria, vocabulario especializado y trabajo con comunidades sordas.',
            ],
            [
                'slug' => 'audiolibros-para-personas-ciegas',
                'title' => 'Audiolibros para personas ciegas',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Locución',
                'time' => '2 h/semana',
                'modalidad' => 'Remoto',
                'match' => 88,
                'summary' => 'Grabar capítulos de libros educativos para la biblioteca sonora de la fundación.',
                'aprenderas' => 'Locución, edición de audio y producción de contenidos accesibles.',
            ],
            [
                'slug' => 'taller-de-tecnologia-adaptada',
                'title' => 'Taller de tecnología adaptada',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Docencia',
                'time' => '4 h/semana',
                'modalidad' => 'Presencial',
                'match' => 86,
                'summary' => 'Enseñar a usar lectores de pantalla y herramientas adaptadas a personas con discapacidad.',
                'aprenderas' => 'Tecnologías de asistencia, pedagogía inclusiva y acompañamiento personalizado.',
            ],
            [
                'slug' => 'app-de-rutas-accesibles',
                'title' => 'App de rutas accesibles',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Desarrollo móvil',
                'time' => '6 h/semana',
                'modalidad' => 'Remoto',
                'match' => 95,
                'summary' => 'Prototipar una app que avise rutas accesibles para personas en silla de ruedas.',
                'aprenderas' => 'Desarrollo móvil, diseño centrado en usuarios y validación con personas reales.',
            ],
            [
                'slug' => 'senaletica-inclusiva-para-museos',
                'title' => 'Señalética inclusiva para museos',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Diseño gráfico',
                'time' => 'Proyecto puntual',
                'modalidad' => 'Híbrido',
                'match' => 84,
                'summary' => 'Diseñar señalética con pictogramas, braille y alto contraste para un museo local.',
                'aprenderas' => 'Diseño universal, señalética y producción de piezas accesibles.',
            ],
            [
                'slug' => 'acompanamiento-a-familias',
                'title' => 'Acompañamiento a familias',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Empatía',
                'time' => '3 h/semana',
                'modalidad' => 'Presencial',
                'match' => 82,
                'summary' => 'Brindar contención y orientación a familias que reciben un diagnóstico de discapacidad.',
                'aprenderas' => 'Escucha activa, orientación familiar y redes de apoyo comunitario.',
            ],
            [
                'slug' => 'campana-de-empleo-inclusivo',
                'title' => 'Campaña de empleo inclusivo',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Comunicación',
                'time' => '4 h/semana',
                'modalidad' => 'Remoto',
                'match' => 87,
                'summary' => 'Crear una campaña digital para que más empresas contraten talento con discapacidad.',
                'aprenderas' => 'Comunicación con causa, contenidos para redes y relacionamiento con empresas.',
            ],
            [
                'slug' => 'subtitulado-de-videos-educativos',
                'title' => 'Subtitulado de videos educativos',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Edición de video',
                'time' => '2 h/semana',
                'modalidad' => 'Remoto',
                'match' => 89,
                'summary' => 'Agregar subtítulos descriptivos y transcripciones a los videos de la fundación.',
                'aprenderas' => 'Accesibilidad audiovisual, subtitulado descriptivo y control de calidad.',
            ],
            [
                'slug' => 'accesibilidad-de-documentos-internos',
                'title' => 'Accesibilidad de documentos internos',
                'org' => 'Fundación Horizonte',
                'cause' => 'Inclusión',
                'skill' => 'Análisis',
                'time' => '3 h/semana',
                'modalidad' => 'Remoto',
                'match' => 83,
                'summary' => 'Auditar y corregir documentos PDF para que sean legibles con lectores de pantalla.',
                'aprenderas' => 'Auditoría de accesibilidad, estructura de documentos y estándares WCAG.',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function aulaAbierta(): array
    {
        return [
            [
                'slug' => 'mentoria-de-matematicas',
                'title' => 'Mentoría de matemáticas',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Docencia',
                'time' => '2 h/semana',
                'modalidad' => 'Híbrido',
                'match' => 90,
                'summary' => 'Acompañar a escolares de secundaria con refuerzo de matemáticas dos veces por semana.',
                'aprenderas' => 'Pedagogía, planificación de sesiones y comunicación con adolescentes.',
                'porque' => [
                    'Buscaban alguien con paciencia para explicar y tu perfil lo refleja.',
                    'Tu disponibilidad de 6 a 10 h/semana cubre el reto.',
                    'Educación es una de tus causas principales.',
                ],
            ],
            [
                'slug' => 'refuerzo-de-comprension-lectora',
                'title' => 'Refuerzo de comprensión lectora',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Docencia',
                'time' => '3 h/semana',
                'modalidad' => 'Presencial',
                'match' => 88,
                'summary' => 'Guiar círculos de lectura y estrategias de comprensión con estudiantes de primaria.',
                'aprenderas' => 'Animación a la lectura, evaluación formativa y dinámicas grupales.',
            ],
            [
                'slug' => 'club-de-ciencia-para-escolares',
                'title' => 'Club de ciencia para escolares',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Ciencias',
                'time' => '4 h/semana',
                'modalidad' => 'Presencial',
                'match' => 85,
                'summary' => 'Diseñar experimentos sencillos y acompañar el club de ciencia de una escuela rural.',
                'aprenderas' => 'Divulgación científica, experimentos seguros y trabajo con niños.',
            ],
            [
                'slug' => 'diseno-de-material-didactico',
                'title' => 'Diseño de material didáctico',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Diseño gráfico',
                'time' => '3 h/semana',
                'modalidad' => 'Remoto',
                'match' => 87,
                'summary' => 'Crear fichas, juegos y láminas imprimibles para las aulas de la organización.',
                'aprenderas' => 'Diseño editorial, ilustración educativa y producción para imprenta.',
            ],
            [
                'slug' => 'tutoria-virtual-de-ingles',
                'title' => 'Tutoría virtual de inglés',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Idiomas',
                'time' => '2 h/semana',
                'modalidad' => 'Remoto',
                'match' => 91,
                'summary' => 'Practicar conversación en inglés con estudiantes que preparan su primera entrevista.',
                'aprenderas' => 'Enseñanza de idiomas, evaluación oral y recursos digitales.',
            ],
            [
                'slug' => 'orientacion-vocacional',
                'title' => 'Orientación vocacional',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Psicología',
                'time' => '3 h/semana',
                'modalidad' => 'Híbrido',
                'match' => 84,
                'summary' => 'Acompañar a jóvenes de quinto de secundaria a descubrir sus intereses y opciones de estudio.',
                'aprenderas' => 'Entrevistas de orientación, test vocacionales y acompañamiento juvenil.',
            ],
            [
                'slug' => 'biblioteca-comunitaria-digital',
                'title' => 'Biblioteca comunitaria digital',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Bibliotecología',
                'time' => '4 h/semana',
                'modalidad' => 'Remoto',
                'match' => 82,
                'summary' => 'Organizar y catalogar recursos educativos abiertos para la biblioteca virtual.',
                'aprenderas' => 'Catalogación, curaduría de contenidos y gestión de repositorios.',
            ],
            [
                'slug' => 'olimpiadas-escolares-de-robotica',
                'title' => 'Olimpiadas escolares de robótica',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Robótica',
                'time' => 'Proyecto puntual',
                'modalidad' => 'Presencial',
                'match' => 89,
                'summary' => 'Mentorizar equipos escolares que participarán en la olimpiada regional de robótica.',
                'aprenderas' => 'Robótica educativa, trabajo en equipo y gestión de competencias.',
            ],
            [
                'slug' => 'analisis-de-encuestas-de-aprendizaje',
                'title' => 'Análisis de encuestas de aprendizaje',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Análisis de datos',
                'time' => '5 h/semana',
                'modalidad' => 'Remoto',
                'match' => 86,
                'summary' => 'Ordenar y analizar las encuestas de los talleres para medir el avance de los estudiantes.',
                'aprenderas' => 'Limpieza de datos, visualización y reportes para la toma de decisiones.',
            ],
            [
                'slug' => 'escuela-de-padres-y-madres',
                'title' => 'Escuela de padres y madres',
                'org' => 'Aula Abierta',
                'cause' => 'Educación',
                'skill' => 'Comunicación',
                'time' => '2 h/semana',
                'modalidad' => 'Híbrido',
                'match' => 81,
                'summary' => 'Facilitar encuentros sobre acompañamiento escolar y hábitos de estudio en familia.',
                'aprenderas' => 'Facilitación de grupos, comunicación con familias y diseño de talleres.',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function raizColectiva(): array
    {
        return [
            [
                'slug' => 'campana-de-reforestacion',
                'title' => 'Campaña de reforestación',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Comunicación',
                'time' => 'Proyecto puntual',
                'modalidad' => 'Mixto',
                'match' => 86,
                'summary' => 'Diseñar y difundir la campaña de reforestación de la cuenca del Cusco.',
                'aprenderas' => 'Comunicación digital con causa, gestión de redes y coordinación con comunidades.',
                'porque' => [
                    'Tu fortaleza en comunicación encaja con el reto.',
                    'Es puntual: puedes aportar sin comprometer tu ciclo académico.',
                    'Medio ambiente está entre tus intereses.',
                ],
            ],
            [
                'slug' => 'huerto-urbano-comunitario',
                'title' => 'Huerto urbano comunitario',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Agricultura',
                'time' => 'Semanal',
                'modalidad' => 'Presencial',
                'match' => 81,
                'summary' => 'Guiar talleres de huerto urbano con familias del barrio y cuidar el espacio comunitario.',
                'aprenderas' => 'Agricultura urbana, compostaje y facilitación de talleres.',
            ],
            [
                'slug' => 'mapa-de-reciclaje-del-barrio',
                'title' => 'Mapa de reciclaje del barrio',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Análisis de datos',
                'time' => '4 h/semana',
                'modalidad' => 'Remoto',
                'match' => 87,
                'summary' => 'Mapear los puntos de reciclaje y construir un visor abierto para vecinos y colegios.',
                'aprenderas' => 'Sistemas de información geográfica, datos abiertos y visualización.',
            ],
            [
                'slug' => 'taller-de-compostaje',
                'title' => 'Taller de compostaje',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Docencia',
                'time' => '2 h/semana',
                'modalidad' => 'Presencial',
                'match' => 83,
                'summary' => 'Enseñar a convertir residuos orgánicos en compost en casa y en la escuela.',
                'aprenderas' => 'Manejo de residuos, educación ambiental y dinámicas participativas.',
            ],
            [
                'slug' => 'limpieza-de-rios',
                'title' => 'Limpieza de ríos',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Trabajo de campo',
                'time' => 'Proyecto puntual',
                'modalidad' => 'Presencial',
                'match' => 82,
                'summary' => 'Organizar jornadas de limpieza y registro de residuos en el río de la comunidad.',
                'aprenderas' => 'Logística de campo, seguridad y sensibilización ambiental.',
            ],
            [
                'slug' => 'campana-de-consumo-responsable',
                'title' => 'Campaña de consumo responsable',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Marketing',
                'time' => '3 h/semana',
                'modalidad' => 'Remoto',
                'match' => 88,
                'summary' => 'Crear contenidos y alianzas para promover hábitos de consumo con menor impacto.',
                'aprenderas' => 'Marketing social, contenidos sostenibles y medición de campañas.',
            ],
            [
                'slug' => 'inventario-de-especies-nativas',
                'title' => 'Inventario de especies nativas',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Biología',
                'time' => '5 h/semana',
                'modalidad' => 'Mixto',
                'match' => 84,
                'summary' => 'Registrar especies nativas y actualizar el inventario del bosque comunitario.',
                'aprenderas' => 'Identificación de especies, muestreo y ciencia ciudadana.',
            ],
            [
                'slug' => 'podcast-ambiental-juvenil',
                'title' => 'Podcast ambiental juvenil',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Locución',
                'time' => '3 h/semana',
                'modalidad' => 'Remoto',
                'match' => 85,
                'summary' => 'Producir episodios breves sobre medio ambiente con voces de jóvenes de la comunidad.',
                'aprenderas' => 'Producción de podcast, guion y entrevistas.',
            ],
            [
                'slug' => 'diseno-de-senaletica-del-bosque',
                'title' => 'Diseño de señalética del bosque',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Diseño gráfico',
                'time' => 'Proyecto puntual',
                'modalidad' => 'Remoto',
                'match' => 80,
                'summary' => 'Diseñar paneles informativos para el sendero educativo del bosque comunitario.',
                'aprenderas' => 'Diseño ambiental, materiales resistentes y comunicación visual.',
            ],
            [
                'slug' => 'educacion-ambiental-en-colegios',
                'title' => 'Educación ambiental en colegios',
                'org' => 'Raíz Colectiva',
                'cause' => 'Medio ambiente',
                'skill' => 'Docencia',
                'time' => '4 h/semana',
                'modalidad' => 'Presencial',
                'match' => 89,
                'summary' => 'Llevar talleres de biodiversidad y cambio climático a colegios de la región.',
                'aprenderas' => 'Educación ambiental, diseño de talleres y trabajo con escolares.',
            ],
        ];
    }
}
