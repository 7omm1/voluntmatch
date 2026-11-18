<?php

namespace App\Console\Commands;

use App\Models\IntegrationDelivery;
use App\Models\User;
use App\Services\Integrations\IntegrationGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Prueba un canal de integración contra la configuración actual.
 * Sirve para validar credenciales antes de desplegar.
 */
class IntegrationsTestCommand extends Command
{
    protected $signature = 'integrations:test
        {channel : mail|calendar|crm|helpdesk|notifications}
        {--email= : Cuenta administradora para la prueba}';

    protected $description = 'Ejecuta una entrega de prueba por el canal indicado';

    public function handle(IntegrationGateway $gateway): int
    {
        $channel = (string) $this->argument('channel');

        if (! in_array($channel, IntegrationDelivery::CHANNELS, true)) {
            $this->error('Canal no válido. Usa: '.implode(', ', IntegrationDelivery::CHANNELS));

            return self::FAILURE;
        }

        $email = (string) ($this->option('email') ?: config('mail.from.address'));
        $admin = $email !== '' ? User::where('email', $email)->first() : null;
        $admin ??= User::where('role', 'admin')->first();

        if (! $admin) {
            $this->error('No hay una cuenta para ejecutar la prueba. Crea el admin o pasa --email.');

            return self::FAILURE;
        }

        $result = match ($channel) {
            'mail' => $gateway->sendMail($admin, (string) $admin->email, 'Prueba de correo de VoluntMatch', 'Entrega de prueba desde la consola.'),
            'calendar' => $gateway->createCalendarEvent($admin, [
                'title' => 'Prueba de calendario',
                'description' => 'Evento de prueba desde la consola.',
                'start' => Carbon::now((string) config('integrations.calendar.timezone', 'America/Lima'))->addDay()->setTime(10, 0)->toIso8601String(),
                'duration_minutes' => 30,
            ]),
            'crm' => $gateway->registerCrmContact($admin, [
                'name' => 'Contacto de prueba',
                'email' => (string) $admin->email,
                'stage' => 'nuevo',
                'source' => 'consola',
                'owner_id' => $admin->id,
            ]),
            'helpdesk' => $gateway->openHelpdeskCase($admin, [
                'subject' => 'Prueba de mesa de ayuda',
                'message' => 'Caso de prueba desde la consola.',
                'priority' => 'baja',
                'channel' => 'consola',
            ]),
            'notifications' => $gateway->notify($admin, [
                'title' => 'Prueba de notificaciones',
                'body' => 'Aviso de prueba desde la consola.',
                'level' => 'info',
            ]),
        };

        if ($result['ok'] ?? false) {
            $this->info((string) $result['summary']);
        } else {
            $this->error((string) ($result['summary'] ?? 'La entrega falló.'));
        }

        if (! empty($result['data']['invitation_url'])) {
            $this->line('Invitación: '.$result['data']['invitation_url']);
        }

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
