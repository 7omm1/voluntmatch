<?php

namespace Tests\Feature;

use App\Models\Oportunidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetalleOportunidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ficha_publica_se_resuelve_por_slug(): void
    {
        $oportunidad = Oportunidad::factory()->create(['titulo' => 'Rediseño web accesible']);

        $this->assertSame('rediseno-web-accesible', $oportunidad->slug);

        $this->get('/detalle-oportunidad/rediseno-web-accesible')
            ->assertOk()
            ->assertSee('Rediseño web accesible');
    }

    public function test_el_id_secuencial_ya_no_es_una_url_valida(): void
    {
        $oportunidad = Oportunidad::factory()->create();

        $this->get('/detalle-oportunidad/'.$oportunidad->id)->assertNotFound();
    }

    public function test_un_slug_inexistente_da_404(): void
    {
        $this->get('/detalle-oportunidad/no-existe')->assertNotFound();
    }

    public function test_los_titulos_repetidos_generan_slugs_distintos(): void
    {
        $primera = Oportunidad::factory()->create(['titulo' => 'Campaña de reforestación']);
        $segunda = Oportunidad::factory()->create(['titulo' => 'Campaña de reforestación']);

        $this->assertSame('campana-de-reforestacion', $primera->slug);
        $this->assertSame('campana-de-reforestacion-2', $segunda->slug);
    }

    public function test_el_slug_se_regenera_cuando_cambia_el_titulo(): void
    {
        $oportunidad = Oportunidad::factory()->create(['titulo' => 'Título original']);

        $oportunidad->update(['titulo' => 'Título nuevo']);

        $this->assertSame('titulo-nuevo', $oportunidad->fresh()->slug);
    }

    public function test_la_ficha_funciona_con_el_catalogo_demo_sin_sembrar(): void
    {
        $this->assertSame(0, Oportunidad::count());

        $this->get('/detalle-oportunidad/mentoria-de-matematicas')
            ->assertOk()
            ->assertSee('Mentoría de matemáticas');
    }

    public function test_el_listado_expone_el_slug_en_cada_oportunidad(): void
    {
        $this->get('/oportunidades')
            ->assertOk()
            ->assertSee('/detalle-oportunidad/rediseno-web-accesible', false);
    }
}
