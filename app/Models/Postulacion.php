<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulacion extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'postulaciones';

    public const ESTADOS = ['en revision', 'en entrevista', 'aceptado', 'rechazado'];

    protected $fillable = [
        'user_id',
        'oportunidad_id',
        'estado',
        'nota',
        'nota_interna',
    ];

    /** Estado inicial mientras la ONG no lo revisa. */
    protected $attributes = [
        'estado' => 'en revision',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nota' => 'string',
        ];
    }

    /** Voluntario que postulo. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Oportunidad a la que postulo. */
    public function oportunidad(): BelongsTo
    {
        return $this->belongsTo(Oportunidad::class);
    }

    /** Etapa del Match Inbox de la ONG. */
    public function etapa(): string
    {
        return match ($this->estado) {
            'en entrevista' => 'entrevista',
            'aceptado' => 'aceptado',
            'rechazado' => 'rechazado',
            default => 'por-revisar',
        };
    }

    /** Etiqueta legible del estado para el estudiante. */
    public function estadoLegible(): string
    {
        return match ($this->estado) {
            'en entrevista' => 'En entrevista',
            'aceptado' => 'Aceptado',
            'rechazado' => 'Rechazado',
            default => 'En revisión',
        };
    }

    /** Clase CSS del badge de estado. */
    public function estadoClase(): string
    {
        return match ($this->estado) {
            'en entrevista' => 'en-entrevista',
            'aceptado' => 'aceptado',
            'rechazado' => 'rechazada',
            default => 'en-revisión',
        };
    }
}
