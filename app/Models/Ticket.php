<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Caso de soporte abierto por un usuario de la plataforma y atendido
 * por la administracion.
 */
class Ticket extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'tickets';

    public const PRIORIDADES = ['alta', 'media', 'baja'];

    public const ESTADOS = ['abierto', 'en_proceso', 'cerrado'];

    protected $fillable = [
        'user_id',
        'asunto',
        'mensaje',
        'prioridad',
        'estado',
        'respuesta',
        'atendido_por',
    ];

    /** Usuario que abrio el caso. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Administrador que atendio el caso. */
    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    /** @param Builder<self> $query */
    public function scopeAbiertos($query)
    {
        return $query->whereIn('estado', ['abierto', 'en_proceso']);
    }
}
