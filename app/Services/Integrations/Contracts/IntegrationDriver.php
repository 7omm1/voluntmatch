<?php

namespace App\Services\Integrations\Contracts;

/**
 * Contrato de un canal de integración.
 *
 * deliver() nunca recibe URLs ni credenciales desde el usuario: el driver
 * las toma de config/integrations.php. Debe devolver siempre un resumen
 * apto para mostrar a una persona.
 */
interface IntegrationDriver
{
    public function channel(): string;

    public function driver(): string;

    public function configured(): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{user?: mixed, api_client?: mixed}  $context
     * @return array{ok: bool, summary: string, data?: array<string, mixed>, target?: ?string}
     */
    public function deliver(string $action, array $payload, array $context = []): array;
}
