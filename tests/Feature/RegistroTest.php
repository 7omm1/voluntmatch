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
        $this->get('/login')->assertOk()->assertSee('admin@voluntmatch.org');
    }

    public function test_registro_de_estudiante_crea_rol_voluntario(): void
    {
        $respuesta = $this->post('/registrar', [
            'role' => 'voluntario',
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
        $respuesta = $this->post('/registrar', [
            'role' => 'organizacion',
            'name' => 'Fundación Horizonte',
            'ruc' => '20123456789',
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
        $this->assertSame('20123456789', $user->ruc);

        $this->actingAs($user)->get('/home')->assertRedirect(route('dashboard-organizacion'));
        $this->actingAs($user)->get('/dashboard-organizacion')->assertOk();
    }

    public function test_valida_dni_y_ruc_peruanos(): void
    {
        $this->post('/registrar', [
            'role' => 'voluntario',
            'name' => 'Prueba',
            'dni' => '123',
            'phone' => '123',
            'email' => 'prueba@example.com',
            'region' => 'Lima',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ])->assertSessionHasErrors(['dni', 'phone']);

        $this->post('/registrar', [
            'role' => 'organizacion',
            'name' => 'Prueba ONG',
            'ruc' => '99123456789',
            'email' => 'ong@example.com',
            'region' => 'Lima',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'terms' => '1',
        ])->assertSessionHasErrors(['ruc']);
    }

    public function test_un_voluntario_no_entra_al_panel_de_ong(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)->get('/dashboard-organizacion')->assertForbidden();
    }
}