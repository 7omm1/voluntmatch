<?php

namespace Tests\Feature;

use App\Http\Controllers\OportunidadController;
use App\Models\Organizacion;
use App\Models\Oportunidad;
use App\Support\Peru;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OportunidadSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_tres_ong_con_diez_oportunidades_cada_una(): void
    {
        $this->seed(\Database\Seeders\OportunidadSeeder::class);

        $this->assertSame(30, Oportunidad::count());
        $this->assertSame(3, Organizacion::count());

        Organizacion::withCount('oportunidades')->get()->each(function (Organizacion $organizacion) {
            $this->assertSame(
                10,
                $organizacion->oportunidades_count,
                "La ONG {$organizacion->nombre} debería tener diez oportunidades distintas."
            );
        });

        $this->assertSame(30, Oportunidad::distinct()->count('titulo'));
        $this->assertSame(30, Oportunidad::distinct()->count('slug'));

        $primera = Oportunidad::first();
        $this->assertSame('Rediseño web accesible', $primera->titulo);
        $this->assertSame('rediseno-web-accesible', $primera->slug);
        $this->assertSame('Fundación Horizonte', $primera->organizacion->nombre);
        $this->assertSame(92, $primera->afinidad);
        $this->assertCount(3, $primera->porque);
        $this->assertTrue($primera->organizacion->verificada);
    }

    public function test_los_rucs_sembrados_pasan_el_digito_verificador(): void
    {
        $this->seed(\Database\Seeders\OportunidadSeeder::class);

        Organizacion::whereNotNull('ruc')->get()->each(function (Organizacion $organizacion) {
            $this->assertTrue(
                Peru::rucValido($organizacion->ruc),
                "El RUC {$organizacion->ruc} de {$organizacion->nombre} no es válido."
            );
        });
    }

    public function test_es_idempotente(): void
    {
        $this->seed(\Database\Seeders\OportunidadSeeder::class);
        $this->seed(\Database\Seeders\OportunidadSeeder::class);

        $this->assertSame(30, Oportunidad::count());
        $this->assertSame(3, Organizacion::count());
    }

    public function test_el_listado_prefiere_la_base_de_datos_al_fallback_estatico(): void
    {
        $this->seed(\Database\Seeders\OportunidadSeeder::class);

        $titulos = array_column(OportunidadController::demo(), 'title');
        $this->assertContains('Rediseño web accesible', $titulos);
        $this->assertContains('rediseno-web-accesible', array_column(OportunidadController::demo(), 'slug'));

        // Cambiamos la fila en BD: el listado debe reflejar la BD, no el array estático.
        Oportunidad::where('titulo', 'Rediseño web accesible')->update(['titulo' => 'Título actualizado en BD']);

        $titulos = array_column(OportunidadController::demo(), 'title');
        $this->assertContains('Título actualizado en BD', $titulos);
        $this->assertNotContains('Rediseño web accesible', $titulos);
    }
}
