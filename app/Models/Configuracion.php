<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuracion persistente de la plataforma (pesos del algoritmo,
 * reglas de moderacion y avisos). Se guarda como clave/valor JSON.
 */
class Configuracion extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor'];

    protected function casts(): array
    {
        return [
            'valor' => 'array',
        ];
    }

    /** Lee un valor con respaldo cuando todavia no existe. */
    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        $registro = static::query()->where('clave', $clave)->first();

        return $registro?->valor ?? $porDefecto;
    }

    /** Crea o actualiza una clave de configuracion. */
    public static function guardar(string $clave, mixed $valor): self
    {
        return static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
