<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro auditable de un intento de integración.
 * No guarda credenciales ni el cuerpo completo de los mensajes.
 */
class IntegrationDelivery extends Model
{
    protected $table = 'integration_deliveries';

    public const CHANNELS = ['mail', 'calendar', 'crm', 'helpdesk', 'notifications'];

    protected $fillable = [
        'channel',
        'action',
        'driver',
        'status',
        'target',
        'user_id',
        'api_client_id',
        'related_type',
        'related_id',
        'payload',
        'response',
        'error',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function estadoLegible(): string
    {
        return match ($this->status) {
            'sent' => 'Entregado',
            'skipped' => 'Omitido',
            default => 'Falló',
        };
    }
}
