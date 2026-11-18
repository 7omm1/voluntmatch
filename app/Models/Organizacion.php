<?php

namespace App\Models;

use Database\Factories\OrganizacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organizacion extends Model
{
    /** @use HasFactory<OrganizacionFactory> */
    use HasFactory;

    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'organizaciones';

    protected $fillable = [
        'user_id',
        'nombre',
        'slug',
        'ruc',
        'sector',
        'mision',
        'descripcion',
        'como_participar',
        'ciudad',
        'direccion',
        'latitud',
        'longitud',
        'telefono',
        'sitio_web',
        'logo_url',
        'portada_url',
        'anio_fundacion',
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
            'latitud' => 'float',
            'longitud' => 'float',
            'anio_fundacion' => 'integer',
        ];
    }

    /** La URL publica usa el slug, nunca el id secuencial. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Genera el slug al crear la ficha o al cambiar el nombre. */
    protected static function booted(): void
    {
        static::saving(function (Organizacion $organizacion) {
            if (! $organizacion->slug || ($organizacion->isDirty('nombre') && ! $organizacion->isDirty('slug'))) {
                $organizacion->slug = $organizacion->slugUnico();
            }
        });
    }

    /** Slug estable y sin colisiones para dos nombres repetidos. */
    public function slugUnico(): string
    {
        $base = Str::slug($this->nombre) ?: 'ong';
        $slug = $base;
        $sufijo = 2;

        while ($this->slugOcupado($slug)) {
            $slug = $base.'-'.$sufijo++;
        }

        return $slug;
    }

    private function slugOcupado(string $slug): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }

    /** Cuenta de la ONG asociada (rol organizacion). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Oportunidades publicadas por la ONG. */
    public function oportunidades(): HasMany
    {
        return $this->hasMany(Oportunidad::class);
    }

    /** Solo los retos visibles en el catalogo publico. */
    public function oportunidadesPublicadas(): HasMany
    {
        return $this->hasMany(Oportunidad::class)->where('estado', 'publicada');
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

    /** Fichas visibles en el directorio publico. */
    public function scopeVerificadas(Builder $query): Builder
    {
        return $query->where('verificada', true);
    }

    /** Fichas que pueden dibujarse en el mapa. */
    public function scopeConUbicacion(Builder $query): Builder
    {
        return $query->whereNotNull('latitud')->whereNotNull('longitud');
    }

    /** ¿La ONG ya tiene coordenadas para aparecer en el mapa? */
    public function tieneUbicacion(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    /** Iniciales usadas como respaldo cuando no hay logo. */
    public function iniciales(): string
    {
        return Str::of($this->nombre)
            ->explode(' ')
            ->filter()
            ->map(fn (string $palabra) => Str::upper(Str::substr($palabra, 0, 1)))
            ->take(2)
            ->implode('');
    }

    /** Enlace publico de la ficha. */
    public function urlPublica(): ?string
    {
        return $this->slug ? route('ongs.show', ['organizacion' => $this->slug]) : null;
    }
}
