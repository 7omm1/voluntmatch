<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_voluntario_no_accede_a_ningun_panel_privilegiado(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)->get('/dashboard-admin')->assertForbidden();
        $this->actingAs($user)->get('/dashboard-organizacion')->assertForbidden();
        $this->actingAs($user)->get('/home')->assertOk(); // su propio panel si
    }

    public function test_una_ong_accede_a_su_panel_pero_no_al_de_admin(): void
    {
        $user = User::factory()->create(['role' => 'organizacion']);

        $this->actingAs($user)->get('/dashboard-organizacion')->assertOk();
        $this->actingAs($user)->get('/dashboard-admin')->assertForbidden();
    }

    public function test_el_admin_accede_a_su_panel_pero_no_al_de_ong(): void
    {
        $_ENV['ADMIN_EMAIL'] = 'root@demo.org';

        $_SERVER['ADMIN_EMAIL'] = 'root@demo.org';
        $_ENV['ADMIN_PASSWORD'] = 'clave-larga-de-demo-2026';

        $_SERVER['ADMIN_PASSWORD'] = 'clave-larga-de-demo-2026';
        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'root@demo.org')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard-admin')->assertOk();
        $this->actingAs($admin)->get('/dashboard-organizacion')->assertForbidden();
        $this->actingAs($admin)->get('/home')->assertRedirect(route('dashboard-admin'));
    }

    public function test_invitados_son_redirigidos_al_login(): void
    {
        $this->get('/dashboard-admin')->assertRedirect(route('login'));
        $this->get('/dashboard-organizacion')->assertRedirect(route('login'));
    }

    protected function tearDown(): void
    {
        unset($_ENV['ADMIN_EMAIL'], $_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_EMAIL'], $_SERVER['ADMIN_PASSWORD']);
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        parent::tearDown();
    }
}
