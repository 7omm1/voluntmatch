<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulacion extends Model
{
    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'postulaciones';

    protected $fillable = [
        'user_id',
        'oportunidad_id',
        'estado',
        'nota',
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
}
