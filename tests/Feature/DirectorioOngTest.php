<?php

namespace Tests\Feature;

use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorioOngTest extends TestCase
{
    use RefreshDatabase;

    private function ongVerificada(array $atributos = []): Organizacion
    {
        return Organizacion::factory()->verificada()->create($atributos + [
            'nombre' => 'Qosqo Verde',
            'sector' => 'Medio ambiente',
            'ciudad' => 'Cusco · San Jerónimo',
            'mision' => 'Convertimos espacios urbanos abandonados en huertos comunitarios.',
            'latitud' => -13.5417,
            'longitud' => -71.8847,
        ]);
    }

    public function test_el_directorio_muestra_solo_ong_verificadas_y_sus_puntos(): void
    {
        $verificada = $this->ongVerificada();
        $oculta = Organizacion::factory()->create([
            'nombre' => 'Organizacion En Revision',
            'verificada' => false,
        ]);

        $this->get(route('ongs.index'))
            ->assertOk()
            ->assertSee('Qosqo Verde')
            ->assertSee('qosqo-verde')
            ->assertDontSee($oculta->nombre);

        $this->assertSame('qosqo-verde', $verificada->slug);
        $this->assertTrue($verificada->tieneUbicacion());
    }

    public function test_el_directorio_permite_buscar_por_texto_y_filtrar_por_causa(): void
    {
        $this->ongVerificada();
        Organizacion::factory()->verificada()->create([
            'nombre' => 'Aula Abierta',
            'sector' => 'Educación',
            'ciudad' => 'Lima',
        ]);

        $this->get(route('ongs.index', ['q' => 'Qosqo']))
            ->assertOk()
            ->assertSee('Qosqo Verde')
            ->assertDontSee('Aula Abierta');

        $this->get(route('ongs.index', ['sector' => 'Educación']))
            ->assertOk()
            ->assertSee('Aula Abierta')
            ->assertDontSee('Qosqo Verde');
    }

    public function test_la_ficha_publica_muestra_la_mision_y_las_oportunidades_abiertas(): void
    {
        $ong = $this->ongVerificada([
            'descripcion' => 'Somos una colectiva que recupera espacios verdes.',
            'como_participar' => 'Las brigadas se organizan por barrio.',
        ]);

        $ong->oportunidades()->create([
            'titulo' => 'Huerto comunitario',
            'causa' => 'Medio ambiente',
            'habilidad' => 'Jardinería',
            'dedicacion' => '4 h/semana',
            'modalidad' => 'Presencial',
            'resumen' => 'Acompanar el huerto del barrio.',
            'estado' => 'publicada',
        ]);

        $this->get(route('ongs.show', ['organizacion' => $ong->slug]))
            ->assertOk()
            ->assertSee('Qosqo Verde')
            ->assertSee('recupera espacios verdes')
            ->assertSee('Las brigadas se organizan por barrio')
            ->assertSee('Huerto comunitario')
            ->assertSee('Verificada');
    }

    public function test_una_ong_sin_verificar_no_es_publica_pero_su_dueno_puede_previsualizarla(): void
    {
        $user = User::factory()->create(['role' => 'organizacion']);
        $ong = Organizacion::factory()->create([
            'nombre' => 'Borrador ONG',
            'user_id' => $user->id,
            'verificada' => false,
        ]);

        $this->get(route('ongs.show', ['organizacion' => $ong->slug]))->assertNotFound();

        $this->actingAs($user)
            ->get(route('ongs.show', ['organizacion' => $ong->slug]))
            ->assertOk()
            ->assertSee('Borrador ONG')
            ->assertSee('pendiente de verificación');
    }

    public function test_la_ong_actualiza_su_ficha_publica_desde_el_panel(): void
    {
        $user = User::factory()->create(['role' => 'organizacion']);
        $ong = Organizacion::factory()->verificada()->create([
            'nombre' => 'Nombre Anterior',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('ong.perfil.update'), [
                'nombre' => 'Nuevo Nombre Comunitario',
                'sector' => 'Educación',
                'mision' => 'Acompanamos a estudiantes de zonas rurales con lectura y mentoría.',
                'descripcion' => 'Una historia de trabajo comunitario.',
                'ciudad' => 'Cusco',
                'latitud' => -13.53,
                'longitud' => -71.97,
                'anio_fundacion' => 2015,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $ong->refresh();

        $this->assertSame('Nuevo Nombre Comunitario', $ong->nombre);
        $this->assertSame('nuevo-nombre-comunitario', $ong->slug);
        $this->assertSame('Educación', $ong->sector);
        $this->assertTrue($ong->tieneUbicacion());
        $this->assertSame(2015, $ong->anio_fundacion);
    }
}
