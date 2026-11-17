<?php

namespace Tests\Feature;

use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelOngTest extends TestCase
{
    use RefreshDatabase;

    private function organizacionConCuenta(): array
    {
        $user = User::factory()->create(['role' => 'organizacion']);
        $organizacion = Organizacion::factory()->verificada()->create(['user_id' => $user->id]);

        return [$user, $organizacion];
    }

    public function test_la_ong_accede_a_todas_las_paginas_de_su_panel(): void
    {
        [$user, $organizacion] = $this->organizacionConCuenta();
        Oportunidad::factory()->create([
            'organizacion_id' => $organizacion->id,
            'estado' => 'publicada',
        ]);

        foreach ([
            'ong.resumen',
            'ong.oportunidades',
            'ong.talento',
            'ong.candidatos',
            'ong.metricas',
            'ong.mensajes',
        ] as $ruta) {
            $this->actingAs($user)->get(route($ruta))->assertOk();
        }
    }

    public function test_la_ong_acepta_y_rechaza_candidatos_con_persistencia_real(): void
    {
        [$user, $organizacion] = $this->organizacionConCuenta();
        $oportunidad = Oportunidad::factory()->create([
            'organizacion_id' => $organizacion->id,
            'estado' => 'publicada',
        ]);
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        $postulacion = Postulacion::create([
            'user_id' => $voluntario->id,
            'oportunidad_id' => $oportunidad->id,
            'estado' => 'en revision',
        ]);

        $this->actingAs($user)
            ->patchJson(route('ong.candidatos.actualizar', $postulacion), ['estado' => 'aceptado'])
            ->assertOk()
            ->assertJson(['ok' => true, 'etapa' => 'aceptado']);

        $this->assertSame('aceptado', $postulacion->fresh()->estado);

        $this->actingAs($user)
            ->patchJson(route('ong.candidatos.actualizar', $postulacion), ['nota_interna' => 'Gran portafolio.'])
            ->assertOk();

        $this->assertSame('Gran portafolio.', $postulacion->fresh()->nota_interna);
    }

    public function test_otra_ong_no_puede_modificar_una_postulacion_ajena(): void
    {
        [, $organizacion] = $this->organizacionConCuenta();
        [$intruso] = $this->organizacionConCuenta();

        $oportunidad = Oportunidad::factory()->create([
            'organizacion_id' => $organizacion->id,
            'estado' => 'publicada',
        ]);
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        $postulacion = Postulacion::create([
            'user_id' => $voluntario->id,
            'oportunidad_id' => $oportunidad->id,
            'estado' => 'en revision',
        ]);

        $this->actingAs($intruso)
            ->patchJson(route('ong.candidatos.actualizar', $postulacion), ['estado' => 'aceptado'])
            ->assertForbidden();

        $this->assertSame('en revision', $postulacion->fresh()->estado);
    }

    public function test_la_ong_publica_una_oportunidad_real(): void
    {
        [$user, $organizacion] = $this->organizacionConCuenta();

        $this->actingAs($user)
            ->post(route('ong.oportunidades.store'), [
                'titulo' => 'Nueva campaña de salud',
                'causa' => 'Salud',
                'habilidad' => 'Comunicación',
                'dedicacion' => '3 h/semana',
                'modalidad' => 'Remoto',
                'resumen' => 'Difundir hábitos saludables en la comunidad.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('oportunidades', [
            'organizacion_id' => $organizacion->id,
            'titulo' => 'Nueva campaña de salud',
            'estado' => 'publicada',
        ]);
    }

    public function test_un_estudiante_no_entra_al_panel_de_la_ong(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)->get(route('ong.resumen'))->assertForbidden();
    }
}
