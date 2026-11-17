<?php

namespace App\Models;

use App\Support\OportunidadesDemo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Oportunidad extends Model
{
    /** @use HasFactory<\Database\Factories\OportunidadFactory> */
    use HasFactory;

    /** Laravel pluraliza en ingles; esta tabla vive en espanol. */
    protected $table = 'oportunidades';

    protected $fillable = [
        'organizacion_id',
        'slug',
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

    /** La URL publica usa el slug, nunca el id secuencial. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Genera el slug al crear o al cambiar el titulo. */
    protected static function booted(): void
    {
        static::saving(function (Oportunidad $oportunidad) {
            if (! $oportunidad->slug || ($oportunidad->isDirty('titulo') && ! $oportunidad->isDirty('slug'))) {
                $oportunidad->slug = $oportunidad->slugUnico();
            }
        });
    }

    /** Slug estable y sin colisiones para dos titulos repetidos. */
    public function slugUnico(): string
    {
        $base = Str::slug($this->titulo) ?: 'oportunidad';
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

    /**
     * Resuelve la ficha por slug. Si la base aun no esta sembrada, cae
     * al catalogo de demostracion para que los enlaces sigan funcionando.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $encontrada = $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();

        if ($encontrada !== null) {
            return $encontrada;
        }

        $demo = collect(OportunidadesDemo::todas())->firstWhere('slug', $value);

        return $demo !== null ? static::desdeDemo($demo) : null;
    }

    /** @param array<string, mixed> $demo */
    public static function desdeDemo(array $demo): self
    {
        $oportunidad = new static([
            'slug' => $demo['slug'],
            'titulo' => $demo['title'],
            'causa' => $demo['cause'],
            'habilidad' => $demo['skill'],
            'dedicacion' => $demo['time'],
            'modalidad' => $demo['modalidad'],
            'afinidad' => $demo['match'],
            'imagen' => $demo['image'],
            'resumen' => $demo['summary'],
            'aprenderas' => $demo['aprenderas'],
            'porque' => $demo['porque'],
        ]);

        $oportunidad->id = (int) $demo['id'];
        $oportunidad->exists = false;
        $oportunidad->setRelation('organizacion', new Organizacion([
            'nombre' => $demo['org'],
            'verificada' => true,
        ]));

        return $oportunidad;
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
