<?php

namespace Database\Seeders;

use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use App\Support\OportunidadesDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Comunidad de demostracion: las tres ONG del catalogo, cada una con
 * su cuenta de acceso, y estudiantes con perfil completo para que
 * ambos paneles tengan datos reales.
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

        // El catalogo es la fuente unica de oportunidades (3 ONG x 10).
        $this->call(OportunidadSeeder::class);

        $this->crearOrganizaciones();
        $voluntarios = $this->crearVoluntarios();
        $this->crearPostulaciones($voluntarios);
    }

    /** @return array<int, Organizacion> */
    private function crearOrganizaciones(): array
    {
        $organizaciones = [];

        foreach (OportunidadesDemo::organizaciones() as $datos) {
            $usuario = User::updateOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['nombre'],
                    'password' => $this->passwordHash,
                    'role' => 'organizacion',
                    'phone' => '9'.str_pad((string) (20000000 + count($organizaciones)), 8, '0', STR_PAD_LEFT),
                    'region' => $datos['region'],
                    'city' => $datos['ciudad'],
                    'ruc' => $datos['ruc'],
                    'org_type' => 'ONG',
                    'legal_rep' => 'Representante de '.$datos['nombre'],
                    'sector' => $datos['sector'],
                    'mission' => $datos['mision'],
                    'website' => 'https://'.str($datos['nombre'])->slug().'.org',
                ],
            );

            $organizaciones[] = Organizacion::updateOrCreate(
                ['ruc' => $datos['ruc']],
                [
                    'user_id' => $usuario->id,
                    'nombre' => $datos['nombre'],
                    'sector' => $datos['sector'],
                    'mision' => $datos['mision'],
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
     * Historial de postulaciones por estudiante. Se usan slugs para que
     * el plan siga siendo valido aunque cambie el orden del catalogo.
     *
     * @param  array<int, User>  $voluntarios
     */
    private function crearPostulaciones(array $voluntarios): void
    {
        $plan = [
            0 => [['rediseno-web-accesible', 'aceptado'], ['acompanamiento-a-familias', 'en entrevista'], ['app-de-rutas-accesibles', 'en revision']],
            1 => [['app-de-rutas-accesibles', 'en entrevista'], ['analisis-de-encuestas-de-aprendizaje', 'aceptado'], ['rediseno-web-accesible', 'en revision']],
            2 => [['campana-de-consumo-responsable', 'aceptado'], ['educacion-ambiental-en-colegios', 'en revision'], ['podcast-ambiental-juvenil', 'en revision']],
            3 => [['analisis-de-encuestas-de-aprendizaje', 'en revision'], ['mapa-de-reciclaje-del-barrio', 'en revision'], ['orientacion-vocacional', 'en entrevista']],
            4 => [['acompanamiento-a-familias', 'aceptado'], ['taller-de-tecnologia-adaptada', 'en entrevista']],
            5 => [['huerto-urbano-comunitario', 'en revision'], ['campana-de-consumo-responsable', 'en entrevista']],
            6 => [['campana-de-empleo-inclusivo', 'aceptado'], ['escuela-de-padres-y-madres', 'en revision']],
            7 => [['app-de-rutas-accesibles', 'en entrevista'], ['diseno-de-material-didactico', 'en revision']],
            8 => [['mentoria-de-matematicas', 'aceptado'], ['refuerzo-de-comprension-lectora', 'en revision']],
            9 => [['senaletica-inclusiva-para-museos', 'en entrevista'], ['diseno-de-senaletica-del-bosque', 'en revision']],
            10 => [['acompanamiento-a-familias', 'en revision'], ['educacion-ambiental-en-colegios', 'en entrevista']],
            11 => [['orientacion-vocacional', 'en revision']],
            12 => [['subtitulado-de-videos-educativos', 'en revision'], ['podcast-ambiental-juvenil', 'en revision']],
            13 => [['analisis-de-encuestas-de-aprendizaje', 'aceptado'], ['tutoria-virtual-de-ingles', 'en revision']],
        ];

        $notas = [
            'Me interesa aportar con mi tiempo y aprender del equipo.',
            'Tengo disponibilidad esta semana para empezar.',
            'Ya trabajé en un proyecto similar y quiero repetir la experiencia.',
        ];

        $oportunidades = Oportunidad::query()
            ->whereIn('slug', collect($plan)->flatten(1)->pluck(0)->unique())
            ->get()
            ->keyBy('slug');

        foreach ($plan as $indiceVoluntario => $asignaciones) {
            foreach ($asignaciones as [$slug, $estado]) {
                $voluntario = $voluntarios[$indiceVoluntario] ?? null;
                $oportunidad = $oportunidades->get($slug);

                if ($voluntario === null || $oportunidad === null) {
                    continue;
                }

                Postulacion::firstOrCreate(
                    [
                        'user_id' => $voluntario->id,
                        'oportunidad_id' => $oportunidad->id,
                    ],
                    [
                        'estado' => $estado,
                        'nota' => $notas[($indiceVoluntario + $oportunidad->id) % count($notas)],
                    ],
                );
            }
        }
    }
}
