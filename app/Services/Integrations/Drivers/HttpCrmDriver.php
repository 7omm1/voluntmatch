<?php

namespace App\Services\Integrations\Drivers;

use App\Services\Integrations\Contracts\IntegrationDriver;
use App\Services\Integrations\Support\Endpoint;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * CRM externo (HubSpot, Pipedrive, Salesforce o un webhook propio).
 * Envía un POST JSON y guarda además un espejo local para que el panel de
 * administración pueda mostrar el contacto aunque el proveedor falle.
 */
class HttpCrmDriver implements IntegrationDriver
{
    public function __construct(private readonly LocalCrmDriver $local) {}

    public function channel(): string
    {
        return 'crm';
    }

    public function driver(): string
    {
        return 'http';
    }

    public function configured(): bool
    {
        return trim((string) config('integrations.crm.url')) !== '';
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'summary' => 'El CRM externo no está configurado.'];
        }

        try {
            $url = Endpoint::assert('crm', (string) config('integrations.crm.url'));

            $request = Http::timeout((int) config('integrations.crm.timeout', 8))
                ->acceptJson()
                ->withHeaders(['Idempotency-Key' => 'vm-crm-'.substr(hash('sha256', mb_strtolower((string) ($payload['email'] ?? ''))), 0, 24)]);

            if ($token = (string) config('integrations.crm.token')) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, [
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'company' => $payload['org_name'] ?? null,
                'stage' => $payload['stage'] ?? 'nuevo',
                'source' => $payload['source'] ?? 'voluntmatch',
                'notes' => $payload['notes'] ?? null,
                'metadata' => [
                    'user_id' => $context['user']?->id ?? null,
                    'organizacion_id' => $payload['organizacion_id'] ?? null,
                    'api_client_id' => $context['api_client']?->id ?? null,
                ],
            ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'summary' => 'El CRM externo respondió con error '.$response->status().'.',
                    'data' => ['status' => $response->status()],
                ];
            }

            $data = $response->json();
            $externalId = is_array($data) ? ($data['id'] ?? $data['contact_id'] ?? null) : null;

            $mirror = $this->local->upsert($payload, $externalId !== null ? (string) $externalId : null);

            return [
                'ok' => true,
                'summary' => ($mirror['summary'] ?? 'Contacto guardado.').' Sincronizado con el CRM externo.',
                'data' => [
                    'external_id' => $externalId,
                    'local' => $mirror['data'] ?? null,
                    'response' => is_array($data) ? $data : null,
                ],
                'target' => $payload['email'] ?? null,
            ];
        } catch (Throwable $exception) {
            // Si el proveedor falla, al menos conservamos el contacto local.
            $mirror = $this->local->upsert($payload, null);

            return [
                'ok' => false,
                'summary' => 'No pude contactar al CRM externo; el contacto quedó guardado localmente.',
                'data' => ['local' => $mirror['data'] ?? null, 'error' => $exception->getMessage()],
                'target' => $payload['email'] ?? null,
            ];
        }
    }
}
