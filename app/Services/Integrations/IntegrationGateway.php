<?php

namespace App\Services\Integrations;

use App\Models\ApiClient;
use App\Models\IntegrationDelivery;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use App\Services\Integrations\Contracts\IntegrationDriver;
use App\Services\Integrations\Drivers\CalendarHttpDriver;
use App\Services\Integrations\Drivers\CalendarIcsDriver;
use App\Services\Integrations\Drivers\DisabledDriver;
use App\Services\Integrations\Drivers\HttpCrmDriver;
use App\Services\Integrations\Drivers\HttpHelpdeskDriver;
use App\Services\Integrations\Drivers\LocalCrmDriver;
use App\Services\Integrations\Drivers\LocalHelpdeskDriver;
use App\Services\Integrations\Drivers\MailDriver;
use App\Services\Integrations\Drivers\NotificationDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Puerta única a las integraciones.
 *
 * - Elige el driver según config/integrations.php.
 * - Autoriza destinatarios de correo según el rol real y sus relaciones.
 * - Aplica límites de envío por cuenta.
 * - Registra cada intento en integration_deliveries sin guardar cuerpos
 *   completos ni credenciales.
 */
class IntegrationGateway
{
    public function __construct(
        private readonly MailDriver $mail,
        private readonly CalendarIcsDriver $calendarIcs,
        private readonly CalendarHttpDriver $calendarHttp,
        private readonly LocalCrmDriver $crmLocal,
        private readonly HttpCrmDriver $crmHttp,
        private readonly LocalHelpdeskDriver $helpdeskLocal,
        private readonly HttpHelpdeskDriver $helpdeskHttp,
        private readonly NotificationDriver $notifications,
    ) {}

    public function mailDriver(): IntegrationDriver
    {
        return $this->mail;
    }

    public function calendarDriver(): IntegrationDriver
    {
        return match (config('integrations.calendar.driver')) {
            'http' => $this->calendarHttp,
            'disabled' => new DisabledDriver('calendar'),
            default => $this->calendarIcs,
        };
    }

    public function crmDriver(): IntegrationDriver
    {
        return match (config('integrations.crm.driver')) {
            'http' => $this->crmHttp,
            'disabled' => new DisabledDriver('crm'),
            default => $this->crmLocal,
        };
    }

    public function helpdeskDriver(): IntegrationDriver
    {
        return match (config('integrations.helpdesk.driver')) {
            'http' => $this->helpdeskHttp,
            'disabled' => new DisabledDriver('helpdesk'),
            default => $this->helpdeskLocal,
        };
    }

    public function notificationDriver(): IntegrationDriver
    {
        return $this->notifications;
    }

    /**
     * @return list<array{channel: string, label: string, driver: string, configured: bool, detail: string}>
     */
    public function status(): array
    {
        $definitions = [
            ['mail', 'Correo', $this->mail, 'MAIL_MAILER='.config('mail.default')],
            ['calendar', 'Calendario', $this->calendarDriver(), 'Zona '.config('integrations.calendar.timezone')],
            ['crm', 'CRM', $this->crmDriver(), 'Contactos locales + espejo externo'],
            ['helpdesk', 'Mesa de ayuda', $this->helpdeskDriver(), 'Cola '.config('integrations.helpdesk.queue')],
            ['notifications', 'Notificaciones', $this->notifications, 'Bandeja interna y/o webhook'],
        ];

        return array_map(fn (array $item) => [
            'channel' => $item[0],
            'label' => $item[1],
            'driver' => $item[2]->driver(),
            'configured' => $item[2]->configured(),
            'detail' => $item[3],
        ], $definitions);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    public function sendMail(User $actor, string $to, string $subject, string $body, array $context = []): array
    {
        $to = mb_strtolower(trim($to));
        $subject = Str::limit(trim($subject), 160, '');
        $body = Str::limit(trim($body), 6000, '');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'summary' => 'Necesito una dirección de correo válida.'];
        }

        if ($subject === '' || $body === '') {
            return ['ok' => false, 'summary' => 'El correo necesita asunto y mensaje.'];
        }

        if (! $this->canEmail($actor, $to)) {
            return [
                'ok' => false,
                'summary' => 'Por seguridad solo puedo escribir a tu propio correo o a personas vinculadas a tus proyectos. Si necesitas otro destinatario, escribe a soporte.',
                'data' => ['recipient' => $to],
            ];
        }

        $limited = $this->mailLimitReached($actor);

        if ($limited !== null) {
            return ['ok' => false, 'summary' => $limited];
        }

        $result = $this->record('mail', 'send', $this->mail, [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'reply_to' => $actor->email,
        ], ['user' => $actor] + $context);

        if ($result['ok'] ?? false) {
            $this->hitMailLimit($actor);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    public function createCalendarEvent(User $actor, array $payload, array $context = []): array
    {
        $payload['organizer'] = (string) config('integrations.mail.from_address');
        $payload['attendee'] = $payload['attendee'] ?? $actor->email;

        return $this->record('calendar', 'create_event', $this->calendarDriver(), $payload, ['user' => $actor] + $context);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    public function registerCrmContact(User $actor, array $payload, array $context = []): array
    {
        $payload['source'] = $payload['source'] ?? 'asistente';
        $payload['owner_id'] = $payload['owner_id'] ?? $actor->id;

        return $this->record('crm', 'upsert_contact', $this->crmDriver(), $payload, ['user' => $actor] + $context);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    public function openHelpdeskCase(User $actor, array $payload, array $context = []): array
    {
        $payload['user_id'] = $payload['user_id'] ?? $actor->id;
        $payload['channel'] = $payload['channel'] ?? 'asistente';

        return $this->record('helpdesk', 'open_case', $this->helpdeskDriver(), $payload, ['user' => $actor] + $context);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    public function notify(User $actor, array $payload, array $context = []): array
    {
        return $this->record('notifications', 'notify', $this->notificationDriver(), $payload, ['user' => $actor] + $context);
    }

    /**
     * ¿El usuario puede escribir a este correo?
     * - Admin: cuentas registradas y dominios permitidos.
     * - ONG: su correo y estudiantes postulados a sus oportunidades.
     * - Voluntario: su correo y ONG de oportunidades a las que postuló.
     */
    public function canEmail(User $actor, string $to): bool
    {
        $to = mb_strtolower(trim($to));
        $own = mb_strtolower((string) $actor->email);

        if ($to === $own) {
            return true;
        }

        if ($actor->role === 'admin') {
            if (User::whereRaw('lower(email) = ?', [$to])->exists()) {
                return true;
            }

            return in_array($this->domainOf($to), (array) config('integrations.mail.allowed_domains', []), true);
        }

        if ($actor->role === 'organizacion') {
            $organizacion = $actor->organizacion()->first();

            if (! $organizacion) {
                return false;
            }

            return Postulacion::query()
                ->whereHas('oportunidad', fn ($q) => $q->where('organizacion_id', $organizacion->id))
                ->whereHas('user', fn ($q) => $q->whereRaw('lower(email) = ?', [$to]))
                ->exists();
        }

        if ($actor->role === 'voluntario') {
            $ids = Postulacion::query()
                ->where('user_id', $actor->id)
                ->whereHas('oportunidad')
                ->with('oportunidad:id,organizacion_id')
                ->get()
                ->pluck('oportunidad.organizacion_id')
                ->filter()
                ->unique()
                ->all();

            if ($ids === []) {
                return false;
            }

            $orgUserIds = Organizacion::whereIn('id', $ids)->pluck('user_id')->filter()->all();

            return User::whereIn('id', $orgUserIds)->whereRaw('lower(email) = ?', [$to])->exists();
        }

        return false;
    }

    /**
     * Ejecuta el driver y registra el intento. Nunca lanza excepciones hacia
     * el asistente: siempre devuelve un resumen legible.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    private function record(string $channel, string $action, IntegrationDriver $driver, array $payload, array $context): array
    {
        $started = hrtime(true);
        $result = ['ok' => false, 'summary' => 'El canal no está disponible.'];
        $error = null;

        if (! $driver->configured()) {
            $result = ['ok' => false, 'summary' => "El canal «{$channel}» no está configurado."];
        } else {
            try {
                $result = $driver->deliver($action, $payload, $context);
            } catch (Throwable $exception) {
                $error = Str::limit($exception->getMessage(), 300);
                $result = [
                    'ok' => false,
                    'summary' => 'No pude completar la entrega en este momento. El equipo técnico puede revisarlo en el panel de integraciones.',
                ];
            }
        }

        $this->persist($channel, $action, $driver, $payload, $result, $context, $error, $started);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $context
     */
    private function persist(
        string $channel,
        string $action,
        IntegrationDriver $driver,
        array $payload,
        array $result,
        array $context,
        ?string $error,
        int $started
    ): void {
        if (! config('integrations.deliveries.store', true)) {
            return;
        }

        try {
            $user = $context['user'] ?? null;
            $client = $context['api_client'] ?? null;

            IntegrationDelivery::create([
                'channel' => $channel,
                'action' => $action,
                'driver' => $driver->driver(),
                'status' => ($result['ok'] ?? false) ? 'sent' : 'failed',
                'target' => isset($result['target']) ? Str::limit((string) $result['target'], 180, '') : null,
                'user_id' => $user?->id,
                'api_client_id' => $client instanceof ApiClient ? $client->id : null,
                'payload' => $this->sanitize($channel, $payload),
                'response' => [
                    'summary' => Str::limit((string) ($result['summary'] ?? ''), 500, '…'),
                    'data' => $this->sanitize($channel, is_array($result['data'] ?? null) ? $result['data'] : []),
                ],
                'error' => $error,
                'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            ]);
        } catch (Throwable $exception) {
            Log::warning('integrations.record_failed', [
                'channel' => $channel,
                'message' => Str::limit($exception->getMessage(), 160),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitize(string $channel, array $payload): array
    {
        $clean = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, ['token', 'password', 'authorization', 'secret'], true)) {
                continue;
            }

            if ($channel === 'mail' && $key === 'body') {
                $clean['body_chars'] = mb_strlen((string) $value);

                continue;
            }

            if (is_string($value)) {
                $clean[$key] = Str::limit($value, 400, '…');
            } elseif (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$key] = $value;
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitize($channel, $value);
            }
        }

        return array_slice($clean, 0, 20, true);
    }

    private function domainOf(string $email): string
    {
        $parts = explode('@', $email, 2);

        return mb_strtolower($parts[1] ?? '');
    }

    private function mailLimitReached(User $actor): ?string
    {
        $hour = (int) config('integrations.mail.rate.per_hour', 20);
        $day = (int) config('integrations.mail.rate.per_day', 60);

        if ($hour <= 0 && $day <= 0) {
            return null;
        }

        $hourKey = 'integrations.mail.hour.'.$actor->id.'.'.now()->format('YmdH');
        $dayKey = 'integrations.mail.day.'.$actor->id.'.'.now()->format('Ymd');

        if ($hour > 0 && (int) Cache::get($hourKey, 0) >= $hour) {
            return 'Alcanzaste el límite de correos por hora. Inténtalo más tarde o escribe desde la página de contacto.';
        }

        if ($day > 0 && (int) Cache::get($dayKey, 0) >= $day) {
            return 'Alcanzaste el límite diario de correos desde el asistente.';
        }

        return null;
    }

    private function hitMailLimit(User $actor): void
    {
        $hourKey = 'integrations.mail.hour.'.$actor->id.'.'.now()->format('YmdH');
        $dayKey = 'integrations.mail.day.'.$actor->id.'.'.now()->format('Ymd');

        Cache::add($hourKey, 0, now()->addHour());
        Cache::add($dayKey, 0, now()->addDay());

        Cache::increment($hourKey);
        Cache::increment($dayKey);
    }
}
