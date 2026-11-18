<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Support\ChatDemo;
use App\Support\PerfilVoluntario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Espacio del estudiante (rol voluntario). Cada seccion es una pagina
 * propia con URL estable: resumen, skills, matches, postulaciones,
 * impacto y mensajes.
 */
class VoluntarioPanelController extends Controller
{
    public function resumen()
    {
        $datos = $this->contexto();
        $datos['oportunidades'] = $this->recomendadas($datos['user'])->limit(3)->get();
        $datos['metricas'] = [
            'activas' => $datos['postulaciones']->whereIn('estado', ['en revision', 'en entrevista', 'aceptado'])->count(),
            'aceptadas' => $datos['postulaciones']->where('estado', 'aceptado')->count(),
            'total' => $datos['postulaciones']->count(),
            'afinidad' => $this->afinidadMedia(),
        ];

        return view('panel.voluntario.resumen', $datos);
    }

    public function skills()
    {
        $datos = $this->contexto();
        $datos['puedeHacerMatch'] = PerfilVoluntario::puedeHacerMatch($datos['user']);
        $datos['faltantesClave'] = PerfilVoluntario::faltantesClave($datos['user']);
        $datos['puntos'] = PerfilVoluntario::puntos($datos['user']);

        return view('panel.voluntario.skills', $datos);
    }

    public function matches()
    {
        $datos = $this->contexto();
        $datos['oportunidades'] = $this->recomendadas($datos['user'])->get();

        return view('panel.voluntario.matches', $datos);
    }

    public function postulaciones()
    {
        $datos = $this->contexto();
        $datos['metricas'] = [
            'activas' => $datos['postulaciones']->whereIn('estado', ['en revision', 'en entrevista', 'aceptado'])->count(),
            'revision' => $datos['postulaciones']->where('estado', 'en revision')->count(),
            'entrevista' => $datos['postulaciones']->where('estado', 'en entrevista')->count(),
            'aceptadas' => $datos['postulaciones']->where('estado', 'aceptado')->count(),
        ];
        $datos['faltantes'] = PerfilVoluntario::faltantes($datos['user']);
        $datos['puntos'] = PerfilVoluntario::puntos($datos['user']);

        return view('panel.voluntario.postulaciones', $datos);
    }

    public function impacto()
    {
        $datos = $this->contexto();
        $aceptadas = $datos['postulaciones']->where('estado', 'aceptado');
        $horas = $aceptadas->sum(fn (Postulacion $postulacion) => $this->horasEstimadas($postulacion->oportunidad));

        $datos['aceptadas'] = $aceptadas->values();
        $datos['horas'] = (int) $horas;
        $datos['insignias'] = [
            ['icon' => 'heart', 'label' => 'Primer match', 'done' => $datos['postulaciones']->isNotEmpty()],
            ['icon' => 'users', 'label' => 'Alma de equipo', 'done' => $aceptadas->count() >= 2],
            ['icon' => 'spark', 'label' => '10 horas', 'done' => $horas >= 10],
        ];

        return view('panel.voluntario.impacto', $datos);
    }

    public function mensajes()
    {
        $datos = $this->contexto();
        $datos['conversaciones'] = ChatDemo::conversaciones();
        $datos['noLeidos'] = ChatDemo::noLeidos();

        return view('panel.voluntario.mensajes', $datos);
    }

    /**
     * Postular desde la baraja de matches (fetch). Idempotente: repetir
     * el gesto no crea filas duplicadas.
     */
    public function postular(Request $request, Oportunidad $oportunidad): JsonResponse
    {
        abort_unless($oportunidad->exists, 404);

        $postulacion = Postulacion::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'oportunidad_id' => $oportunidad->id,
            ],
            [
                'estado' => 'en revision',
                'nota' => $request->string('nota')->trim()->limit(1000)->toString() ?: null,
            ],
        );

        return response()->json([
            'ok' => true,
            'creada' => $postulacion->wasRecentlyCreated,
            'mensaje' => $postulacion->wasRecentlyCreated
                ? "¡Postulación enviada a «{$oportunidad->titulo}»!"
                : 'Ya estabas postulado a este reto.',
            'postulaciones' => $request->user()->postulaciones()->count(),
            'estado' => $postulacion->estadoLegible(),
        ]);
    }

    /** Oportunidades publicadas a las que el estudiante aun no postula. */
    private function recomendadas($user): Builder
    {
        return Oportunidad::query()
            ->publicadas()
            ->with('organizacion:id,nombre')
            ->whereDoesntHave('postulaciones', fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('afinidad')
            ->orderBy('id');
    }

    /** Contexto comun a todas las paginas del panel. */
    private function contexto(): array
    {
        $user = auth()->user();
        $postulaciones = $user->postulaciones()
            ->with('oportunidad.organizacion:id,nombre')
            ->latest()
            ->get();

        return [
            'user' => $user,
            'initials' => $this->iniciales($user->name),
            'porcentaje' => PerfilVoluntario::porcentaje($user),
            'skills' => PerfilVoluntario::skills($user),
            'destacadas' => PerfilVoluntario::destacadas($user),
            'postulaciones' => $postulaciones,
            'anuncios' => Anuncio::activos()
                ->whereIn('audiencia', ['todos', 'voluntario'])
                ->latest()
                ->limit(3)
                ->get(),
            'noLeidos' => ChatDemo::noLeidos(),
        ];
    }

    private function afinidadMedia(): int
    {
        $media = Oportunidad::query()->publicadas()->avg('afinidad');

        return $media === null ? 0 : (int) round($media);
    }

    /** Estimacion simple de horas aportadas a partir de la dedicacion. */
    private function horasEstimadas(?Oportunidad $oportunidad): int
    {
        if ($oportunidad === null) {
            return 0;
        }

        if (preg_match('/(\d+)\s*h/i', $oportunidad->dedicacion, $coincidencia)) {
            // Cuatro semanas de aporte como referencia del impacto acumulado.
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
