<?php

namespace Database\Seeders;

use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use App\Support\Peru;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Comunidad de demostracion: varias ONG con cuenta propia y estudiantes
 * con perfil completo, para que ambos paneles tengan datos reales.
 *
 * Credenciales: todas las cuentas usan la contrasenia "password".
 * Es idempotente: se puede ejecutar varias veces sin duplicar filas.
 */
class ComunidadDemoSeeder extends Seeder
{
    /** Hash unico reutilizado por todas las cuentas demo. */
    private string $passwordHash;

    public function run(): void
    {
        $this->passwordHash = Hash::make('password');

        $organizaciones = $this->crearOrganizaciones();
        $voluntarios = $this->crearVoluntarios();
        $oportunidades = $this->crearOportunidades($organizaciones);
        $this->crearPostulaciones($voluntarios, $oportunidades);
    }

    /** @return array<int, Organizacion> */
    private function crearOrganizaciones(): array
    {
        $datos = [
            ['Manos que Suman', 'manos@voluntmatch.test', 'Educación', 'Lima', 'Lima', 'Acompañamos a escolares de zonas vulnerables con refuerzo y mentoría.'],
            ['Verde Vivo', 'verde@voluntmatch.test', 'Medio ambiente', 'Cusco', 'Cusco', 'Recuperamos áreas verdes y formamos brigadas ambientales escolares.'],
            ['Código Ciudadano', 'codigo@voluntmatch.test', 'Tecnología', 'Arequipa', 'Arequipa', 'Creamos tecnología cívica abierta para organizaciones sociales.'],
            ['Casa Abierta', 'casa@voluntmatch.test', 'Niñez y adolescencia', 'Piura', 'Piura', 'Abrimos espacios seguros y talleres para niñas, niños y adolescentes.'],
            ['Salud en Red', 'salud@voluntmatch.test', 'Salud', 'La Libertad', 'Trujillo', 'Acercamos información y acompañamiento de salud a comunidades rurales.'],
            ['Cultura Viva', 'cultura@voluntmatch.test', 'Cultura y arte', 'Puno', 'Puno', 'Promovemos el arte, la memoria y las lenguas originarias del sur.'],
        ];

        $organizaciones = [];

        foreach ($datos as $indice => [$nombre, $email, $sector, $region, $ciudad, $mision]) {
            $ruc = $this->rucDemo($indice + 1);

            $usuario = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => $this->passwordHash,
                    'role' => 'organizacion',
                    'phone' => '9'.str_pad((string) (20000000 + $indice), 8, '0', STR_PAD_LEFT),
                    'region' => $region,
                    'city' => $ciudad,
                    'ruc' => $ruc,
                    'org_type' => 'ONG',
                    'legal_rep' => 'Representante de '.$nombre,
                    'sector' => $sector,
                    'mission' => $mision,
                    'website' => 'https://'.str($nombre)->slug().'.org',
                ],
            );

            $organizaciones[] = Organizacion::updateOrCreate(
                ['user_id' => $usuario->id],
                [
                    'nombre' => $nombre,
                    'ruc' => $ruc,
                    'sector' => $sector,
                    'mision' => $mision,
                    'verificada' => true,
                    'verificada_at' => now(),
                ],
            );
        }

        return $organizaciones;
    }

    /** @return array<int, User> */
    private function crearVoluntarios(): array
    {
        $datos = [
            ['Sofía Martín', 'sofia@voluntmatch.test', 'Diseño', 'UPC', '6', '6-10', ['Lima', 'Miraflores'], ['Figma' => 92, 'Creatividad' => 88, 'Comunicación' => 84, 'Liderazgo' => 70, 'Trabajo en equipo' => 90], ['Inclusión y discapacidad', 'Educación']],
            ['Álvaro Vega', 'alvaro@voluntmatch.test', 'Informática', 'Universidad Nacional de Ingeniería', '8', '11-15', ['Lima', 'San Miguel'], ['React' => 94, 'Node.js' => 88, 'Liderazgo' => 76, 'Análisis' => 85, 'Trabajo en equipo' => 80], ['Tecnología', 'Educación']],
            ['Nora Campos', 'nora@voluntmatch.test', 'Comunicación', 'Universidad de Lima', '5', '6-10', ['Lima', 'Barranco'], ['Estrategia' => 90, 'Copywriting' => 86, 'Creatividad' => 84, 'Comunicación' => 93, 'Trabajo en equipo' => 78], ['Medio ambiente', 'Cultura y arte']],
            ['Leo Santos', 'leo@voluntmatch.test', 'Estadística', 'Universidad Nacional Mayor de San Marcos', '4', '6-10', ['Lima', 'Lima'], ['Python' => 91, 'SQL' => 87, 'Análisis' => 94, 'Trabajo en equipo' => 74, 'Comunicación' => 70], ['Educación', 'Tecnología']],
            ['Camila Rojas', 'camila@voluntmatch.test', 'Psicología', 'PUCP', '7', '6-10', ['Lima', 'Jesús María'], ['Empatía' => 93, 'Comunicación' => 89, 'Liderazgo' => 72, 'Trabajo en equipo' => 88, 'Análisis' => 68], ['Salud', 'Niñez y adolescencia']],
            ['Diego Paredes', 'diego@voluntmatch.test', 'Ingeniería Ambiental', 'Universidad Nacional Agraria La Molina', '6', '6-10', ['Lima', 'La Molina'], ['Sostenibilidad' => 90, 'Análisis' => 82, 'Trabajo en equipo' => 86, 'Liderazgo' => 74, 'Comunicación' => 71], ['Medio ambiente', 'Animales']],
            ['Valeria Quispe', 'valeria@voluntmatch.test', 'Marketing', 'Universidad del Pacífico', '5', '6-10', ['Lima', 'Surco'], ['Marketing' => 91, 'Creatividad' => 86, 'Comunicación' => 90, 'Estrategia' => 84, 'Liderazgo' => 73], ['Emprendimiento', 'Educación']],
            ['Renzo Ibáñez', 'renzo@voluntmatch.test', 'Ingeniería de Sistemas', 'Universidad Nacional de San Agustín', '7', '11-15', ['Arequipa', 'Arequipa'], ['Java' => 88, 'APIs REST' => 84, 'SQL' => 80, 'Análisis' => 82, 'Trabajo en equipo' => 76], ['Tecnología', 'Derechos humanos']],
            ['Lucía Herrera', 'lucia@voluntmatch.test', 'Educación', 'Universidad Nacional de Trujillo', '8', '6-10', ['La Libertad', 'Trujillo'], ['Docencia' => 94, 'Comunicación' => 88, 'Empatía' => 90, 'Liderazgo' => 80, 'Creatividad' => 75], ['Educación', 'Niñez y adolescencia']],
            ['Marco Silva', 'marco@voluntmatch.test', 'Arquitectura', 'Universidad Católica de Santa María', '5', '6-10', ['Arequipa', 'Arequipa'], ['Diseño' => 87, 'Creatividad' => 85, 'Análisis' => 76, 'Trabajo en equipo' => 82, 'Comunicación' => 74], ['Cultura y arte', 'Medio ambiente']],
            ['Ana Lucero', 'ana@voluntmatch.test', 'Medicina', 'Universidad Nacional de Piura', '9', '1-5', ['Piura', 'Piura'], ['Salud' => 92, 'Empatía' => 91, 'Comunicación' => 84, 'Análisis' => 78, 'Trabajo en equipo' => 83], ['Salud', 'Adultos mayores']],
            ['Jorge Núñez', 'jorge@voluntmatch.test', 'Administración', 'Universidad César Vallejo', '6', '6-10', ['Lima', 'Los Olivos'], ['Estrategia' => 84, 'Análisis' => 80, 'Liderazgo' => 86, 'Comunicación' => 78, 'Trabajo en equipo' => 88], ['Emprendimiento', 'Deporte y recreación']],
            ['Fiorella Ramos', 'fiorella@voluntmatch.test', 'Comunicación Audiovisual', 'Universidad de Piura', '4', '1-5', ['Piura', 'Castilla'], ['Video' => 89, 'Creatividad' => 91, 'Comunicación' => 86, 'Trabajo en equipo' => 79, 'Liderazgo' => 68], ['Cultura y arte', 'Derechos humanos']],
            ['Pablo Cárdenas', 'pablo@voluntmatch.test', 'Contabilidad', 'Universidad San Ignacio de Loyola', '10', '11-15', ['Lima', 'La Victoria'], ['Finanzas' => 90, 'Análisis' => 88, 'Liderazgo' => 81, 'Comunicación' => 73, 'Trabajo en equipo' => 84], ['Emprendimiento', 'Educación']],
        ];

        $voluntarios = [];

        foreach ($datos as $indice => [$nombre, $email, $carrera, $universidad, $ciclo, $disponibilidad, [$region, $ciudad], $skills, $intereses]) {
            $voluntarios[] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => $this->passwordHash,
                    'role' => 'voluntario',
                    'phone' => '9'.str_pad((string) (30000000 + $indice), 8, '0', STR_PAD_LEFT),
                    'region' => $region,
                    'city' => $ciudad,
                    'dni' => (string) (70000001 + $indice),
                    'university' => $universidad,
                    'career' => $carrera,
                    'cycle' => $ciclo,
                    'availability' => $disponibilidad,
                    'interests' => $intereses,
                    'skills' => $skills,
                ],
            );
        }

        return $voluntarios;
    }

    /**
     * @param  array<int, Organizacion>  $organizaciones
     * @return array<int, Oportunidad>
     */
    private function crearOportunidades(array $organizaciones): array
    {
        $datos = [
            [0, 'Web accesible para todos', 'Inclusión', 'UX/UI', '4 h/semana', 'Remoto', 94, 'Rediseñar el portal de la organización para que sea usable con lectores de pantalla.'],
            [1, 'Refuerzo escolar en matemáticas', 'Educación', 'Docencia', '2 h/semana', 'Híbrido', 90, 'Acompañar a escolares de secundaria con refuerzo de matemáticas dos veces por semana.'],
            [2, 'Campaña de reciclaje escolar', 'Medio ambiente', 'Comunicación', 'Proyecto puntual', 'Presencial', 86, 'Diseñar y difundir una campaña de reciclaje en colegios de la ciudad.'],
            [3, 'Landing para donaciones', 'Tecnología', 'Desarrollo web', '6 h/semana', 'Remoto', 91, 'Construir una landing clara y rápida para recibir donaciones recurrentes.'],
            [4, 'Acompañamiento a adultos mayores', 'Adultos mayores', 'Empatía', '3 h/semana', 'Presencial', 82, 'Visitas y talleres de memoria con personas adultas mayores de la comunidad.'],
            [5, 'Podcast de salud comunitaria', 'Salud', 'Locución', '4 h/semana', 'Híbrido', 85, 'Producir episodios breves con consejos de salud para radios locales.'],
            [3, 'Análisis de encuestas de impacto', 'Educación', 'Análisis de datos', '5 h/semana', 'Remoto', 88, 'Ordenar y analizar las encuestas de nuestros talleres para medir resultados.'],
            [0, 'Identidad visual para comedor popular', 'Niñez y adolescencia', 'Diseño gráfico', '3 h/semana', 'Remoto', 87, 'Crear logotipo, menús y piezas para un comedor que atiende a 80 niños.'],
            [1, 'Taller de huerto urbano', 'Medio ambiente', 'Agricultura', 'Semanal', 'Presencial', 80, 'Guiar talleres de huerto urbano con familias del barrio.'],
            [1, 'Mentoría para emprendedores', 'Emprendimiento', 'Marketing', '2 h/semana', 'Remoto', 83, 'Acompañar a emprendedoras locales con su estrategia de comunicación.'],
            [2, 'App de transporte accesible', 'Inclusión y discapacidad', 'React', '6 h/semana', 'Remoto', 95, 'Prototipar una app que avise rutas accesibles para sillas de ruedas.'],
            [5, 'Traducción al quechua', 'Cultura y arte', 'Idiomas', 'Proyecto puntual', 'Remoto', 84, 'Traducir materiales educativos al quechua para escuelas rurales.'],
        ];

        $oportunidades = [];

        foreach ($datos as $indice => [$org, $titulo, $causa, $habilidad, $dedicacion, $modalidad, $afinidad, $resumen]) {
            $oportunidades[] = Oportunidad::updateOrCreate(
                ['titulo' => $titulo],
                [
                    'organizacion_id' => $organizaciones[$org]->id,
                    'causa' => $causa,
                    'habilidad' => $habilidad,
                    'dedicacion' => $dedicacion,
                    'modalidad' => $modalidad,
                    'afinidad' => $afinidad,
                    'resumen' => $resumen,
                    'aprenderas' => 'Experiencia real, acompañamiento del equipo y certificado digital para tu portafolio.',
                    'porque' => [
                        'Tu habilidad encaja con lo que busca la organización.',
                        'La dedicación es compatible con tu ciclo académico.',
                        'La causa está entre tus intereses.',
                    ],
                    'estado' => 'publicada',
                ],
            );
        }

        return $oportunidades;
    }

    /**
     * @param  array<int, User>  $voluntarios
     * @param  array<int, Oportunidad>  $oportunidades
     */
    private function crearPostulaciones(array $voluntarios, array $oportunidades): void
    {
        // Indices explicitos para que la cuenta demo de Sofia tenga historial.
        $plan = [
            0 => [[0, 'aceptado'], [4, 'en entrevista'], [10, 'en revision']],
            1 => [[3, 'en entrevista'], [10, 'aceptado'], [0, 'en revision']],
            2 => [[2, 'aceptado'], [11, 'en revision'], [7, 'en revision']],
            3 => [[6, 'en revision'], [3, 'en revision'], [9, 'en entrevista']],
            4 => [[4, 'aceptado'], [5, 'en entrevista']],
            5 => [[8, 'en revision'], [2, 'en entrevista']],
            6 => [[9, 'aceptado'], [7, 'en revision']],
            7 => [[10, 'en entrevista'], [3, 'en revision']],
            8 => [[1, 'aceptado'], [5, 'en revision']],
            9 => [[7, 'en entrevista'], [8, 'en revision']],
            10 => [[4, 'en revision'], [11, 'en entrevista']],
            11 => [[9, 'en revision']],
            12 => [[5, 'en revision'], [2, 'en revision']],
            13 => [[9, 'en revision'], [6, 'aceptado']],
        ];

        $notas = [
            'Me interesa aportar con mi tiempo y aprender del equipo.',
            'Tengo disponibilidad esta semana para empezar.',
            'Ya trabajé en un proyecto similar y quiero repetir la experiencia.',
        ];

        foreach ($plan as $indiceVoluntario => $asignaciones) {
            foreach ($asignaciones as [$indiceOportunidad, $estado]) {
                if (! isset($voluntarios[$indiceVoluntario], $oportunidades[$indiceOportunidad])) {
                    continue;
                }

                Postulacion::firstOrCreate(
                    [
                        'user_id' => $voluntarios[$indiceVoluntario]->id,
                        'oportunidad_id' => $oportunidades[$indiceOportunidad]->id,
                    ],
                    [
                        'estado' => $estado,
                        'nota' => $notas[($indiceVoluntario + $indiceOportunidad) % count($notas)],
                    ],
                );
            }
        }
    }

    /** Genera un RUC de demostracion que pasa el digito verificador. */
    private function rucDemo(int $semilla): string
    {
        $base = '20'.str_pad((string) (10000000 + $semilla), 8, '0', STR_PAD_LEFT);
        $factores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $base[$i] * $factores[$i];
        }

        $digito = (11 - ($suma % 11)) % 10;
        $ruc = $base.$digito;

        if (! Peru::rucValido($ruc)) {
            $this->command?->warn("RUC de demostracion invalido: {$ruc}");
        }

        return $ruc;
    }
}
