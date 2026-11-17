<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_paginas_de_registro_cargan(): void
    {
        $this->get('/registro-voluntario')->assertOk()->assertSee('DNI')->assertSee('ONG');
        $this->get('/registro-organizacion')->assertOk()->assertSee('RUC')->assertSee('Estudiante');
        // Las credenciales del admin nunca deben aparecer en la interfaz.
        $login = $this->get('/login');
        $login->assertOk();
        $login->assertDontSee('REDACTED');
        $login->assertDontSee('admin@voluntmatch.org');
    }

    public function test_registro_de_estudiante_crea_rol_voluntario(): void
    {
        $respuesta = $this->post('/registro-voluntario', [
            'name' => 'María González',
            'dni' => '12345678',
            'phone' => '987654321',
            'email' => 'maria@example.com',
            'region' => 'Lima',
            'city' => 'San Juan de Lurigancho',
            'university' => 'Universidad Nacional Mayor de San Marcos',
            'career' => 'Ingeniería Ambiental',
            'cycle' => '5',
            'availability' => '6-10',
            'interests' => ['Medio ambiente', 'Educación'],
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ]);

        $respuesta->assertRedirect(route('login'));

        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame('voluntario', $user->role);
        $this->assertSame('12345678', $user->dni);
        $this->assertSame(['Medio ambiente', 'Educación'], $user->interests);

        $this->actingAs($user)->get('/home')->assertOk()->assertSee('Skills Passport');
    }

    public function test_registro_de_ong_crea_rol_organizacion(): void
    {
        $respuesta = $this->post('/registro-organizacion', [
            'name' => 'Fundación Horizonte',
            'ruc' => '20123456786',
            'org_type' => 'Fundación',
            'legal_rep' => 'Ana Pérez',
            'phone' => '987654321',
            'email' => 'contacto@horizonte.org',
            'region' => 'Cusco',
            'city' => 'Cusco',
            'address' => 'Av. Sol 123',
            'sector' => 'Educación',
            'mission' => 'Acompañamos a niños y niñas en su educación rural.',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ]);

        $respuesta->assertRedirect(route('login'));

        $user = User::where('email', 'contacto@horizonte.org')->firstOrFail();
        $this->assertSame('organizacion', $user->role);
        $this->assertSame('20123456786', $user->ruc);

        $this->actingAs($user)->get('/home')->assertRedirect(route('dashboard-organizacion'));
        $this->actingAs($user)->get('/dashboard-organizacion')->assertOk();
    }

    public function test_registro_de_ong_con_solo_los_datos_esenciales(): void
    {
        $respuesta = $this->post('/registro-organizacion', [
            'name' => 'ONG Esencial',
            'ruc' => '20123456786',
            'sector' => 'Educación',
            'region' => 'Lima',
            'phone' => '987654321',
            'email' => 'esencial@example.org',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ]);

        $respuesta->assertRedirect(route('login'));

        $user = User::where('email', 'esencial@example.org')->firstOrFail();
        $this->assertSame('organizacion', $user->role);
        $this->assertSame('Lima', $user->region);
        $this->assertNull($user->legal_rep);
    }

    public function test_el_registro_de_ong_se_puede_abrir_en_una_ventana(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-ngo-modal', false)
            ->assertSee('data-ngo-modal-open', false)
            ->assertSee('Registra tu ONG en 2 minutos');

        $this->get('/oportunidades?tab=organizations')
            ->assertOk()
            ->assertSee('data-ngo-modal-open', false);
    }

    public function test_los_errores_de_ong_van_a_su_propia_bolsa(): void
    {
        $this->post('/registro-organizacion', [
            'name' => 'ONG con RUC inválido',
            'ruc' => '20123456789',
            'sector' => 'Educación',
            'region' => 'Lima',
            'phone' => '987654321',
            'email' => 'bolsa@example.org',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
            '_modal' => 'ong',
        ])->assertSessionHasErrors(['ruc'], null, 'ong')
            ->assertSessionHasInput('_modal', 'ong');

        $this->assertSame([], session('errors')->getBag('default')->all());
    }

    public function test_valida_dni_y_ruc_peruanos(): void
    {
        $this->post('/registro-voluntario', [
            'name' => 'Prueba',
            'dni' => '123',
            'phone' => '123',
            'email' => 'prueba@example.com',
            'region' => 'Lima',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ])->assertSessionHasErrors(['dni', 'phone']);

        $this->post('/registro-organizacion', [
            'name' => 'Prueba ONG',
            'ruc' => '99123456789',
            'email' => 'ong@example.com',
            'region' => 'Lima',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ])->assertSessionHasErrors(['ruc'], null, 'ong');
    }

    public function test_un_voluntario_no_entra_al_panel_de_ong(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)->get('/dashboard-organizacion')->assertForbidden();
    }

    public function test_el_rol_enviado_en_el_formulario_se_ignora(): void
    {
        // Aunque el payload intente colar role=admin, el controlador fija
        // el rol segun la ruta usada.
        $this->post('/registro-voluntario', [
            'role' => 'admin',
            'name' => 'Intento de escalada',
            'email' => 'escalada@example.com',
            'region' => 'Lima',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'escalada@example.com')->firstOrFail();
        $this->assertSame('voluntario', $user->role);
        $this->actingAs($user)->get('/dashboard-admin')->assertForbidden();
    }

    public function test_ruc_con_digito_verificador_invalido_es_rechazado(): void
    {
        // 20123456789 cumple el formato (11 digitos, prefijo 20) pero el
        // digito verificador correcto es 6: debe ser rechazado.
        $this->post('/registro-organizacion', [
            'name' => 'RUC inventado',
            'ruc' => '20123456789',
            'email' => 'inventado@example.org',
            'region' => 'Lima',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ])->assertSessionHasErrors(['ruc'], null, 'ong');
    }
}
