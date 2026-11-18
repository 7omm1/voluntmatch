<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Emite un token de API/MCP desde la consola. Útil para CI, integraciones
 * internas o para el primer arranque sin entrar al panel.
 */
class ApiTokenCommand extends Command
{
    protected $signature = 'api:token
        {name : Nombre del consumidor}
        {--role=public : public|voluntario|organizacion|admin}
        {--email= : Cuenta asociada (obligatoria si el rol no es público)}
        {--scopes=* : Scopes; por defecto todos}
        {--days= : Caducidad en días}';

    protected $description = 'Genera un token de API/MCP para un sistema externo';

    public function handle(): int
    {
        $role = (string) $this->option('role');

        if (! in_array($role, ApiClient::ROLES, true)) {
            $this->error('Rol no válido. Usa: '.implode(', ', ApiClient::ROLES));

            return self::FAILURE;
        }

        $user = null;

        if ($role !== 'public') {
            $email = (string) $this->option('email');

            if ($email === '') {
                $this->error('Un rol privado necesita --email de una cuenta existente.');

                return self::FAILURE;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error('No existe una cuenta con ese correo.');

                return self::FAILURE;
            }

            if ($user->role !== $role) {
                $this->error("La cuenta tiene rol «{$user->role}», no «{$role}».");

                return self::FAILURE;
            }
        }

        $scopes = $this->option('scopes');

        if ($scopes === [] || $scopes === null) {
            $scopes = ApiClient::SCOPES;
        }

        $days = $this->option('days');

        [$client, $plain] = ApiClient::issue(
            (string) $this->argument('name'),
            $role,
            $user,
            $scopes,
            null,
            $days !== null ? now()->addDays(max(1, (int) $days)) : null
        );

        $this->info('Token creado. Guárdalo: no se volverá a mostrar.');
        $this->line('');
        $this->line('  <comment>'.$plain.'</comment>');
        $this->line('');
        $this->line('  Cliente: '.$client->name.' (#'.$client->id.')');
        $this->line('  Rol: '.$client->role.' · Scopes: '.implode(', ', $client->scopes));

        return self::SUCCESS;
    }
}
