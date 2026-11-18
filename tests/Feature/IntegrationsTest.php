<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\CrmContact;
use App\Models\IntegrationDelivery;
use App\Models\Oportunidad;
use App\Models\Organizacion;
use App\Models\Postulacion;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Chatbot\Mcp\ToolExecutor;
use App\Services\Integrations\IntegrationGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_correo_solo_sale_a_destinatarios_autorizados(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario', 'email' => 'voluntario@voluntmatch.test']);
        $gateway = app(IntegrationGateway::class);

        $propio = $gateway->sendMail($voluntario, 'voluntario@voluntmatch.test', 'Mi asunto', 'Contenido de prueba.');
        $this->assertTrue($propio['ok']);
        $this->assertDatabaseHas('integration_deliveries', [
            'channel' => 'mail',
            'status' => 'sent',
            'target' => 'voluntario@voluntmatch.test',
        ]);

        $ajeno = $gateway->sendMail($voluntario, 'desconocido@example.com', 'Spam', 'No debería salir.');
        $this->assertFalse($ajeno['ok']);
        $this->assertDatabaseCount('integration_deliveries', 1);
    }

    public function test_una_organizacion_puede_escribir_a_los_voluntarios_que_se_postularon(): void
    {
        $usuarioOng = User::factory()->create(['role' => 'organizacion', 'email' => 'ong@voluntmatch.test']);
        $ong = Organizacion::factory()->create(['user_id' => $usuarioOng->id, 'nombre' => 'ONG Integración']);
        $voluntario = User::factory()->create(['role' => 'voluntario', 'email' => 'postulado@voluntmatch.test']);
        $oportunidad = Oportunidad::factory()->create(['organizacion_id' => $ong->id]);
        Postulacion::create(['user_id' => $voluntario->id, 'oportunidad_id' => $oportunidad->id, 'estado' => 'en revision']);

        $gateway = app(IntegrationGateway::class);

        $this->assertTrue($gateway->canEmail($usuarioOng, 'postulado@voluntmatch.test'));
        $this->assertFalse($gateway->canEmail($usuarioOng, 'otro@example.com'));

        $resultado = $gateway->sendMail($usuarioOng, 'postulado@voluntmatch.test', 'Entrevista', 'Hola, ¿nos vemos el lunes?');
        $this->assertTrue($resultado['ok']);
    }

    public function test_el_calendario_genera_una_invitacion_ics_firmada(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@voluntmatch.test']);
        $gateway = app(IntegrationGateway::class);

        $resultado = $gateway->createCalendarEvent($admin, [
            'title' => 'Entrevista con voluntario',
            'description' => 'Primera conversación.',
            'start' => Carbon::now()->addDay()->setTime(15, 0)->toIso8601String(),
            'duration_minutes' => 45,
        ]);

        $this->assertTrue($resultado['ok']);
        $uid = $resultado['data']['uid'];
        Storage::disk('local')->assertExists('calendar/'.$uid.'.ics');

        $contenido = Storage::disk('local')->get('calendar/'.$uid.'.ics');
        $this->assertStringContainsString('BEGIN:VEVENT', $contenido);
        $this->assertStringContainsString('SUMMARY:Entrevista con voluntario', $contenido);

        $url = $resultado['data']['invitation_url'];
        $this->assertStringContainsString('signature=', $url);

        $this->get($url)->assertOk();
        $this->assertDatabaseHas('integration_deliveries', ['channel' => 'calendar', 'status' => 'sent']);
    }

    public function test_el_crm_guarda_y_actualiza_contactos(): void
    {
        $ong = User::factory()->create(['role' => 'organizacion']);
        $gateway = app(IntegrationGateway::class);

        $primero = $gateway->registerCrmContact($ong, [
            'name' => 'Ana Torres',
            'email' => 'ana@voluntmatch.test',
            'stage' => 'contactado',
            'notes' => 'Interesada en educación.',
        ]);

        $this->assertTrue($primero['ok']);
        $this->assertSame(1, CrmContact::count());

        $segundo = $gateway->registerCrmContact($ong, [
            'name' => 'Ana Torres',
            'email' => 'ana@voluntmatch.test',
            'stage' => 'entrevista',
        ]);

        $this->assertTrue($segundo['ok']);
        $this->assertSame(1, CrmContact::count());
        $this->assertSame('entrevista', CrmContact::first()->etapa);
    }

    public function test_la_mesa_de_ayuda_abre_un_ticket_con_canal(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        $gateway = app(IntegrationGateway::class);

        $resultado = $gateway->openHelpdeskCase($voluntario, [
            'subject' => 'Problema con mi perfil',
            'message' => 'No puedo guardar mis habilidades.',
            'priority' => 'alta',
        ]);

        $this->assertTrue($resultado['ok']);
        $ticket = Ticket::firstOrFail();
        $this->assertSame('asistente', $ticket->canal);
        $this->assertSame('alta', $ticket->prioridad);
    }

    public function test_las_notificaciones_llegan_a_la_bandeja_interna(): void
    {
        $voluntario = User::factory()->create(['role' => 'voluntario']);
        $gateway = app(IntegrationGateway::class);

        $resultado = $gateway->notify($voluntario, [
            'title' => 'Tu postulación avanzó',
            'body' => 'La ONG quiere entrevistarte.',
            'url' => '/mi-espacio/postulaciones',
        ]);

        $this->assertTrue($resultado['ok']);
        $this->assertSame(1, $voluntario->notifications()->count());

        $this->actingAs($voluntario)
            ->get(route('notificaciones'))
            ->assertOk()
            ->assertSee('Tu postulación avanzó');
    }

    public function test_una_organizacion_puede_notificar_a_sus_postulantes(): void
    {
        $usuarioOng = User::factory()->create(['role' => 'organizacion']);
        $ong = Organizacion::factory()->create(['user_id' => $usuarioOng->id]);
        $voluntario = User::factory()->create(['role' => 'voluntario', 'email' => 'candidato@voluntmatch.test']);
        $oportunidad = Oportunidad::factory()->create(['organizacion_id' => $ong->id]);
        Postulacion::create(['user_id' => $voluntario->id, 'oportunidad_id' => $oportunidad->id, 'estado' => 'en revision']);

        $resultado = app(ToolExecutor::class)->execute(
            'notificar_candidato',
            [
                'email' => 'candidato@voluntmatch.test',
                'titulo' => 'Tu postulación avanzó',
                'mensaje' => 'Queremos coordinar una entrevista contigo.',
            ],
            [
                'user' => $usuarioOng,
                'audience' => 'organizacion',
                'conversation' => null,
                'request_id' => 'test-notificacion',
                'owner_key' => 'user:'.$usuarioOng->id,
            ],
            true
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame(1, $voluntario->notifications()->count());
        $this->assertDatabaseHas('integration_deliveries', [
            'channel' => 'notifications',
            'target' => 'candidato@voluntmatch.test',
            'status' => 'sent',
        ]);
    }

    public function test_el_admin_crea_y_revoca_tokens_desde_el_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.integraciones'))
            ->assertOk()
            ->assertSee('Estado de las integraciones');

        $this->actingAs($admin)
            ->post(route('admin.integraciones.clientes.store'), [
                'name' => 'Mesa de ayuda externa',
                'role' => 'public',
                'scopes' => ['assistant:chat'],
            ])
            ->assertRedirect(route('admin.integraciones'))
            ->assertSessionHas('api_token_nuevo');

        $cliente = ApiClient::firstOrFail();
        $this->assertTrue($cliente->estaVigente());

        $this->actingAs($admin)
            ->patch(route('admin.integraciones.clientes.revoke', $cliente))
            ->assertRedirect();

        $this->assertFalse($cliente->fresh()->estaVigente());
        $this->assertDatabaseHas('audit_logs', ['accion' => 'api.cliente_revocado']);
    }

    public function test_el_admin_puede_actualizar_un_contacto_del_crm(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contacto = CrmContact::create([
            'nombre' => 'Luis Ramos',
            'email' => 'luis@voluntmatch.test',
            'etapa' => 'nuevo',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.integraciones.contactos.update', $contacto), [
                'etapa' => 'voluntario',
                'notas' => 'Ya empezó su primer proyecto.',
            ])
            ->assertRedirect();

        $this->assertSame('voluntario', $contacto->fresh()->etapa);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'crm.contacto_actualizado']);
    }

    public function test_el_admin_puede_probar_un_canal_desde_el_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@voluntmatch.test']);

        $this->actingAs($admin)
            ->post(route('admin.integraciones.test'), ['channel' => 'notifications'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertDatabaseHas('integration_deliveries', ['channel' => 'notifications', 'status' => 'sent']);
    }

    public function test_la_bitacora_no_guarda_el_cuerpo_del_correo(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@voluntmatch.test']);
        app(IntegrationGateway::class)->sendMail($admin, 'admin@voluntmatch.test', 'Asunto', 'Contenido privado del correo.');

        $entrega = IntegrationDelivery::where('channel', 'mail')->firstOrFail();
        $this->assertArrayNotHasKey('body', $entrega->payload);
        $this->assertArrayHasKey('body_chars', $entrega->payload);
        $this->assertStringNotContainsString('Contenido privado', json_encode($entrega->payload));
    }
}
