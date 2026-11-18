<?php

namespace App\Services\Integrations\Support;

use RuntimeException;

/**
 * Validación de endpoints configurados.
 * En producción solo se aceptan URLs HTTPS; en local se permite HTTP para
 * poder probar contra servicios de desarrollo.
 */
final class Endpoint
{
    public static function assert(string $channel, ?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("El canal «{$channel}» no tiene una URL válida configurada.");
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $local = app()->environment(['local', 'testing']);

        if ($scheme !== 'https' && ! $local) {
            throw new RuntimeException("El canal «{$channel}» debe usar HTTPS en este entorno.");
        }

        return $url;
    }
}
