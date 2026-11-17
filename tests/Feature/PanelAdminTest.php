<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Configuracion;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Reporte;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_todas_las_secciones_del_panel_responden_para_el_admin(): void
    {
        $admin = $this->admin();
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        $organizacion = Organizacion::factory()->create(['verificada' => false]);
        $oportunidad = Oportunidad::factory()->create();
        $reporte = Reporte::create(['motivo' => 'Perfil falso', 'estado' => 'pendiente']);
        $ticket = Ticket::create(['asunto' => 'Error', 'mensaje' => 'No puedo entrar']);

        $this->actingAs($admin)->get('/dashboard-admin')->assertOk()->assertSee('El pulso de VoluntMatch');
        $this->get('/admin/usuarios')->assertOk()->assertSee('Cuentas registradas');
        $this->get('/admin/usuarios/'.$voluntario->id)->assertOk()->assertSee($voluntario->email);
        $this->get('/admin/organizaciones')->assertOk()->assertSee($organizacion->nombre);
        $this->get('/admin/oportunidades')->assertOk()->assertSee($oportunidad->titulo);
        $this->get('/admin/reportes')->assertOk()->assertSee('Perfil falso');
        $this->get('/admin/tickets')->assertOk()->assertSee('No puedo entrar');
        $this->get('/admin/anuncios')->assertOk();
        $this->get('/admin/analiticas')->assertOk();
        $this->get('/admin/configuracion')->assertOk();
        $this->get('/admin/actividad')->assertOk();
        $this->get('/admin/buscar?q='.urlencode($voluntario->name))->assertOk()->assertSee($voluntario->email);

        $this->assertSame($reporte->id, Reporte::first()->id);
        $this->assertSame($ticket->id, Ticket::first()->id);
    }

    public function test_un_usuario_normal_no_puede_entrar_al_panel_admin(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($voluntario)->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($voluntario)->get('/admin/configuracion')->assertForbidden();
    }

    public function test_el_admin_suspende_y_reactiva_cuentas(): void
    {
        $admin = $this->admin();
        $usuario = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($admin)
            ->patch("/admin/usuarios/{$usuario->id}/bloquear", ['motivo' => 'Suplantación'])
            ->assertRedirect();

        $usuario->refresh();
        $this->assertNotNull($usuario->blocked_at);
        $this->assertSame('Suplantación', $usuario->blocked_reason);

        $this->actingAs($admin)
            ->patch("/admin/usuarios/{$usuario->id}/reactivar")
            ->assertRedirect();

        $this->assertNull($usuario->refresh()->blocked_at);
    }

    public function test_el_admin_no_puede_suspenderse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/admin/usuarios/{$admin->id}/bloquear")
            ->assertRedirect();

        $this->assertNull($admin->refresh()->blocked_at);
    }

    public function test_una_cuenta_suspendida_pierde_la_sesion(): void
    {
        $usuario = User::factory()->create(['role' => 'voluntario', 'blocked_at' => now()]);

        $this->actingAs($usuario)->get('/home')->assertRedirect(route('login'));
    }

    public function test_el_admin_cambia_el_rol_y_crea_la_ficha_de_ong(): void
    {
        $admin = $this->admin();
        $usuario = User::factory()->create(['role' => 'voluntario']);

        $this->actingAs($admin)
            ->patch("/admin/usuarios/{$usuario->id}/rol", ['role' => 'organizacion'])
            ->assertRedirect();

        $this->assertSame('organizacion', $usuario->refresh()->role);
        $this->assertNotNull($usuario->organizacion);
        $this->assertFalse($usuario->organizacion->verificada);
    }

    public function test_verificar_y_rechazar_organizaciones(): void
    {
        $admin = $this->admin();
        $organizacion = Organizacion::factory()->create(['verificada' => false]);

        $this->actingAs($admin)
            ->patch("/admin/organizaciones/{$organizacion->id}/revisar", [
                'accion' => 'verificar',
                'nota' => 'Documentación completa',
            ])->assertRedirect();

        $organizacion->refresh();
        $this->assertTrue($organizacion->verificada);
        $this->assertSame($admin->id, $organizacion->verificada_por);
        $this->assertNotNull($organizacion->verificada_at);

        $this->actingAs($admin)
            ->patch("/admin/organizaciones/{$organizacion->id}/revisar", ['accion' => 'rechazar'])
            ->assertRedirect();

        $this->assertFalse($organizacion->refresh()->verificada);
    }

    public function test_el_admin_modera_el_catalogo_de_oportunidades(): void
    {
        $admin = $this->admin();
        $oportunidad = Oportunidad::factory()->create(['estado' => 'publicada']);

        $this->actingAs($admin)
            ->patch("/admin/oportunidades/{$oportunidad->slug}/estado", ['estado' => 'archivada'])
            ->assertRedirect();
        $this->assertSame('archivada', $oportunidad->refresh()->estado);

        $this->actingAs($admin)
            ->patch("/admin/oportunidades/{$oportunidad->slug}/destacar")
            ->assertRedirect();
        $this->assertTrue($oportunidad->refresh()->destacada);

        $this->actingAs($admin)
            ->delete("/admin/oportunidades/{$oportunidad->slug}")
            ->assertRedirect();
        $this->assertDatabaseMissing('oportunidades', ['id' => $oportunidad->id]);
    }

    public function test_el_admin_resuelve_reportes_y_responde_tickets(): void
    {
        $admin = $this->admin();
        $reporte = Reporte::create(['motivo' => 'Spam', 'estado' => 'pendiente']);
        $ticket = Ticket::create(['asunto' => 'Error', 'mensaje' => 'Ayuda', 'prioridad' => 'alta']);

        $this->actingAs($admin)
            ->patch("/admin/reportes/{$reporte->id}/resolver", ['nota' => 'Se contactó al usuario'])
            ->assertRedirect();
        $this->assertSame('resuelto', $reporte->refresh()->estado);
        $this->assertSame($admin->id, $reporte->resuelto_por);

        $this->actingAs($admin)
            ->patch("/admin/tickets/{$ticket->id}/responder", [
                'respuesta' => 'Estamos revisando tu caso.',
                'cerrar' => '1',
            ])->assertRedirect();
        $this->assertSame('cerrado', $ticket->refresh()->estado);
        $this->assertSame($admin->id, $ticket->atendido_por);
    }

    public function test_el_admin_publica_anuncios_y_guarda_configuracion(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/anuncios', [
            'titulo' => 'Bienvenida',
            'cuerpo' => 'Nuevos retos disponibles.',
            'audiencia' => 'voluntario',
        ])->assertRedirect();

        $anuncio = Anuncio::firstOrFail();
        $this->assertSame($admin->id, $anuncio->created_by);
        $this->assertTrue($anuncio->activo);

        $this->actingAs($admin)->patch('/admin/configuracion', [
            'habilidades' => 90,
            'horario' => 40,
            'causa' => 75,
            'cercania' => 20,
            'aviso' => 'Semana de voluntariado',
            'max_postulaciones' => 3,
            'ocultar_reportadas' => '1',
        ])->assertRedirect();

        $this->assertSame(90, Configuracion::obtener('matching.pesos')['habilidades']);
        $this->assertTrue(Configuracion::obtener('moderacion')['ocultar_reportadas']);
    }

    public function test_el_admin_exporta_csv_de_usuarios(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Exportable Uno', 'role' => 'voluntario']);

        $respuesta = $this->actingAs($admin)->get('/admin/usuarios/exportar');

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', $respuesta->headers->get('content-type'));
        $this->assertStringContainsString('Exportable Uno', $respuesta->streamedContent());
    }
}
