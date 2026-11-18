<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\CrmContact;
use App\Models\IntegrationDelivery;
use App\Models\User;
use App\Services\Integrations\IntegrationGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Panel de integraciones y clientes API.
 *
 * Muestra el estado de cada canal, la bitácora de entregas, los contactos
 * del CRM local y los tokens emitidos. El token en claro se muestra una
 * sola vez, justo después de crearlo.
 */
class IntegracionController extends Controller
{
    public function index(IntegrationGateway $gateway)
    {
        return view('admin.integraciones.index', [
            'estado' => $gateway->status(),
            'clientes' => ApiClient::query()->with(['user:id,name,email', 'creador:id,name'])->latest()->get(),
            'entregas' => IntegrationDelivery::query()->with(['user:id,name', 'apiClient:id,name'])->latest()->limit(25)->get(),
            'contactos' => CrmContact::query()->with(['owner:id,name'])->latest()->limit(15)->get(),
            'resumen' => [
                'enviadas' => IntegrationDelivery::where('status', 'sent')->count(),
                'fallidas' => IntegrationDelivery::where('status', 'failed')->count(),
                'contactos' => CrmContact::count(),
                'tokens' => ApiClient::vigentes()->count(),
            ],
        ]);
    }

    public function storeClient(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(ApiClient::ROLES)],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => [Rule::in(ApiClient::SCOPES)],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $usuario = isset($datos['user_id']) ? User::find($datos['user_id']) : null;

        if ($datos['role'] !== 'public' && ! $usuario) {
            return back()->withErrors([
                'user_id' => 'Un token con rol privado debe estar atado a una cuenta concreta.',
            ])->withInput();
        }

        if ($usuario && $usuario->role !== $datos['role']) {
            return back()->withErrors([
                'role' => 'El rol del token debe coincidir con el rol de la cuenta seleccionada.',
            ])->withInput();
        }

        if ($usuario?->estaBloqueado()) {
            return back()->withErrors(['user_id' => 'La cuenta seleccionada está suspendida.']);
        }

        [$client, $plain] = ApiClient::issue(
            $datos['name'],
            $datos['role'],
            $usuario,
            $datos['scopes'] ?? ApiClient::SCOPES,
            auth()->id(),
            isset($datos['expires_days']) ? now()->addDays((int) $datos['expires_days']) : null
        );

        AuditLog::registrar('api.cliente_creado', $client, $client->name.' · '.$client->role);

        return redirect()
            ->route('admin.integraciones')
            ->with('status', 'Token creado. Cópialo ahora: no volverá a mostrarse.')
            ->with('api_token_nuevo', $plain);
    }

    public function revokeClient(ApiClient $client)
    {
        $client->forceFill([
            'active' => false,
            'revoked_at' => now(),
        ])->save();

        AuditLog::registrar('api.cliente_revocado', $client, $client->name);

        return back()->with('status', 'Token revocado. Las peticiones con ese token fallarán de inmediato.');
    }

    public function updateContact(Request $request, CrmContact $contact)
    {
        $datos = $request->validate([
            'etapa' => ['required', Rule::in(CrmContact::ETAPAS)],
            'notas' => ['nullable', 'string', 'max:1500'],
        ]);

        $contact->forceFill($datos)->save();

        AuditLog::registrar('crm.contacto_actualizado', $contact, $contact->email.' · '.$contact->etapa);

        return back()->with('status', 'Contacto actualizado en el CRM.');
    }

    public function test(Request $request, IntegrationGateway $gateway)
    {
        $datos = $request->validate([
            'channel' => ['required', Rule::in(IntegrationDelivery::CHANNELS)],
        ]);

        $admin = $request->user();
        $channel = (string) $datos['channel'];

        $resultado = match ($channel) {
            'mail' => $gateway->sendMail(
                $admin,
                (string) $admin->email,
                'Prueba de integración de correo',
                "Este es un correo de prueba enviado desde el panel de integraciones de VoluntMatch.\n\nSi lo recibes, el canal de correo está operativo."
            ),
            'calendar' => $gateway->createCalendarEvent($admin, [
                'title' => 'Prueba de integración de calendario',
                'description' => 'Evento creado desde el panel de integraciones de VoluntMatch.',
                'start' => Carbon::now((string) config('integrations.calendar.timezone', 'America/Lima'))->addDay()->setTime(10, 0)->toIso8601String(),
                'duration_minutes' => 30,
            ]),
            'crm' => $gateway->registerCrmContact($admin, [
                'name' => 'Contacto de prueba',
                'email' => (string) $admin->email,
                'stage' => 'nuevo',
                'source' => 'panel-integraciones',
                'notes' => 'Registro generado desde el panel para comprobar el CRM.',
                'owner_id' => $admin->id,
            ]),
            'helpdesk' => $gateway->openHelpdeskCase($admin, [
                'subject' => 'Prueba de integración de mesa de ayuda',
                'message' => 'Caso generado desde el panel para comprobar la mesa de ayuda.',
                'priority' => 'baja',
                'channel' => 'panel',
            ]),
            'notifications' => $gateway->notify($admin, [
                'title' => 'Prueba de notificaciones',
                'body' => 'Si ves este aviso en tu bandeja, el canal de notificaciones está operativo.',
                'url' => route('admin.integraciones'),
                'level' => 'info',
            ]),
        };

        AuditLog::registrar('integracion.prueba', null, $channel.' · '.(($resultado['ok'] ?? false) ? 'ok' : 'fallo'));

        return back()->with(
            ($resultado['ok'] ?? false) ? 'status' : 'error',
            (string) ($resultado['summary'] ?? 'Prueba ejecutada.')
        );
    }
}
