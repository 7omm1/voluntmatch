<?php

namespace Tests\Feature;

use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Models\User;
use Database\Seeders\ComunidadDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComunidadDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_varias_ong_y_estudiantes_con_postulaciones(): void
    {
        $this->seed(ComunidadDemoSeeder::class);

        $this->assertSame(6, User::where('role', 'organizacion')->count());
        $this->assertSame(14, User::where('role', 'voluntario')->count());
        $this->assertSame(12, Oportunidad::count());
        $this->assertGreaterThanOrEqual(25, Postulacion::count());
    }

    public function test_las_cuentas_demo_pueden_iniciar_sesion_y_ver_su_panel(): void
    {
        $this->seed(ComunidadDemoSeeder::class);

        $sofia = User::where('email', 'sofia@voluntmatch.test')->firstOrFail();

        $this->post('/logear', [
            'email' => 'sofia@voluntmatch.test',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        foreach ([
            'voluntario.resumen',
            'voluntario.skills',
            'voluntario.matches',
            'voluntario.postulaciones',
            'voluntario.impacto',
            'voluntario.mensajes',
        ] as $ruta) {
            $this->actingAs($sofia)->get(route($ruta))->assertOk();
        }

        $this->actingAs($sofia)->get(route('voluntario.resumen'))->assertSee('Sofía');
        $this->actingAs($sofia)->get(route('voluntario.matches'))->assertSee('MATCH');
        $this->actingAs($sofia)->get(route('voluntario.postulaciones'))->assertSee('Aceptado');
    }

    public function test_la_cuenta_demo_de_ong_ve_sus_candidatos_reales(): void
    {
        $this->seed(ComunidadDemoSeeder::class);

        $cuenta = User::where('email', 'manos@voluntmatch.test')->firstOrFail();

        foreach ([
            'ong.resumen',
            'ong.oportunidades',
            'ong.talento',
            'ong.candidatos',
            'ong.metricas',
            'ong.mensajes',
        ] as $ruta) {
            $this->actingAs($cuenta)->get(route($ruta))->assertOk();
        }

        $this->actingAs($cuenta)->get(route('ong.resumen'))->assertSee('Manos que Suman');
        $this->actingAs($cuenta)->get(route('ong.candidatos'))->assertSee('Postula a:');
        $this->actingAs($cuenta)->get(route('ong.oportunidades'))->assertSee('Web accesible para todos');
    }
}
