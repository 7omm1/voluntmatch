<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_el_admin_con_las_credenciales_del_entorno(): void
    {
        $_ENV['ADMIN_EMAIL'] = 'root@demo.org';

        $_SERVER['ADMIN_EMAIL'] = 'root@demo.org';
        $_ENV['ADMIN_PASSWORD'] = 'clave-larga-de-demo-2026';

        $_SERVER['ADMIN_PASSWORD'] = 'clave-larga-de-demo-2026';

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'root@demo.org')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('clave-larga-de-demo-2026', $admin->password));
    }

    public function test_es_idempotente_y_actualiza_la_contrasenia(): void
    {
        $_ENV['ADMIN_EMAIL'] = 'root@demo.org';

        $_SERVER['ADMIN_EMAIL'] = 'root@demo.org';
        $_ENV['ADMIN_PASSWORD'] = 'primera-clave';

        $_SERVER['ADMIN_PASSWORD'] = 'primera-clave';

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $admin = User::where('email', 'root@demo.org')->firstOrFail();
        $this->assertTrue(Hash::check('primera-clave', $admin->password));
    }

    public function test_sin_contraseña_en_el_entorno_genera_una_aleatoria_utilizable(): void
    {
        $_ENV['ADMIN_EMAIL'] = 'root@demo.org';

        $_SERVER['ADMIN_EMAIL'] = 'root@demo.org';
        unset($_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);
        putenv('ADMIN_PASSWORD');

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'root@demo.org')->firstOrFail();
        $this->assertNotSame('', $admin->password);
        $this->assertSame('admin', $admin->role);
    }

    protected function tearDown(): void
    {
        unset($_ENV['ADMIN_EMAIL'], $_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_EMAIL'], $_SERVER['ADMIN_PASSWORD']);
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        parent::tearDown();
    }
}
