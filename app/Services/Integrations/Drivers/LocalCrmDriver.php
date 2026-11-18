<?php

namespace App\Services\Integrations\Drivers;

use App\Models\CrmContact;
use App\Services\Integrations\Contracts\IntegrationDriver;
use Throwable;

/**
 * CRM local: contactos reales en la tabla crm_contacts.
 * Se puede consultar desde /admin/integraciones y se reutiliza como espejo
 * cuando el CRM es externo.
 */
class LocalCrmDriver implements IntegrationDriver
{
    public function channel(): string
    {
        return 'crm';
    }

    public function driver(): string
    {
        return 'local';
    }

    public function configured(): bool
    {
        return true;
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        return $this->upsert($payload, null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(array $payload, ?string $externalRef): array
    {
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'summary' => 'El contacto necesita un correo válido.'];
        }

        if (trim((string) ($payload['name'] ?? '')) === '') {
            return ['ok' => false, 'summary' => 'El contacto necesita un nombre.'];
        }

        try {
            $contact = CrmContact::updateOrCreate(
                [
                    'owner_id' => $payload['owner_id'] ?? null,
                    'email' => $email,
                ],
                [
                    'user_id' => $payload['user_id'] ?? null,
                    'organizacion_id' => $payload['organizacion_id'] ?? null,
                    'nombre' => mb_substr(trim((string) $payload['name']), 0, 160),
                    'email' => $email,
                    'telefono' => $payload['phone'] ?? null,
                    'organizacion_nombre' => $payload['org_name'] ?? null,
                    'etapa' => in_array($payload['stage'] ?? '', CrmContact::ETAPAS, true) ? $payload['stage'] : 'nuevo',
                    'fuente' => mb_substr((string) ($payload['source'] ?? 'asistente'), 0, 60),
                    'notas' => $payload['notes'] ?? null,
                    'external_ref' => $externalRef,
                ]
            );

            return [
                'ok' => true,
                'summary' => sprintf(
                    'Contacto %s registrado en el CRM en la etapa «%s».',
                    $contact->nombre,
                    $contact->etapa
                ),
                'data' => [
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'etapa' => $contact->etapa,
                    'external_ref' => $externalRef,
                ],
                'target' => $contact->email,
            ];
        } catch (Throwable $exception) {
            return ['ok' => false, 'summary' => 'No pude guardar el contacto en el CRM.'];
        }
    }
}
