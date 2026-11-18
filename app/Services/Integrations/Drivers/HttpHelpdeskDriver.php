<?php

namespace App\Services\Integrations\Drivers;

use App\Models\Ticket;
use App\Services\Integrations\Contracts\IntegrationDriver;
use App\Services\Integrations\Support\Endpoint;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Mesa de ayuda externa (Zendesk, Freshdesk, GLPI…).
 *
 * El ticket local se crea siempre para que el panel interno tenga el caso;
 * después se replica al proveedor y se guarda su identificador.
 */
class HttpHelpdeskDriver implements IntegrationDriver
{
    public function __construct(private readonly LocalHelpdeskDriver $local) {}

    public function channel(): string
    {
        return 'helpdesk';
    }

    public function driver(): string
    {
        return 'http';
    }

    public function configured(): bool
    {
        return trim((string) config('integrations.helpdesk.url')) !== '';
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        $ticketLocal = $this->local->create($payload, $context, null);

        if (! $this->configured()) {
            return $ticketLocal;
        }

        if (! ($ticketLocal['ok'] ?? false)) {
            return $ticketLocal;
        }

        try {
            $url = Endpoint::assert('helpdesk', (string) config('integrations.helpdesk.url'));

            $request = Http::timeout((int) config('integrations.helpdesk.timeout', 8))
                ->acceptJson()
                ->withHeaders(['Idempotency-Key' => 'vm-ticket-'.$ticketLocal['data']['ticket_id']]);

            if ($token = (string) config('integrations.helpdesk.token')) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, [
                'subject' => $payload['subject'] ?? null,
                'description' => $payload['message'] ?? null,
                'priority' => $payload['priority'] ?? 'media',
                'requester' => [
                    'name' => $context['user']?->name,
                    'email' => $context['user']?->email,
                    'user_id' => $context['user']?->id,
                ],
                'queue' => config('integrations.helpdesk.queue'),
                'tags' => ['voluntmatch', 'asistente'],
            ]);

            if (! $response->successful()) {
                return [
                    'ok' => true,
                    'summary' => $ticketLocal['summary'].' No se pudo replicar en la mesa de ayuda externa.',
                    'data' => ($ticketLocal['data'] ?? []) + ['external_error' => $response->status()],
                    'target' => $ticketLocal['target'] ?? null,
                ];
            }

            $data = $response->json();
            $externalId = is_array($data) ? ($data['id'] ?? $data['ticket_id'] ?? null) : null;

            if ($externalId !== null) {
                Ticket::whereKey($ticketLocal['data']['ticket_id'])
                    ->update(['external_ref' => mb_substr((string) $externalId, 0, 120)]);
            }

            return [
                'ok' => true,
                'summary' => $ticketLocal['summary'].' Sincronizado con la mesa de ayuda externa.',
                'data' => ($ticketLocal['data'] ?? []) + ['external_ref' => $externalId],
                'target' => $ticketLocal['target'] ?? null,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => true,
                'summary' => $ticketLocal['summary'].' No se pudo contactar a la mesa externa.',
                'data' => ($ticketLocal['data'] ?? []) + ['external_error' => $exception->getMessage()],
                'target' => $ticketLocal['target'] ?? null,
            ];
        }
    }
}
