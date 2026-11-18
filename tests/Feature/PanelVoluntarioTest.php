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

    public function test_el_skills_passport_tiene_mapa_en_vivo_y_suma_al_perfil(): void
    {
        $user = User::factory()->create([
            'role' => 'voluntario',
            'skills' => [
                'Comunicación' => 90,
                'Creatividad' => 80,
                'Liderazgo' => 70,
                'Análisis' => 60,
                'Trabajo en equipo' => 85,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('voluntario.skills'))
            ->assertOk()
            ->assertSee('data-radar-shape', false)
            ->assertSee('data-skill-range', false)
            ->assertSee('data-profile-base', false)
            ->assertSee('data-profile-total', false)
            ->assertSee('En vivo');
    }

    public function test_las_postulaciones_se_abren_en_una_ventana_con_completar_perfil(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);
        $oportunidad = Oportunidad::factory()->create([
            'titulo' => 'Reto para ventana',
            'estado' => 'publicada',
        ]);

        Postulacion::create([
            'user_id' => $user->id,
            'oportunidad_id' => $oportunidad->id,
            'estado' => 'aceptado',
            'nota' => 'Quiero aportar con mi talento.',
        ]);

        $this->actingAs($user)
            ->get(route('voluntario.postulaciones'))
            ->assertOk()
            ->assertSee('data-application-open', false)
            ->assertSee('data-application-modal', false)
            ->assertSee('data-profile-modal', false)
            ->assertSee('Completar perfil');
    }

    public function test_el_panel_de_matches_explica_por_que_encaja_cada_carta(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);
        Oportunidad::factory()->create([
            'titulo' => 'Reto con razones',
            'estado' => 'publicada',
            'porque' => ['Tu habilidad encaja.', 'Tu horario es compatible.', 'La causa te importa.'],
        ]);

        $this->actingAs($user)
            ->get(route('voluntario.matches'))
            ->assertOk()
            ->assertSee('POR QUÉ ENCAJA CONTIGO')
            ->assertSee('data-reasons', false)
            ->assertSee('data-match-side', false)
            ->assertSee('data-match-open', false);
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

    public function test_el_voluntario_puede_abrir_un_caso_de_soporte(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)
            ->postJson(route('soporte.store'), [
                'asunto' => 'No puedo enviar mi postulación',
                'mensaje' => 'Al pulsar el botón la pantalla se queda cargando.',
                'prioridad' => 'alta',
            ])
            ->assertCreated()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'asunto' => 'No puedo enviar mi postulación',
            'estado' => 'abierto',
            'prioridad' => 'alta',
        ]);
    }

    public function test_el_caso_de_soporte_necesita_asunto_y_mensaje(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)
            ->postJson(route('soporte.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['asunto', 'mensaje']);
    }
}
