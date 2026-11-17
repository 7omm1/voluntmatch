<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organizacion extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizacionFactory> */
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
        ];
    }

    /** Cuenta de la ONG asociada (rol organizacion). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
