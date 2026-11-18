<?php

namespace App\Support;

use App\Models\Organizacion;
use Illuminate\Support\Collection;

/**
 * Datos compartidos por el mapa publico de ONG. Mantener aqui la
 * serializacion evita que la portada y el directorio se desincronicen.
 */
class MapaOngs
{
    /** Centro del Cusco usado por el mapa interactivo. */
    public const CUSCO = ['lat' => -13.53195, 'lng' => -71.96746];

    /**
     * @param  Collection<int, Organizacion>|iterable<Organizacion>  $organizaciones
     * @return list<array<string, mixed>>
     */
    public static function marcadores(iterable $organizaciones): array
    {
        return collect($organizaciones)
            ->filter(fn (Organizacion $organizacion) => $organizacion->tieneUbicacion())
            ->map(fn (Organizacion $organizacion) => [
                'id' => $organizacion->id,
                'nombre' => $organizacion->nombre,
                'sector' => $organizacion->sector,
                'ciudad' => $organizacion->ciudad,
                'logo' => $organizacion->logo_url,
                'iniciales' => $organizacion->iniciales(),
                'lat' => $organizacion->latitud,
                'lng' => $organizacion->longitud,
                'oportunidades' => (int) ($organizacion->oportunidades_count ?? 0),
                'url' => $organizacion->urlPublica(),
            ])
            ->values()
            ->all();
    }
}
