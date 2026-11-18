<?php

namespace App\Services\Integrations\Drivers;

use App\Notifications\PlatformNotification;
use App\Services\Integrations\Contracts\IntegrationDriver;
use App\Services\Integrations\Support\Endpoint;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Notificaciones: bandeja interna (tabla notifications) y/o webhook saliente
 * compatible con Slack, Discord, Teams o un endpoint propio.
 */
class NotificationDriver implements IntegrationDriver
{
    public function channel(): string
    {
        return 'notifications';
    }

    public function driver(): string
    {
        return (string) config('integrations.notifications.driver', 'database');
    }

    public function configured(): bool
    {
        return $this->driver() !== 'disabled';
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        $driver = $this->driver();

        if ($driver === 'disabled') {
            return ['ok' => false, 'summary' => 'El canal de notificaciones está desactivado.'];
        }

        $user = $context['user'] ?? null;
        $recipient = $payload['recipient'] ?? $user;
        $title = trim((string) ($payload['title'] ?? 'Notificación de VoluntMatch'));
        $body = trim((string) ($payload['body'] ?? ''));
        $url = $payload['url'] ?? null;

        if ($title === '' || $body === '') {
            return ['ok' => false, 'summary' => 'La notificación necesita título y contenido.'];
        }

        $sent = [];

        if (in_array($driver, ['database', 'both'], true)) {
            if (! $recipient) {
                return ['ok' => false, 'summary' => 'Para notificar dentro de la plataforma hace falta una cuenta.'];
            }

            $recipient->notify(new PlatformNotification($title, $body, is_string($url) ? $url : null, (string) ($payload['level'] ?? 'info')));

            $sent[] = 'bandeja interna';
        }

        if (in_array($driver, ['webhook', 'both'], true)) {
            $webhook = (string) config('integrations.notifications.webhook_url');

            if ($webhook === '') {
                return [
                    'ok' => $sent !== [],
                    'summary' => $sent !== []
                        ? 'Notificación guardada en la bandeja interna; el webhook no está configurado.'
                        : 'El webhook de notificaciones no está configurado.',
                ];
            }

            try {
                $response = Http::timeout((int) config('integrations.notifications.timeout', 6))
                    ->acceptJson()
                    ->post(Endpoint::assert('notifications', $webhook), [
                        'text' => $title."\n".$body,
                        'title' => $title,
                        'body' => $body,
                        'url' => $url,
                        'level' => $payload['level'] ?? 'info',
                        'source' => 'voluntmatch',
                        'user' => $recipient ? ['id' => $recipient->id, 'email' => $recipient->email] : null,
                    ]);

                if (! $response->successful()) {
                    return [
                        'ok' => $sent !== [],
                        'summary' => $sent !== []
                            ? 'Notificación guardada; el webhook respondió con error '.$response->status().'.'
                            : 'El webhook de notificaciones respondió con error '.$response->status().'.',
                    ];
                }

                $sent[] = 'webhook';
            } catch (Throwable $exception) {
                return [
                    'ok' => $sent !== [],
                    'summary' => $sent !== []
                        ? 'Notificación guardada; no se pudo contactar al webhook.'
                        : 'No se pudo contactar al webhook de notificaciones.',
                ];
            }
        }

        return [
            'ok' => $sent !== [],
            'summary' => $sent !== []
                ? 'Notificación enviada por: '.implode(' y ', $sent).'.'
                : 'No había ningún canal de notificación disponible.',
            'data' => ['channels' => $sent, 'title' => $title],
            'target' => $recipient?->email,
        ];
    }
}
