<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use App\Support\ChatDemo;
use App\Support\Peru;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Panel de la ONG (rol organizacion). Cada seccion vive en su propia
 * pagina y las acciones sobre candidatos persisten en la base de datos.
 */
class OngPanelController extends Controller
{
    public function resumen()
    {
        $datos = $this->contexto();
        $datos['metricas'] = $this->resumenMetricas($datos['postulaciones'], $datos['oportunidades']);
        $datos['pendientes'] = $this->pendientes($datos['postulaciones'], $datos['oportunidades']);
        $datos['hitos'] = $this->hitos($datos['postulaciones']);
        $datos['recientes'] = $datos['oportunidades']->take(3);

        return view('panel.organizacion.resumen', $datos);
    }

    public function oportunidades()
    {
        $datos = $this->contexto();
        $datos['totales'] = [
            'todas' => $datos['oportunidades']->count(),
            'publicadas' => $datos['oportunidades']->where('estado', 'publicada')->count(),
            'borradores' => $datos['oportunidades']->where('estado', 'borrador')->count(),
            'archivadas' => $datos['oportunidades']->where('estado', 'archivada')->count(),
        ];

        return view('panel.organizacion.oportunidades', $datos);
    }

    public function talento()
    {
        $datos = $this->contexto();
        $datos['talentos'] = $this->talentos();

        return view('panel.organizacion.talento', $datos);
    }

    public function candidatos(Request $request)
    {
        $datos = $this->contexto();
        $proyecto = (int) $request->query('proyecto');
        $postulaciones = $proyecto > 0
            ? $datos['postulaciones']->where('oportunidad_id', $proyecto)
            : $datos['postulaciones'];

        $datos['candidatos'] = $this->mapearCandidatos($postulaciones);

        return view('panel.organizacion.candidatos', $datos);
    }

    public function metricas()
    {
        $datos = $this->contexto();
        $datos['metricas'] = $this->resumenMetricas($datos['postulaciones'], $datos['oportunidades']);
        $datos['horas'] = $datos['postulaciones']
            ->where('estado', 'aceptado')
            ->sum(fn (Postulacion $postulacion) => $this->horasEstimadas($postulacion->oportunidad));
        $datos['areas'] = $this->areas($datos['postulaciones']);

        return view('panel.organizacion.metricas', $datos);
    }

    public function mensajes()
    {
        $datos = $this->contexto();
        $datos['conversaciones'] = ChatDemo::conversaciones();
        $datos['noLeidos'] = ChatDemo::noLeidos();

        return view('panel.organizacion.mensajes', $datos);
    }

    /** Ficha publica de la ONG dentro del directorio y el mapa. */
    public function perfil()
    {
        $datos = $this->contexto();
        $datos['sectores'] = Peru::SECTORES;
        $datos['cusco'] = ['lat' => -13.53195, 'lng' => -71.96746];

        return view('panel.organizacion.perfil', $datos);
    }

    /** Guarda los datos que se muestran en /ongs/{slug}. */
    public function actualizarPerfil(Request $request)
    {
        $organizacion = $this->ficha($request->user());

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'sector' => ['required', Rule::in(Peru::SECTORES)],
            'mision' => ['nullable', 'string', 'min:30', 'max:1000'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'como_participar' => ['nullable', 'string', 'max:2000'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'portada_url' => ['nullable', 'url', 'max:255'],
            'anio_fundacion' => ['nullable', 'integer', 'between:1900,'.now()->year],
        ], [
            'nombre.required' => 'El nombre publico de la organizacion es obligatorio.',
            'sector.required' => 'Selecciona la causa o sector principal.',
            'sector.in' => 'Selecciona un sector valido.',
            'mision.min' => 'La mision debe tener al menos 30 caracteres.',
            'sitio_web.url' => 'El sitio web debe ser una direccion valida (ej. https://mi-ong.org).',
            'logo_url.url' => 'El logo debe ser una URL valida.',
            'portada_url.url' => 'La portada debe ser una URL valida.',
            'latitud.between' => 'La latitud debe estar entre -90 y 90.',
            'longitud.between' => 'La longitud debe estar entre -180 y 180.',
            'anio_fundacion.between' => 'El anio de fundacion debe ser valido.',
        ]);

        $organizacion->update($datos);

        return back()->with('status', 'Ficha publica actualizada. Tus cambios ya se ven en el directorio.');
    }

    /**
     * Mueve una postulacion entre etapas o guarda una nota interna.
     * Solo la ONG duena de la oportunidad puede hacerlo.
     */
    public function actualizarCandidato(Request $request, Postulacion $postulacion): JsonResponse
    {
        $this->autorizarPostulacion($postulacion);

        $datos = $request->validate([
            'estado' => ['sometimes', Rule::in(Postulacion::ESTADOS)],
            'nota_interna' => ['nullable', 'string', 'max:2000'],
        ]);

        if (array_key_exists('estado', $datos)) {
            $postulacion->estado = $datos['estado'];
        }

        if ($request->has('nota_interna')) {
            $postulacion->nota_interna = $datos['nota_interna'] ?? null;
        }

        $postulacion->save();

        return response()->json([
            'ok' => true,
            'estado' => $postulacion->estado,
            'etapa' => $postulacion->etapa(),
            'legible' => $postulacion->estadoLegible(),
            'nota_interna' => $postulacion->nota_interna,
        ]);
    }

    /** Publica una nueva oportunidad desde el panel de la ONG. */
    public function guardarOportunidad(Request $request)
    {
        $organizacion = $this->ficha($request->user());

        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'causa' => ['required', 'string', 'max:120'],
            'habilidad' => ['required', 'string', 'max:120'],
            'dedicacion' => ['required', 'string', 'max:80'],
            'modalidad' => ['required', 'string', 'max:40'],
            'resumen' => ['required', 'string', 'max:1000'],
        ]);

        $oportunidad = $organizacion->oportunidades()->create($datos + [
            'estado' => 'publicada',
            'afinidad' => 80,
            'aprenderas' => 'Experiencia real, acompañamiento de la ONG y certificado digital.',
        ]);

        return back()->with('status', "«{$oportunidad->titulo}» quedó publicada.");
    }

    private function autorizarPostulacion(Postulacion $postulacion): void
    {
        $organizacion = auth()->user()->organizacion;

        abort_unless(
            $organizacion !== null
            && $postulacion->oportunidad?->organizacion_id === $organizacion->id,
            403,
        );
    }

    /** Contexto comun a todas las paginas del panel. */
    private function contexto(): array
    {
        $user = auth()->user();
        $organizacion = $this->ficha($user);

        $oportunidades = $organizacion->oportunidades()
            ->withCount('postulaciones')
            ->latest()
            ->get();

        $postulaciones = Postulacion::query()
            ->whereHas('oportunidad', fn ($query) => $query->where('organizacion_id', $organizacion->id))
            ->with(['user:id,name,career,cycle,university,availability,skills,interests', 'oportunidad:id,titulo,causa,dedicacion,afinidad'])
            ->latest()
            ->get();

        return [
            'user' => $user,
            'organizacion' => $organizacion,
            'initials' => $this->iniciales($user->name),
            'oportunidades' => $oportunidades,
            'postulaciones' => $postulaciones,
            'anuncios' => Anuncio::activos()
                ->whereIn('audiencia', ['todos', 'organizacion'])
                ->latest()
                ->limit(3)
                ->get(),
            'noLeidos' => ChatDemo::noLeidos(),
        ];
    }

    /** Crea la ficha de organizacion si la cuenta aun no la tiene. */
    private function ficha(User $user): Organizacion
    {
        return $user->organizacion()->firstOrCreate([], [
            'nombre' => $user->name,
            'ruc' => $user->ruc,
            'sector' => $user->sector,
            'mision' => $user->mission,
            'verificada' => false,
        ]);
    }

    /** @param Collection<int, Postulacion> $postulaciones */
    private function resumenMetricas(Collection $postulaciones, Collection $oportunidades): array
    {
        $afinidad = $oportunidades->avg('afinidad');

        return [
            'voluntarios' => $postulaciones->pluck('user_id')->unique()->count(),
            'postulaciones' => $postulaciones->count(),
            'aceptados' => $postulaciones->where('estado', 'aceptado')->count(),
            'afinidad' => $afinidad === null ? 0 : (int) round($afinidad),
            'oportunidades' => $oportunidades->count(),
        ];
    }

    /** @param Collection<int, Postulacion> $postulaciones */
    private function pendientes(Collection $postulaciones, Collection $oportunidades): array
    {
        $porRevisar = $postulaciones->where('estado', 'en revision');
        $entrevistas = $postulaciones->where('estado', 'en entrevista');
        $borradores = $oportunidades->where('estado', 'borrador');

        $tareas = [];

        if ($porRevisar->isNotEmpty()) {
            $tareas[] = [
                'icon' => 'users',
                'title' => $porRevisar->count().' postulaciones por revisar',
                'detail' => 'Revisa perfiles y responde a los estudiantes.',
                'action' => 'Ver candidatos',
                'route' => 'ong.candidatos',
            ];
        }

        if ($entrevistas->isNotEmpty()) {
            $tareas[] = [
                'icon' => 'clock',
                'title' => $entrevistas->count().' entrevistas en curso',
                'detail' => 'Coordina horarios desde el Match Inbox.',
                'action' => 'Abrir bandeja',
                'route' => 'ong.candidatos',
            ];
        }

        if ($borradores->isNotEmpty()) {
            $tareas[] = [
                'icon' => 'heart',
                'title' => $borradores->count().' oportunidades en borrador',
                'detail' => 'Completa y publica para recibir postulantes.',
                'action' => 'Gestionar',
                'route' => 'ong.oportunidades',
            ];
        }

        return $tareas;
    }

    /** @param Collection<int, Postulacion> $postulaciones */
    private function hitos(Collection $postulaciones): array
    {
        return $postulaciones
            ->where('estado', 'en entrevista')
            ->take(3)
            ->map(fn (Postulacion $postulacion) => [
                'day' => 'ENT',
                'date' => (string) $postulacion->updated_at?->format('d'),
                'title' => 'Entrevista con '.$postulacion->user?->name,
                'meta' => 'Por coordinar · '.$postulacion->oportunidad?->titulo,
                'type' => 'reunion',
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function talentos(): array
    {
        return User::query()
            ->where('role', 'voluntario')
            ->orderBy('name')
            ->get()
            ->map(function (User $voluntario) {
                $skills = is_array($voluntario->skills) ? array_keys($voluntario->skills) : [];
                $skills = array_slice($skills, 0, 3);

                if ($skills === []) {
                    $skills = array_slice($voluntario->interests ?? [], 0, 2) ?: ['Voluntariado'];
                }

                $hours = match ($voluntario->availability) {
                    '1-5' => 5,
                    '6-10' => 10,
                    '11-15' => 15,
                    '16-20' => 20,
                    '20+' => 25,
                    default => 6,
                };

                return [
                    'id' => $voluntario->id,
                    'name' => $voluntario->name,
                    'initials' => $this->iniciales($voluntario->name),
                    'detail' => trim(($voluntario->career ?: 'Estudiante').' · '.($voluntario->cycle ? $voluntario->cycle.'º curso' : 'Voluntariado'), ' ·'),
                    'career' => $voluntario->career ?: 'Otra',
                    'skills' => $skills,
                    'hours' => $hours,
                    'match' => min(98, 70 + count($skills) * 8),
                ];
            })
            ->all();
    }

    /** @param Collection<int, Postulacion> $postulaciones */
    private function mapearCandidatos(Collection $postulaciones): array
    {
        return $postulaciones
            ->map(function (Postulacion $postulacion) {
                $voluntario = $postulacion->user;

                return [
                    'id' => $postulacion->id,
                    'name' => $voluntario?->name ?? 'Estudiante',
                    'initials' => $this->iniciales($voluntario?->name ?? 'E'),
                    'detail' => trim(($voluntario?->career ?: 'Estudiante').' · '.($voluntario?->cycle ? $voluntario?->cycle.'º curso' : 'Voluntariado'), ' ·'),
                    'skills' => array_slice(is_array($voluntario?->skills) ? array_keys($voluntario->skills) : [], 0, 2) ?: ['Voluntariado'],
                    'match' => $postulacion->oportunidad?->afinidad ?? 80,
                    'proyecto' => $postulacion->oportunidad?->titulo ?? 'Proyecto',
                    'estado' => $postulacion->estado,
                    'etapa' => $postulacion->etapa(),
                    'legible' => $postulacion->estadoLegible(),
                    'nota' => $postulacion->nota,
                    'nota_interna' => $postulacion->nota_interna,
                ];
            })
            ->all();
    }

    /** @param Collection<int, Postulacion> $postulaciones */
    private function areas(Collection $postulaciones): array
    {
        return $postulaciones
            ->where('estado', 'aceptado')
            ->groupBy(fn (Postulacion $postulacion) => $postulacion->oportunidad?->causa ?? 'Otras')
            ->map(fn (Collection $grupo, string $causa) => [
                'label' => $causa,
                'value' => min(100, $grupo->count() * 25),
            ])
            ->values()
            ->all();
    }

    private function horasEstimadas(?Oportunidad $oportunidad): int
    {
        if ($oportunidad === null) {
            return 0;
        }

        if (preg_match('/(\d+)\s*h/i', $oportunidad->dedicacion, $coincidencia)) {
            return (int) $coincidencia[1] * 4;
        }

        return str_contains(mb_strtolower($oportunidad->dedicacion), 'puntual') ? 12 : 8;
    }

    private function iniciales(string $nombre): string
    {
        return Collection::make(explode(' ', trim($nombre)))
            ->filter()
            ->map(fn ($palabra) => mb_strtoupper(mb_substr($palabra, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
