<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reporte o denuncia de moderacion: puede apuntar a una cuenta y/o a
 * una oportunidad. Lo revisa la administracion desde el panel.
 */
class Reporte extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'reportes';

    public const ESTADOS = ['pendiente', 'resuelto', 'descartado'];

    protected $fillable = [
        'user_id',
        'oportunidad_id',
        'reportado_por',
        'motivo',
        'detalle',
        'estado',
        'resolucion',
        'resuelto_por',
    ];

    /** Cuenta reportada. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Oportunidad reportada. */
    public function oportunidad(): BelongsTo
    {
        return $this->belongsTo(Oportunidad::class);
    }

    /** Quien levanto el reporte (puede ser anonimo). */
    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }

    /** Administrador que resolvio o descarto el reporte. */
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    /** @param Builder<self> $query */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
}
