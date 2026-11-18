<?php

namespace App\Services\Integrations\Drivers;

use App\Models\Ticket;
use App\Services\Integrations\Contracts\IntegrationDriver;
use Illuminate\Support\Str;

/**
 * Mesa de ayuda interna: abre un ticket real con canal y prioridad.
 * Es el driver por defecto y no necesita configuración.
 */
class LocalHelpdeskDriver implements IntegrationDriver
{
    public function channel(): string
    {
        return 'helpdesk';
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
        return $this->create($payload, $context, null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{user?: mixed, api_client?: mixed}  $context
     */
    public function create(array $payload, array $context, ?string $externalRef): array
    {
        $subject = Str::limit(trim((string) ($payload['subject'] ?? 'Solicitud de soporte')), 160, '');
        $message = Str::limit(trim((string) ($payload['message'] ?? '')), 3000, '');

        if ($message === '') {
            return ['ok' => false, 'summary' => 'El caso de soporte necesita una descripción.'];
        }

        $ticket = Ticket::create([
            'user_id' => $payload['user_id'] ?? $context['user']?->id,
            'asunto' => $subject,
            'mensaje' => $message,
            'prioridad' => in_array($payload['priority'] ?? 'media', Ticket::PRIORIDADES, true)
                ? $payload['priority']
                : 'media',
            'estado' => 'abierto',
            'canal' => Str::limit((string) ($payload['channel'] ?? 'asistente'), 30, ''),
            'external_ref' => $externalRef,
        ]);

        return [
            'ok' => true,
            'summary' => "Abrí el caso #{$ticket->id} con prioridad {$ticket->prioridad}. El equipo de soporte lo revisará y te responderá en menos de 24 horas hábiles.",
            'data' => [
                'ticket_id' => $ticket->id,
                'estado' => $ticket->estado,
                'prioridad' => $ticket->prioridad,
                'canal' => $ticket->canal,
            ],
            'target' => 'ticket:'.$ticket->id,
        ];
    }
}
