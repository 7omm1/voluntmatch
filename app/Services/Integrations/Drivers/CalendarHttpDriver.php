<?php

namespace App\Services\Integrations\Drivers;

use App\Services\Integrations\Contracts\IntegrationDriver;
use App\Services\Integrations\Support\Endpoint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Publica el evento en un calendario externo mediante HTTP.
 * El contrato esperado es un POST JSON con los campos del evento; el
 * proveedor debe responder 2xx y, si puede, un identificador externo.
 */
class CalendarHttpDriver implements IntegrationDriver
{
    public function channel(): string
    {
        return 'calendar';
    }

    public function driver(): string
    {
        return 'http';
    }

    public function configured(): bool
    {
        return trim((string) config('integrations.calendar.url')) !== '';
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'summary' => 'El calendario externo no está configurado.'];
        }

        try {
            $url = Endpoint::assert('calendar', (string) config('integrations.calendar.url'));
            $timezone = (string) config('integrations.calendar.timezone', 'America/Lima');
            $inicio = Carbon::parse((string) ($payload['start'] ?? 'now'), $timezone);
            $fin = $inicio->copy()->addMinutes(max(5, (int) ($payload['duration_minutes'] ?? 60)));

            $body = [
                'summary' => (string) ($payload['title'] ?? 'Evento VoluntMatch'),
                'description' => (string) ($payload['description'] ?? ''),
                'start' => $inicio->toIso8601String(),
                'end' => $fin->toIso8601String(),
                'timezone' => $timezone,
                'attendee' => $payload['attendee'] ?? null,
                'source' => 'voluntmatch',
                'metadata' => [
                    'user_id' => $context['user']?->id ?? null,
                    'api_client_id' => $context['api_client']?->id ?? null,
                ],
            ];

            $request = Http::timeout((int) config('integrations.calendar.timeout', 8))
                ->acceptJson()
                ->withHeaders(['Idempotency-Key' => 'vm-cal-'.substr(hash('sha256', json_encode($body)), 0, 24)]);

            if ($token = (string) config('integrations.calendar.token')) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, $body);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'summary' => 'El calendario externo respondió con error '.$response->status().'.',
                    'data' => ['status' => $response->status()],
                ];
            }

            $data = $response->json();
            $externalId = is_array($data) ? ($data['id'] ?? $data['event_id'] ?? null) : null;

            return [
                'ok' => true,
                'summary' => sprintf('Evento «%s» creado en el calendario externo.', (string) ($payload['title'] ?? '')),
                'data' => [
                    'external_id' => $externalId,
                    'start' => $inicio->toIso8601String(),
                    'end' => $fin->toIso8601String(),
                    'response' => is_array($data) ? $data : null,
                ],
                'target' => $payload['attendee'] ?? null,
            ];
        } catch (Throwable $exception) {
            return ['ok' => false, 'summary' => 'No pude contactar al calendario externo.', 'data' => ['error' => $exception->getMessage()]];
        }
    }
}
