<?php

namespace Tests\Feature;

use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelVoluntarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estudiante_accede_a_todas_las_paginas_de_su_panel(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);
        Oportunidad::factory()->create(['estado' => 'publicada']);

        foreach ([
            'voluntario.resumen',
            'voluntario.skills',
            'voluntario.matches',
            'voluntario.postulaciones',
            'voluntario.impacto',
            'voluntario.mensajes',
        ] as $ruta) {
            $this->actingAs($user)->get(route($ruta))->assertOk();
        }
    }

    public function test_postular_desde_la_baraja_crea_la_postulacion_y_es_idempotente(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);
        $oportunidad = Oportunidad::factory()->create(['estado' => 'publicada', 'slug' => 'reto-demo']);

        $this->actingAs($user)
            ->postJson(route('voluntario.postular', ['oportunidad' => $oportunidad->slug]))
            ->assertOk()
            ->assertJson(['ok' => true, 'creada' => true]);

        $this->actingAs($user)
            ->postJson(route('voluntario.postular', ['oportunidad' => $oportunidad->slug]))
            ->assertOk()
            ->assertJson(['ok' => true, 'creada' => false]);

        $this->assertSame(1, Postulacion::where('user_id', $user->id)->count());
    }

    public function test_las_oportunidades_ya_postuladas_no_aparecen_en_la_baraja(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);
        $postulada = Oportunidad::factory()->create(['titulo' => 'Ya postulada', 'estado' => 'publicada']);
        $nueva = Oportunidad::factory()->create(['titulo' => 'Reto nuevo', 'estado' => 'publicada']);

        Postulacion::create([
            'user_id' => $user->id,
            'oportunidad_id' => $postulada->id,
            'estado' => 'en revision',
        ]);

        $this->actingAs($user)
            ->get(route('voluntario.matches'))
            ->assertOk()
            ->assertSee('Reto nuevo')
            ->assertDontSee('Ya postulada');
    }

    public function test_una_ong_no_entra_al_espacio_del_estudiante(): void
    {
        $user = User::factory()->create(['role' => 'organizacion']);
        $oportunidad = Oportunidad::factory()->create(['estado' => 'publicada']);

        $this->actingAs($user)->get(route('voluntario.resumen'))->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('voluntario.postular', ['oportunidad' => $oportunidad->slug]))
            ->assertForbidden();

        $this->assertSame(0, Postulacion::count());
    }
}
