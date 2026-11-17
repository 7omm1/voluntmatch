<?php

namespace App\Models;

use Database\Factories\OrganizacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organizacion extends Model
{
    /** @use HasFactory<OrganizacionFactory> */
    use HasFactory;

    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'organizaciones';

    protected $fillable = [
        'user_id',
        'nombre',
        'ruc',
        'sector',
        'mision',
        'verificada',
        'verificada_por',
        'verificada_at',
        'verificacion_nota',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verificada' => 'boolean',
            'verificada_at' => 'datetime',
        ];
    }

    /** Cuenta de la ONG asociada (rol organizacion). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Administrador que aprobo la verificacion. */
    public function verificadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificada_por');
    }

    /** @param Builder<self> $query */
    public function scopePendientes($query)
    {
        return $query->where('verificada', false);
    }
}
