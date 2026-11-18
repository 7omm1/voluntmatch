<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto del CRM local. Se usa cuando INTEGRATION_CRM_DRIVER=local, que
 * es el modo que funciona sin credenciales de un proveedor externo.
 */
class CrmContact extends Model
{
    protected $table = 'crm_contacts';

    public const ETAPAS = ['nuevo', 'contactado', 'entrevista', 'voluntario', 'descartado'];

    protected $fillable = [
        'user_id',
        'organizacion_id',
        'owner_id',
        'nombre',
        'email',
        'telefono',
        'organizacion_nombre',
        'etapa',
        'fuente',
        'notas',
        'external_ref',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function etapaLegible(): string
    {
        return ucfirst($this->etapa);
    }
}
