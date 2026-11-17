<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role',
    'phone', 'region', 'city',
    'dni', 'university', 'career', 'cycle', 'availability', 'interests',
    'ruc', 'org_type', 'legal_rep', 'address', 'website', 'sector', 'mission',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'interests' => 'array',
            'skills' => 'array',
        ];
    }

    /** La cuenta esta suspendida por la administracion. */
    public function estaBloqueado(): bool
    {
        return $this->blocked_at !== null;
    }

    /** Ficha de organizacion asociada (solo rol organizacion). */
    public function organizacion(): HasOne
    {
        return $this->hasOne(Organizacion::class);
    }

    /** Postulaciones a oportunidades (solo rol voluntario). */
    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    /** Etiqueta legible del rol para las tablas del panel. */
    public function rolLegible(): string
    {
        return match ($this->role) {
            'admin' => 'Administrador',
            'organizacion' => 'Organización',
            default => 'Voluntario',
        };
    }
}
