<?php

namespace Tests\Feature;

use App\Models\Oportunidad;
use App\Models\Postulacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostulacionTest extends TestCase
{
    use RefreshDatabase;

    private function sembrarOportunidad(): Oportunidad
    {
        $this->seed(\Database\Seeders\OportunidadSeeder::class);

        return Oportunidad::first();
    }

    public function test_un_voluntario_puede_postular_y_persiste_en_la_base_de_datos(): void
    {
        $oportunidad = $this->sembrarOportunidad();
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)
            ->post('/postular', ['oportunidad_id' => $oportunidad->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('postulaciones', [
            'user_id' => $user->id,
            'oportunidad_id' => $oportunidad->id,
            'estado' => 'en revision',
        ]);
    }

    public function test_postular_dos_veces_no_duplica_la_postulacion(): void
    {
        $oportunidad = $this->sembrarOportunidad();
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)->post('/postular', ['oportunidad_id' => $oportunidad->id]);
        $this->actingAs($user)->post('/postular', ['oportunidad_id' => $oportunidad->id]);

        $this->assertSame(1, Postulacion::where('user_id', $user->id)->count());
    }

    public function test_una_organizacion_no_puede_postular(): void
    {
        $oportunidad = $this->sembrarOportunidad();
        $user = User::factory()->create(['role' => 'organizacion']);

        $this->actingAs($user)
            ->post('/postular', ['oportunidad_id' => $oportunidad->id])
            ->assertForbidden();

        $this->assertGuestCanNotHavePostulaciones($user->id);
    }

    public function test_el_admin_no_puede_postular(): void
    {
        $oportunidad = $this->sembrarOportunidad();
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post('/postular', ['oportunidad_id' => $oportunidad->id])
            ->assertForbidden();

        $this->assertGuestCanNotHavePostulaciones($user->id);
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $oportunidad = $this->sembrarOportunidad();

        $this->post('/postular', ['oportunidad_id' => $oportunidad->id])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Postulacion::count());
    }

    public function test_una_oportunidad_inexistente_da_error_de_validacion(): void
    {
        $user = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($user)
            ->post('/postular', ['oportunidad_id' => 999999])
            ->assertSessionHasErrors(['oportunidad_id']);
    }

    private function assertGuestCanNotHavePostulaciones(int $userId): void
    {
        $this->assertSame(0, Postulacion::where('user_id', $userId)->count());
    }
}
