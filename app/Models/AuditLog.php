<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitacora de las acciones administrativas. Sirve para auditar quien
 * cambio que y cuando, sin depender de la base de datos.
 */
class AuditLog extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'accion',
        'entidad',
        'entidad_id',
        'detalle',
        'ip',
    ];

    /** Administrador que ejecuto la accion. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra una accion del administrador autenticado.
     * Se llama desde los controladores despues de cada cambio real.
     */
    public static function registrar(string $accion, ?Model $entidad = null, ?string $detalle = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad ? class_basename($entidad) : null,
            'entidad_id' => $entidad?->getKey(),
            'detalle' => $detalle,
            'ip' => request()->ip(),
        ]);
    }
}
