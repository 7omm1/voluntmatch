<?php

namespace App\Services\Chatbot\Mcp;

use App\Models\CrmContact;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use App\Services\Integrations\IntegrationGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Herramientas locales del asistente.
 *
 * Todas las consultas se limitan a datos públicos o a la cuenta que está
 * conversando. Ninguna herramienta acepta un user_id como argumento: el
 * propietario se toma del contexto de sesión, nunca del modelo.
 */
class LocalToolHandler
{
    public function __construct(private readonly IntegrationGateway $integrations) {}

    /**
     * @param  array<string, mixed>  $args
     * @param  array{user: ?User, audience: string, api_client?: mixed}  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, links?: list<array<string, string>>}
     */
    public function execute(string $tool, array $args, array $context): array
    {
        return match ($tool) {
            'buscar_oportunidades' => $this->buscarOportunidades($args),
            'listar_ongs' => $this->listarOngs($args),
            'resumen_plataforma' => $this->resumenPlataforma(),
            'consultar_mis_postulaciones' => $this->consultarMisPostulaciones($context['user']),
            'listar_mis_oportunidades' => $this->listarMisOportunidades($context['user']),
            'consultar_mi_organizacion' => $this->consultarMiOrganizacion($context['user']),
            'consultar_estado_cuenta' => $this->consultarEstadoCuenta($context['user']),
            'crear_ticket_soporte' => $this->crearTicketSoporte($args, $context),
            'enviar_correo' => $this->enviarCorreo($args, $context),
            'agendar_evento' => $this->agendarEvento($args, $context),
            'registrar_contacto_crm' => $this->registrarContactoCrm($args, $context),
            'notificar_candidato' => $this->notificarCandidato($args, $context),
            'consultar_mis_notificaciones' => $this->consultarMisNotificaciones($context['user']),
            default => $this->error('La herramienta solicitada no está disponible.'),
        };
    }

    /** @param array<string, mixed> $args */
    private function buscarOportunidades(array $args): array
    {
        $query = $this->limpiarLike((string) ($args['query'] ?? ''));
        $limit = max(1, min(8, (int) ($args['limit'] ?? 5)));

        $oportunidades = Oportunidad::query()
            ->publicadas()
            ->with('organizacion:id,nombre,slug,verificada')
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%'.$query.'%';
                $builder->where(function ($sub) use ($like) {
                    $sub->where('titulo', 'like', $like)
                        ->orWhere('resumen', 'like', $like)
                        ->orWhere('causa', 'like', $like)
                        ->orWhere('habilidad', 'like', $like)
                        ->orWhere('modalidad', 'like', $like);
                });
            })
            ->orderByDesc('destacada')
            ->latest()
            ->limit($limit)
            ->get();

        if ($oportunidades->isEmpty()) {
            return [
                'ok' => true,
                'summary' => $query === ''
                    ? 'Ahora mismo no hay oportunidades publicadas. Puedes revisar el catálogo completo en VoluntMatch.'
                    : 'No encontré oportunidades que coincidan con esa búsqueda. Prueba con una causa o habilidad distinta.',
                'data' => ['total' => 0, 'items' => []],
                'links' => [
                    ['label' => 'Ver todas las oportunidades', 'url' => route('oportunidades')],
                ],
            ];
        }

        $items = $oportunidades->map(fn (Oportunidad $oportunidad) => [
            'title' => $oportunidad->titulo,
            'meta' => collect([
                $oportunidad->organizacion?->nombre,
                $oportunidad->causa,
                $oportunidad->modalidad,
                $oportunidad->dedicacion,
            ])->filter()->implode(' · '),
            'url' => route('detalle-oportunidad', ['oportunidad' => $oportunidad->slug]),
        ])->values()->all();

        return [
            'ok' => true,
            'summary' => 'Encontré '.count($items).' '.(count($items) === 1 ? 'oportunidad' : 'oportunidades').' que pueden encajar.',
            'data' => ['total' => count($items), 'items' => $items],
            'links' => array_map(
                fn (array $item) => ['label' => $item['title'], 'url' => $item['url']],
                $items
            ),
        ];
    }

    /** @param array<string, mixed> $args */
    private function listarOngs(array $args): array
    {
        $query = $this->limpiarLike((string) ($args['query'] ?? ''));
        $limit = max(1, min(8, (int) ($args['limit'] ?? 5)));

        $ongs = Organizacion::query()
            ->verificadas()
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%'.$query.'%';
                $builder->where(function ($sub) use ($like) {
                    $sub->where('nombre', 'like', $like)
                        ->orWhere('sector', 'like', $like)
                        ->orWhere('ciudad', 'like', $like)
                        ->orWhere('mision', 'like', $like);
                });
            })
            ->orderBy('nombre')
            ->limit($limit)
            ->get();

        if ($ongs->isEmpty()) {
            return [
                'ok' => true,
                'summary' => 'No encontré organizaciones verificadas con ese criterio. Puedes explorar el directorio completo.',
                'data' => ['total' => 0, 'items' => []],
                'links' => [['label' => 'Ver directorio de ONG', 'url' => route('ongs.index')]],
            ];
        }

        $items = $ongs->map(fn (Organizacion $ong) => [
            'title' => $ong->nombre,
            'meta' => collect([$ong->sector, $ong->ciudad])->filter()->implode(' · '),
            'url' => route('ongs.show', ['organizacion' => $ong->slug]),
        ])->values()->all();

        return [
            'ok' => true,
            'summary' => 'Encontré '.count($items).' '.(count($items) === 1 ? 'organización verificada' : 'organizaciones verificadas').'.',
            'data' => ['total' => count($items), 'items' => $items],
            'links' => array_map(fn (array $item) => ['label' => $item['title'], 'url' => $item['url']], $items),
        ];
    }

    private function resumenPlataforma(): array
    {
        $data = [
            'oportunidades_publicadas' => Oportunidad::publicadas()->count(),
            'organizaciones_verificadas' => Organizacion::verificadas()->count(),
            'voluntarios' => User::where('role', 'voluntario')->count(),
            'postulaciones' => Postulacion::count(),
        ];

        return [
            'ok' => true,
            'summary' => sprintf(
                'VoluntMatch tiene %d oportunidades publicadas, %d organizaciones verificadas y %d estudiantes voluntarios. Se han registrado %d postulaciones.',
                $data['oportunidades_publicadas'],
                $data['organizaciones_verificadas'],
                $data['voluntarios'],
                $data['postulaciones']
            ),
            'data' => $data,
            'links' => [
                ['label' => 'Explorar oportunidades', 'url' => route('oportunidades')],
                ['label' => 'Conocer las ONG', 'url' => route('ongs.index')],
            ],
        ];
    }

    private function consultarMisPostulaciones(?User $user): array
    {
        if (! $user) {
            return $this->authRequired('Para consultar tus postulaciones necesitas iniciar sesión.');
        }

        $postulaciones = Postulacion::query()
            ->with('oportunidad:id,titulo,slug,estado')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        if ($postulaciones->isEmpty()) {
            return [
                'ok' => true,
                'summary' => 'Todavía no tienes postulaciones registradas. Puedes explorar oportunidades y postularte desde la ficha de cada reto.',
                'data' => ['total' => 0, 'items' => []],
                'links' => [
                    ['label' => 'Ver oportunidades', 'url' => route('oportunidades')],
                    ['label' => 'Ir a mis matches', 'url' => route('voluntario.matches')],
                ],
            ];
        }

        $items = $postulaciones->map(fn (Postulacion $postulacion) => [
            'title' => $postulacion->oportunidad?->titulo ?? 'Oportunidad eliminada',
            'meta' => 'Estado: '.$postulacion->estadoLegible().' · '.$postulacion->created_at?->format('d/m/Y'),
            'url' => $postulacion->oportunidad?->slug
                ? route('detalle-oportunidad', ['oportunidad' => $postulacion->oportunidad->slug])
                : null,
        ])->values()->all();

        return [
            'ok' => true,
            'summary' => 'Tienes '.count($items).' '.(count($items) === 1 ? 'postulación registrada' : 'postulaciones registradas').'. Te muestro el estado de cada una.',
            'data' => ['total' => count($items), 'items' => $items],
            'links' => [
                ['label' => 'Ver seguimiento completo', 'url' => route('voluntario.postulaciones')],
            ],
        ];
    }

    private function listarMisOportunidades(?User $user): array
    {
        if (! $user) {
            return $this->authRequired('Para revisar las oportunidades de tu organización necesitas iniciar sesión.');
        }

        $organizacion = Organizacion::where('user_id', $user->id)->first();

        if (! $organizacion) {
            return [
                'ok' => false,
                'summary' => 'Tu cuenta no tiene una ficha de organización asociada. Si registraste una ONG, revisa el estado de verificación o escribe a soporte.',
            ];
        }

        $oportunidades = Oportunidad::query()
            ->where('organizacion_id', $organizacion->id)
            ->withCount('postulaciones')
            ->latest()
            ->limit(10)
            ->get();

        if ($oportunidades->isEmpty()) {
            return [
                'ok' => true,
                'summary' => 'Tu organización aún no tiene oportunidades publicadas. Puedes crear una desde el panel de la ONG.',
                'data' => ['total' => 0, 'items' => []],
                'links' => [['label' => 'Publicar una oportunidad', 'url' => route('ong.oportunidades')]],
            ];
        }

        $items = $oportunidades->map(fn (Oportunidad $oportunidad) => [
            'title' => $oportunidad->titulo,
            'meta' => ucfirst($oportunidad->estado).' · '.$oportunidad->postulaciones_count.' postulaciones',
            'url' => route('detalle-oportunidad', ['oportunidad' => $oportunidad->slug]),
        ])->values()->all();

        return [
            'ok' => true,
            'summary' => 'Tu organización tiene '.count($items).' '.(count($items) === 1 ? 'oportunidad registrada' : 'oportunidades registradas').'.',
            'data' => ['total' => count($items), 'items' => $items],
            'links' => [['label' => 'Gestionar oportunidades', 'url' => route('ong.oportunidades')]],
        ];
    }

    private function consultarMiOrganizacion(?User $user): array
    {
        if (! $user) {
            return $this->authRequired('Para revisar los datos de tu organización necesitas iniciar sesión.');
        }

        $organizacion = Organizacion::query()
            ->withCount([
                'oportunidades as oportunidades_count',
                'oportunidadesPublicadas as oportunidades_publicadas_count',
            ])
            ->where('user_id', $user->id)
            ->first();

        if (! $organizacion) {
            return [
                'ok' => false,
                'summary' => 'No encontré una organización asociada a tu cuenta. Puedes registrar tu ONG desde la página de registro.',
                'links' => [['label' => 'Registrar mi ONG', 'url' => route('registro-organizacion')]],
            ];
        }

        return [
            'ok' => true,
            'summary' => sprintf(
                '%s está %s. Sector: %s. Ciudad: %s. Tiene %d oportunidades registradas y %d publicadas.',
                $organizacion->nombre,
                $organizacion->verificada ? 'verificada' : 'pendiente de verificación',
                $organizacion->sector ?: 'sin sector declarado',
                $organizacion->ciudad ?: 'sin ciudad declarada',
                $organizacion->oportunidades_count,
                $organizacion->oportunidades_publicadas_count
            ),
            'data' => [
                'nombre' => $organizacion->nombre,
                'verificada' => (bool) $organizacion->verificada,
                'sector' => $organizacion->sector,
                'ciudad' => $organizacion->ciudad,
                'oportunidades' => $organizacion->oportunidades_count,
                'publicadas' => $organizacion->oportunidades_publicadas_count,
            ],
            'links' => [
                ['label' => 'Ver mi organización', 'url' => route('ong.perfil')],
                ['label' => 'Gestionar oportunidades', 'url' => route('ong.oportunidades')],
            ],
        ];
    }

    private function consultarEstadoCuenta(?User $user): array
    {
        if (! $user) {
            return $this->authRequired('Para revisar tu cuenta necesitas iniciar sesión.');
        }

        return [
            'ok' => true,
            'summary' => sprintf(
                'Tu cuenta está activa. Nombre: %s. Rol: %s. Miembro desde %s.',
                $user->name,
                $user->rolLegible(),
                $user->created_at?->translatedFormat('F \d\e Y') ?? 'fecha no disponible'
            ),
            'data' => [
                'nombre' => $user->name,
                'rol' => $user->role,
                'activa' => ! $user->estaBloqueado(),
                'miembro_desde' => $user->created_at?->toDateString(),
            ],
            'links' => [
                ['label' => 'Ir a mi espacio', 'url' => route('home')],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array{user: ?User, audience: string, api_client?: mixed}  $context
     */
    private function crearTicketSoporte(array $args, array $context): array
    {
        $user = $context['user'] ?? null;

        if (! $user) {
            return $this->authRequired('Para abrir un caso de soporte necesitas iniciar sesión. También puedes escribirnos desde la página de contacto.');
        }

        $result = $this->integrations->openHelpdeskCase($user, [
            'subject' => Str::limit((string) ($args['asunto'] ?? 'Solicitud desde el asistente'), 160, ''),
            'message' => Str::limit((string) ($args['mensaje'] ?? ''), 3000, ''),
            'priority' => in_array($args['prioridad'] ?? 'media', ['alta', 'media', 'baja'], true)
                ? $args['prioridad']
                : 'media',
            'channel' => 'asistente',
        ], ['api_client' => $context['api_client'] ?? null]);

        $result['links'] = [['label' => 'Ver mis casos de soporte', 'url' => route('home')]];

        return $result;
    }

    /**
     * Correo real a través del canal configurado. El gateway decide si el
     * destinatario está permitido para el rol autenticado.
     *
     * @param  array<string, mixed>  $args
     * @param  array{user: ?User, audience: string, api_client?: mixed}  $context
     */
    private function enviarCorreo(array $args, array $context): array
    {
        $user = $context['user'] ?? null;

        if (! $user) {
            return $this->authRequired('Para enviar un correo desde el asistente necesitas iniciar sesión.');
        }

        $destinatario = trim((string) ($args['destinatario'] ?? ''));
        $destinatario = $destinatario !== '' ? $destinatario : (string) $user->email;

        $result = $this->integrations->sendMail(
            $user,
            $destinatario,
            (string) ($args['asunto'] ?? ''),
            (string) ($args['mensaje'] ?? ''),
            ['api_client' => $context['api_client'] ?? null]
        );

        $result['links'] = [];

        return $result;
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array{user: ?User, audience: string, api_client?: mixed}  $context
     */
    private function agendarEvento(array $args, array $context): array
    {
        $user = $context['user'] ?? null;

        if (! $user) {
            return $this->authRequired('Para agendar un evento necesitas iniciar sesión.');
        }

        try {
            $inicio = Carbon::parse((string) ($args['inicio'] ?? ''), (string) config('integrations.calendar.timezone', 'America/Lima'));
        } catch (\Throwable) {
            return [
                'ok' => false,
                'summary' => 'Necesito una fecha y hora válidas para el evento (por ejemplo 2026-11-25 15:00).',
            ];
        }

        if ($inicio->isPast()) {
            return ['ok' => false, 'summary' => 'La fecha del evento ya pasó. Dame una fecha futura.'];
        }

        $result = $this->integrations->createCalendarEvent($user, [
            'title' => Str::limit((string) ($args['titulo'] ?? 'Reunión de voluntariado'), 160, ''),
            'description' => Str::limit((string) ($args['descripcion'] ?? ''), 2000, ''),
            'start' => $inicio->toIso8601String(),
            'duration_minutes' => max(15, min(480, (int) ($args['duracion_min'] ?? 60))),
            'attendee' => $user->email,
        ], ['api_client' => $context['api_client'] ?? null]);

        $link = $result['data']['invitation_url'] ?? null;
        $result['links'] = is_string($link)
            ? [['label' => 'Descargar invitación .ics', 'url' => $link]]
            : [];

        return $result;
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array{user: ?User, audience: string, api_client?: mixed}  $context
     */
    private function registrarContactoCrm(array $args, array $context): array
    {
        $user = $context['user'] ?? null;

        if (! $user) {
            return $this->authRequired('Para registrar contactos en el CRM necesitas iniciar sesión.');
        }

        if (! in_array($context['audience'], ['organizacion', 'admin'], true)) {
            return $this->error('Solo las organizaciones y la administración pueden registrar contactos en el CRM.');
        }

        $organizacion = $context['audience'] === 'organizacion'
            ? Organizacion::where('user_id', $user->id)->first()
            : null;

        $etapa = (string) ($args['etapa'] ?? 'nuevo');
        $result = $this->integrations->registerCrmContact($user, [
            'name' => Str::limit((string) ($args['nombre'] ?? ''), 160, ''),
            'email' => mb_strtolower(trim((string) ($args['email'] ?? ''))),
            'phone' => Str::limit((string) ($args['telefono'] ?? ''), 30, ''),
            'org_name' => $organizacion?->nombre,
            'organizacion_id' => $organizacion?->id,
            'stage' => in_array($etapa, CrmContact::ETAPAS, true) ? $etapa : 'nuevo',
            'source' => Str::limit((string) ($args['fuente'] ?? 'asistente'), 60, ''),
            'notes' => Str::limit((string) ($args['notas'] ?? ''), 1500, ''),
            'owner_id' => $user->id,
        ], ['api_client' => $context['api_client'] ?? null]);

        $result['links'] = $context['audience'] === 'admin'
            ? [['label' => 'Ver integraciones y CRM', 'url' => route('admin.integraciones')]]
            : [];

        return $result;
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array{user: ?User, audience: string, api_client?: mixed}  $context
     */
    private function notificarCandidato(array $args, array $context): array
    {
        $user = $context['user'] ?? null;

        if (! $user) {
            return $this->authRequired('Para enviar avisos a candidatos necesitas iniciar sesión.');
        }

        if (! in_array($context['audience'], ['organizacion', 'admin'], true)) {
            return $this->error('Solo las organizaciones y la administración pueden avisar a candidatos.');
        }

        $email = mb_strtolower(trim((string) ($args['email'] ?? '')));
        $destinatario = null;

        if ($context['audience'] === 'admin') {
            $destinatario = User::whereRaw('lower(email) = ?', [$email])->first();
        } else {
            $organizacion = Organizacion::where('user_id', $user->id)->first();

            if ($organizacion) {
                $destinatario = User::query()
                    ->whereRaw('lower(email) = ?', [$email])
                    ->whereHas('postulaciones', function ($query) use ($organizacion) {
                        $query->whereHas('oportunidad', fn ($oportunidad) => $oportunidad->where('organizacion_id', $organizacion->id));
                    })
                    ->first();
            }
        }

        if (! $destinatario) {
            return [
                'ok' => false,
                'summary' => 'No puedo avisar a esa persona porque no está vinculada a tus proyectos. Solo puedo notificar a estudiantes que se postularon a tus oportunidades.',
            ];
        }

        return $this->integrations->notify($user, [
            'title' => Str::limit((string) ($args['titulo'] ?? 'Aviso de VoluntMatch'), 120, ''),
            'body' => Str::limit((string) ($args['mensaje'] ?? ''), 1000, ''),
            'url' => route('notificaciones'),
            'level' => 'info',
            'recipient' => $destinatario,
        ], ['api_client' => $context['api_client'] ?? null]);
    }

    private function consultarMisNotificaciones(?User $user): array
    {
        if (! $user) {
            return $this->authRequired('Para revisar tus notificaciones necesitas iniciar sesión.');
        }

        $notificaciones = $user->notifications()->latest()->limit(10)->get();

        if ($notificaciones->isEmpty()) {
            return [
                'ok' => true,
                'summary' => 'No tienes notificaciones pendientes.',
                'data' => ['total' => 0, 'items' => []],
                'links' => [['label' => 'Ir a mi espacio', 'url' => route('home')]],
            ];
        }

        $items = $notificaciones->map(fn ($notificacion) => [
            'title' => (string) ($notificacion->data['titulo'] ?? 'Notificación'),
            'meta' => ((string) ($notificacion->data['cuerpo'] ?? '')).($notificacion->read_at ? ' · leída' : ' · nueva'),
            'url' => $notificacion->data['url'] ?? null,
        ])->values()->all();

        $nuevas = $notificaciones->whereNull('read_at')->count();

        return [
            'ok' => true,
            'summary' => "Tienes {$nuevas} ".(($nuevas === 1) ? 'notificación nueva' : 'notificaciones nuevas').' de las últimas '.count($items).'.',
            'data' => ['total' => count($items), 'nuevas' => $nuevas, 'items' => $items],
            'links' => [['label' => 'Ver todas mis notificaciones', 'url' => route('notificaciones')]],
        ];
    }

    private function error(string $message): array
    {
        return ['ok' => false, 'summary' => $message];
    }

    private function authRequired(string $message): array
    {
        return [
            'ok' => false,
            'summary' => $message.' Puedes iniciar sesión o crear una cuenta gratuita.',
            'links' => [
                ['label' => 'Iniciar sesión', 'url' => route('login')],
                ['label' => 'Crear cuenta', 'url' => route('registro-voluntario')],
            ],
        ];
    }

    private function limpiarLike(string $value): string
    {
        $value = trim($value);
        $value = str_replace(['%', '_', '\\'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return Str::limit($value, 120, '');
    }
}
