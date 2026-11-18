<?php

namespace App\Services\Integrations\Drivers;

use App\Services\Integrations\Contracts\IntegrationDriver;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Correo real a través del mailer de Laravel.
 * Con MAIL_MAILER=log los envíos quedan en el log (útil en local); con SMTP,
 * Resend o Postmark salen de verdad. El destinatario lo autoriza el gateway.
 */
class MailDriver implements IntegrationDriver
{
    public function channel(): string
    {
        return 'mail';
    }

    public function driver(): string
    {
        return (string) config('integrations.mail.driver', 'laravel');
    }

    public function configured(): bool
    {
        return $this->driver() !== 'disabled';
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'summary' => 'El canal de correo está desactivado.'];
        }

        $to = (string) ($payload['to'] ?? '');
        $subject = (string) ($payload['subject'] ?? 'Mensaje de VoluntMatch');
        $body = (string) ($payload['body'] ?? '');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL) || $body === '') {
            return ['ok' => false, 'summary' => 'El correo no tiene destinatario o contenido válido.', 'target' => $to ?: null];
        }

        $from = (string) config('integrations.mail.from_address', config('mail.from.address'));
        $fromName = (string) config('integrations.mail.from_name', config('mail.from.name', 'VoluntMatch'));

        try {
            Mail::raw($body, function ($message) use ($to, $subject, $from, $fromName) {
                $message->to($to)->subject($subject);
                if ($from !== '') {
                    $message->from($from, $fromName);
                }
                if (! empty($payload['reply_to'])) {
                    $message->replyTo((string) $payload['reply_to']);
                }
            });
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'summary' => 'No se pudo entregar el correo en este momento.',
                'data' => ['error' => $exception->getMessage()],
                'target' => $to,
            ];
        }

        return [
            'ok' => true,
            'summary' => "Correo enviado a {$to} con el asunto «{$subject}».",
            'data' => [
                'to' => $to,
                'subject' => $subject,
                'mailer' => (string) config('mail.default'),
            ],
            'target' => $to,
        ];
    }
}
