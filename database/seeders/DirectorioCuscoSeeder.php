<?php

namespace Database\Seeders;

use App\Models\Oportunidad;
use App\Models\Organizacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Organizaciones demo del Cusco para que el directorio y el mapa
 * tengan una red visible desde la primera instalacion. No tienen
 * cuenta de acceso: son fichas publicas de demostracion.
 *
 * Es idempotente: se puede ejecutar varias veces sin duplicar filas.
 */
class DirectorioCuscoSeeder extends Seeder
{
    public function run(): void
    {
        $organizaciones = [];

        foreach ($this->organizaciones() as $datos) {
            $organizaciones[$datos['nombre']] = Organizacion::updateOrCreate(
                ['nombre' => $datos['nombre']],
                $datos + [
                    'slug' => Str::slug($datos['nombre']),
                    'verificada' => true,
                    'verificada_at' => now(),
                ],
            );
        }

        $this->crearOportunidades($organizaciones);
    }

    /**
     * Un par de retos por organizacion para que las fichas publicas
     * muestren que ofrece y que busca cada una.
     *
     * @param  array<string, Organizacion>  $organizaciones
     */
    private function crearOportunidades(array $organizaciones): void
    {
        foreach ($this->oportunidades() as $nombreOng => $oportunidades) {
            $organizacion = $organizaciones[$nombreOng] ?? null;

            if ($organizacion === null) {
                continue;
            }

            foreach ($oportunidades as $oportunidad) {
                Oportunidad::updateOrCreate(
                    ['slug' => Str::slug($oportunidad['titulo'])],
                    $oportunidad + [
                        'organizacion_id' => $organizacion->id,
                        'afinidad' => 82,
                        'imagen' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=60',
                        'aprenderas' => 'Experiencia real en terreno, acompañamiento del equipo y un certificado digital para tu portafolio.',
                        'estado' => 'publicada',
                    ],
                );
            }
        }
    }

    /** @return array<string, list<array<string, string>>> */
    private function oportunidades(): array
    {
        return [
            'Qosqo Verde' => [
                [
                    'titulo' => 'Huerto comunitario en San Jerónimo',
                    'causa' => 'Medio ambiente',
                    'habilidad' => 'Agroecología',
                    'dedicacion' => '4 h/semana',
                    'modalidad' => 'Presencial',
                    'resumen' => 'Acompaña la siembra, el riego y la cosecha del huerto que alimenta a doce familias del barrio.',
                ],
                [
                    'titulo' => 'Mapa de compostaje del barrio',
                    'causa' => 'Medio ambiente',
                    'habilidad' => 'Mapeo de datos',
                    'dedicacion' => 'Proyecto puntual',
                    'modalidad' => 'Híbrido',
                    'resumen' => 'Ayúdanos a registrar los puntos de compostaje y a diseñar un mapa abierto para los vecinos.',
                ],
            ],
            'Manos de Barro' => [
                [
                    'titulo' => 'Taller de cerámica para jóvenes',
                    'causa' => 'Cultura y arte',
                    'habilidad' => 'Cerámica',
                    'dedicacion' => '3 h/semana',
                    'modalidad' => 'Presencial',
                    'resumen' => 'Acompaña un grupo de adolescentes en su primer contacto con el barro y los oficios de San Blas.',
                ],
                [
                    'titulo' => 'Catálogo digital de oficios',
                    'causa' => 'Cultura y arte',
                    'habilidad' => 'Fotografía y diseño',
                    'dedicacion' => 'Proyecto puntual',
                    'modalidad' => 'Remoto',
                    'resumen' => 'Documenta las piezas y los procesos de nuestras artesanas para un catálogo digital de oficios vivos.',
                ],
            ],
            'Warmi Empodera' => [
                [
                    'titulo' => 'Mentoría para emprendedoras',
                    'causa' => 'Derechos humanos',
                    'habilidad' => 'Mentoría',
                    'dedicacion' => '2 h/semana',
                    'modalidad' => 'Híbrido',
                    'resumen' => 'Acompaña durante tres meses a una emprendedora en la planificación y crecimiento de su negocio.',
                ],
                [
                    'titulo' => 'Kit de finanzas para pequeños negocios',
                    'causa' => 'Emprendimiento',
                    'habilidad' => 'Finanzas',
                    'dedicacion' => 'Proyecto puntual',
                    'modalidad' => 'Remoto',
                    'resumen' => 'Diseña una plantilla sencilla de ingresos, costos y precios adaptada a negocios de barrio.',
                ],
            ],
            'Semillas de Chinchero' => [
                [
                    'titulo' => 'Lectura bilingüe en quechua y castellano',
                    'causa' => 'Educación',
                    'habilidad' => 'Docencia',
                    'dedicacion' => '4 h/semana',
                    'modalidad' => 'Presencial',
                    'resumen' => 'Acompaña grupos de lectura en las escuelas rurales de Chinchero durante el año escolar.',
                ],
                [
                    'titulo' => 'Biblioteca viajera de Chinchero',
                    'causa' => 'Educación',
                    'habilidad' => 'Logística',
                    'dedicacion' => 'Proyecto puntual',
                    'modalidad' => 'Híbrido',
                    'resumen' => 'Organiza el catalogo, las rutas y las visitas de la biblioteca que recorre cuatro comunidades.',
                ],
            ],
            'Valle Sagrado Salud' => [
                [
                    'titulo' => 'Brigada de salud preventiva',
                    'causa' => 'Salud',
                    'habilidad' => 'Salud comunitaria',
                    'dedicacion' => '6 h/semana',
                    'modalidad' => 'Presencial',
                    'resumen' => 'Súmate a las campañas de control preventivo en las comunidades de Urubamba.',
                ],
                [
                    'titulo' => 'Guía comunitaria de salud materna',
                    'causa' => 'Salud',
                    'habilidad' => 'Comunicación',
                    'dedicacion' => 'Proyecto puntual',
                    'modalidad' => 'Remoto',
                    'resumen' => 'Convierte material técnico en una guía clara y bilingüe para acompañar a madres gestantes.',
                ],
            ],
            'Niños del Sol' => [
                [
                    'titulo' => 'Taller de arte para niños',
                    'causa' => 'Niñez y adolescencia',
                    'habilidad' => 'Arte y educación',
                    'dedicacion' => '3 h/semana',
                    'modalidad' => 'Presencial',
                    'resumen' => 'Comparte tu energía en un taller de pintura, música y juego con niños de Santiago.',
                ],
                [
                    'titulo' => 'Refuerzo escolar en Santiago',
                    'causa' => 'Niñez y adolescencia',
                    'habilidad' => 'Docencia',
                    'dedicacion' => '4 h/semana',
                    'modalidad' => 'Presencial',
                    'resumen' => 'Acompaña a niños y niñas de primaria en sus tareas y en el hábito de la lectura.',
                ],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function organizaciones(): array
    {
        return [
            [
                'nombre' => 'Qosqo Verde',
                'sector' => 'Medio ambiente',
                'ciudad' => 'Cusco · San Jerónimo',
                'mision' => 'Convertimos espacios urbanos abandonados en huertos y corredores verdes cuidados por vecinos.',
                'descripcion' => 'Qosqo Verde nació en 2016 en San Jerónimo con una pregunta sencilla: ¿cuántos terrenos vacíos podrían alimentar a un barrio? Hoy gestionamos siete huertos comunitarios, una red de compostaje y talleres de agricultura urbana para más de 300 familias cusqueñas.',
                'como_participar' => 'Cada huerto tiene una asamblea mensual abierta. Puedes sumarte a las jornadas de siembra y cosecha, al equipo de compostaje o a las visitas escolares. No necesitas experiencia: aprendemos juntos en el mismo suelo.',
                'direccion' => 'Av. Grau 412, San Jerónimo, Cusco',
                'latitud' => -13.5417,
                'longitud' => -71.8847,
                'telefono' => '+51 984 112 908',
                'sitio_web' => null,
                'portada_url' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=1600&q=80',
                'anio_fundacion' => 2016,
            ],
            [
                'nombre' => 'Manos de Barro',
                'sector' => 'Cultura y arte',
                'ciudad' => 'Cusco · San Blas',
                'mision' => 'Preservamos los oficios tradicionales cusqueños abriendo talleres gratuitos para jóvenes.',
                'descripcion' => 'Somos un taller escuela en San Blas donde ceramistas, tejedoras y talladores comparten su oficio con nuevas generaciones. Desde 2011 hemos acompañado a más de 180 jóvenes, muchos de ellos hoy con sus propios emprendimientos culturales.',
                'como_participar' => 'Los talleres funcionan por temporadas de tres meses. Puedes acompañar como aprendiz, apoyar en la producción o sumarte al equipo que organiza ferias y exposiciones en el centro histórico.',
                'direccion' => 'Calle Tandapata 318, San Blas, Cusco',
                'latitud' => -13.5144,
                'longitud' => -71.9722,
                'telefono' => '+51 984 764 210',
                'sitio_web' => null,
                'portada_url' => 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b?auto=format&fit=crop&w=1600&q=80',
                'anio_fundacion' => 2011,
            ],
            [
                'nombre' => 'Warmi Empodera',
                'sector' => 'Derechos humanos',
                'ciudad' => 'Cusco · Wanchaq',
                'mision' => 'Acompañamos a mujeres emprendedoras con formación, mentoría y una red de cuidados.',
                'descripcion' => 'Warmi Empodera trabaja desde 2018 con mujeres que lideran pequeños negocios en Cusco. Ofrecemos formación en finanzas, marketing digital y liderazgo, además de una red de cuidado infantil que les permite participar de las capacitaciones.',
                'como_participar' => 'Puedes sumarte como mentora de un emprendimiento, apoyar en el cuidado de niños durante los talleres o colaborar con el equipo de comunicación. Cada dupla mentora-emprendedora se encuentra dos veces al mes.',
                'direccion' => 'Av. de la Cultura 1120, Wanchaq, Cusco',
                'latitud' => -13.5294,
                'longitud' => -71.9556,
                'telefono' => '+51 986 550 431',
                'sitio_web' => null,
                'portada_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=1600&q=80',
                'anio_fundacion' => 2018,
            ],
            [
                'nombre' => 'Semillas de Chinchero',
                'sector' => 'Educación',
                'ciudad' => 'Cusco · Chinchero',
                'mision' => 'Reforzamos la lectura y la identidad cultural de escolares rurales en Chinchero.',
                'descripcion' => 'Semillas de Chinchero es una red de docentes y comuneros que desde 2013 acompaña a cuatro escuelas rurales. Trabajamos lectura en quechua y castellano, huertos escolares y encuentros de saberes con abuelas y abuelos de la comunidad.',
                'como_participar' => 'Cada voluntario acompaña un grupo de lectura durante el año escolar. Las visitas son los miércoles por la tarde y los sábados hay encuentros comunitarios. El transporte desde Cusco está cubierto por la organización.',
                'direccion' => 'Plaza principal de Chinchero, Cusco',
                'latitud' => -13.3899,
                'longitud' => -72.0481,
                'telefono' => '+51 984 220 776',
                'sitio_web' => null,
                'portada_url' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1600&q=80',
                'anio_fundacion' => 2013,
            ],
            [
                'nombre' => 'Valle Sagrado Salud',
                'sector' => 'Salud',
                'ciudad' => 'Cusco · Urubamba',
                'mision' => 'Acercamos atención preventiva y salud comunitaria a familias del Valle Sagrado.',
                'descripcion' => 'Desde 2009 recorremos comunidades de Urubamba con campañas de salud preventiva, control de niños y adultos mayores, y formación de agentes comunitarios. Trabajamos junto al puesto de salud local y respetamos la medicina tradicional de cada comunidad.',
                'como_participar' => 'Las brigadas salen dos sábados al mes. Estudiantes de salud pueden apoyar en triaje y educación; el resto de perfiles puede sumarse a logística, registro de pacientes o comunicación comunitaria.',
                'direccion' => 'Calle Convención 245, Urubamba, Cusco',
                'latitud' => -13.3051,
                'longitud' => -72.1154,
                'telefono' => '+51 984 908 317',
                'sitio_web' => null,
                'portada_url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1600&q=80',
                'anio_fundacion' => 2009,
            ],
            [
                'nombre' => 'Niños del Sol',
                'sector' => 'Niñez y adolescencia',
                'ciudad' => 'Cusco · Santiago',
                'mision' => 'Creamos espacios seguros de juego, arte y acompañamiento para niños y adolescentes.',
                'descripcion' => 'Niños del Sol abrió sus puertas en 2005 en el barrio de Santiago. Cada tarde recibimos a niños y adolescentes para talleres de arte, refuerzo escolar y acompañamiento emocional, con un equipo de psicólogas y educadores que conoce a cada familia.',
                'como_participar' => 'Puedes acompañar un taller de arte, apoyar el refuerzo escolar o sumarte a las salidas recreativas de fin de semana. Pedimos un compromiso mínimo de un semestre y una entrevista previa con el equipo.',
                'direccion' => 'Av. Tomás Tuyro Túpac 158, Santiago, Cusco',
                'latitud' => -13.5360,
                'longitud' => -71.9850,
                'telefono' => '+51 984 447 602',
                'sitio_web' => null,
                'portada_url' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1600&q=80',
                'anio_fundacion' => 2005,
            ],
        ];
    }
}
