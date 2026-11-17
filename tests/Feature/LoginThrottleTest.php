<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloquea_los_intentos_de_fuerza_bruta(): void
    {
        $user = User::factory()->create([
            'email' => 'maria@example.com',
            'password' => Hash::make('secreto123'),
            'role' => 'voluntario',
        ]);

        $intentos = function () {
            return $this->post('/logear', [
                'email' => 'maria@example.com',
                'password' => 'contraseña-errada',
            ]);
        };

        // 5 intentos fallidos permitidos...
        for ($i = 0; $i < 5; $i++) {
            $intentos()->assertRedirect();
        }

        // ...el sexto ya recibe 429 Too Many Requests.
        $intentos()->assertTooManyRequests();

        // Y la contraseña correcta sigue sin logear mientras dure el bloqueo.
        $this->post('/logear', [
            'email' => 'maria@example.com',
            'password' => 'secreto123',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }
}
