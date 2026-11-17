<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Comunicado que la administracion difunde a un segmento de la
 * comunidad (todos, voluntarios u organizaciones).
 */
class Anuncio extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'anuncios';

    public const AUDIENCIAS = ['todos', 'voluntario', 'organizacion'];

    protected $fillable = [
        'titulo',
        'cuerpo',
        'audiencia',
        'activo',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
