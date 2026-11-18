<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación interna de VoluntMatch. Se guarda en la tabla notifications
 * y se muestra en /notificaciones. No usa correo: el correo es un canal
 * aparte y requiere confirmación explícita.
 */
class PlatformNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $titulo,
        public readonly string $cuerpo,
        public readonly ?string $url = null,
        public readonly string $nivel = 'info',
        public readonly array $extra = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'titulo' => $this->titulo,
            'cuerpo' => $this->cuerpo,
            'url' => $this->url,
            'nivel' => $this->nivel,
        ], $this->extra);
    }
}
