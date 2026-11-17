<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crea (o actualiza) la cuenta administradora del ecosistema VoluntMatch.
 *
 * Las credenciales nunca viven en el codigo: se leen del archivo .env
 * (ADMIN_EMAIL y ADMIN_PASSWORD). Si ADMIN_PASSWORD no esta definida, se
 * genera una aleatoria y se muestra una sola vez por consola.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@voluntmatch.org');
        $password = (string) env('ADMIN_PASSWORD');
        $generada = false;

        if ($password === '') {
            $password = Str::random(20);
            $generada = true;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administración VoluntMatch',
                'password' => $password, // el cast 'hashed' la cifra automaticamente
                'role' => 'admin',
            ],
        );

        $this->command?->info("Cuenta admin lista: {$email}");

        if ($generada) {
            $this->command?->warn("Contraseña generada: {$password} (guárdala ahora, no se volverá a mostrar)");
            $this->command?->comment('Sugerencia: define ADMIN_EMAIL y ADMIN_PASSWORD en tu .env para fijarla.');
        }
    }
}
