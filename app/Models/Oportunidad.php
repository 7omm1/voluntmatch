<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oportunidad extends Model
{
    /** @use HasFactory<\Database\Factories\OportunidadFactory> */
    use HasFactory;

    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'oportunidades';

    protected $fillable = [
        'organizacion_id',
        'titulo',
        'causa',
        'habilidad',
        'dedicacion',
        'modalidad',
        'afinidad',
        'imagen',
        'resumen',
        'aprenderas',
        'porque',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'porque' => 'array',
            'afinidad' => 'integer',
        ];
    }

    /** Organizacion (ONG) que publica el reto. */
    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    /** Voluntarios que se postularon al reto. */
    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }
}
