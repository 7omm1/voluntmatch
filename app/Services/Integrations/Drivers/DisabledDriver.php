<?php

namespace App\Services\Integrations\Drivers;

use App\Services\Integrations\Contracts\IntegrationDriver;

/**
 * Driver explícito para canales apagados por configuración. Devuelve un
 * error legible en lugar de fingir una entrega correcta.
 */
class DisabledDriver implements IntegrationDriver
{
    public function __construct(private readonly string $channel) {}

    public function channel(): string
    {
        return $this->channel;
    }

    public function driver(): string
    {
        return 'disabled';
    }

    public function configured(): bool
    {
        return false;
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        return [
            'ok' => false,
            'summary' => "El canal «{$this->channel}» está desactivado por configuración.",
        ];
    }
}
