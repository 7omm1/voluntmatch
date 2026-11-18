<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Token de un consumidor de la API o de un agente MCP.
 *
 * El valor en claro solo existe una vez, al generarlo. En la base se
 * guarda su hash y un prefijo para reconocerlo en el panel.
 */
class ApiClient extends Model
{
    protected $table = 'api_clients';

    protected $fillable = [
        'name',
        'key_prefix',
        'key_hash',
        'role',
        'user_id',
        'scopes',
        'active',
        'last_used_at',
        'last_ip',
        'expires_at',
        'revoked_at',
        'created_by',
    ];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public const ROLES = ['public', 'voluntario', 'organizacion', 'admin'];

    public const SCOPES = ['assistant:chat', 'assistant:tools', 'assistant:confirm', 'mcp:use'];

    /**
     * Genera un cliente y devuelve el token en claro junto al modelo.
     *
     * @param  list<string>|null  $scopes
     * @return array{0: self, 1: string}
     */
    public static function issue(
        string $name,
        string $role,
        ?User $user = null,
        ?array $scopes = null,
        ?int $createdBy = null,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $role = in_array($role, self::ROLES, true) ? $role : 'public';
        $scopes = array_values(array_intersect($scopes ?? self::SCOPES, self::SCOPES));

        if ($scopes === []) {
            $scopes = ['assistant:chat'];
        }

        $plain = (string) config('api.token_prefix', 'vm_').Str::random(48);
        $prefix = substr($plain, 0, 12);

        $client = static::create([
            'name' => Str::limit(trim($name) !== '' ? trim($name) : 'Cliente API', 120, ''),
            'key_prefix' => $prefix,
            'key_hash' => hash('sha256', $plain),
            'role' => $role,
            'user_id' => $role === 'public' ? null : $user?->id,
            'scopes' => $scopes,
            'active' => true,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);

        return [$client, $plain];
    }

    /** Resuelve un token en claro sin comparaciones de tiempo vulnerables. */
    public static function findFromToken(string $plain): ?self
    {
        $prefix = (string) config('api.token_prefix', 'vm_');

        if (! str_starts_with($plain, $prefix) || strlen($plain) < 24) {
            return null;
        }

        $client = static::query()
            ->where('key_hash', hash('sha256', $plain))
            ->where('active', true)
            ->whereNull('revoked_at')
            ->first();

        if (! $client) {
            return null;
        }

        if ($client->expires_at !== null && $client->expires_at->isPast()) {
            return null;
        }

        return $client;
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, (array) $this->scopes, true);
    }

    /** @param list<string> $scopes */
    public function allowsAll(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (! $this->allows($scope)) {
                return false;
            }
        }

        return true;
    }

    public function audience(): string
    {
        return in_array($this->role, self::ROLES, true) ? $this->role : 'public';
    }

    public function estaVigente(): bool
    {
        return $this->active
            && $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<self> $query */
    public function scopeVigentes($query)
    {
        return $query->where('active', true)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
