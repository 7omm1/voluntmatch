<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Mesa de soporte: casos abiertos por la comunidad, con prioridad,
 * respuesta y cierre. La administracion tambien puede registrar casos
 * recibidos por otros canales.
 */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $estado = (string) $request->query('estado');
        $prioridad = (string) $request->query('prioridad');

        $tickets = Ticket::query()
            ->with(['user:id,name,email,role', 'atendidoPor:id,name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('asunto', 'like', "%{$q}%")
                        ->orWhere('mensaje', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
                });
            })
            ->when(in_array($estado, Ticket::ESTADOS, true), fn ($query) => $query->where('estado', $estado))
            ->when(in_array($prioridad, Ticket::PRIORIDADES, true), fn ($query) => $query->where('prioridad', $prioridad))
            ->orderByRaw("case when estado = 'abierto' then 0 when estado = 'en_proceso' then 1 else 2 end")
            ->orderByRaw("case when prioridad = 'alta' then 0 when prioridad = 'media' then 1 else 2 end")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'q' => $q,
            'estado' => $estado,
            'prioridad' => $prioridad,
            'totales' => [
                'todos' => Ticket::count(),
                'abiertos' => Ticket::where('estado', 'abierto')->count(),
                'enProceso' => Ticket::where('estado', 'en_proceso')->count(),
                'cerrados' => Ticket::where('estado', 'cerrado')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'asunto' => 'required|string|max:160',
            'mensaje' => 'required|string|max:3000',
            'prioridad' => ['required', Rule::in(Ticket::PRIORIDADES)],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $ticket = Ticket::create($datos);

        AuditLog::registrar('ticket.creado', $ticket, $ticket->asunto);

        return back()->with('status', 'Caso de soporte registrado.');
    }

    public function responder(Request $request, Ticket $ticket)
    {
        $datos = $request->validate([
            'respuesta' => 'required|string|max:3000',
            'cerrar' => 'nullable|boolean',
        ]);

        $ticket->forceFill([
            'respuesta' => $datos['respuesta'],
            'estado' => $request->boolean('cerrar') ? 'cerrado' : 'en_proceso',
            'atendido_por' => auth()->id(),
        ])->save();

        AuditLog::registrar('ticket.respondido', $ticket, $request->boolean('cerrar') ? 'cerrado' : 'en seguimiento');

        return back()->with('status', 'Respuesta guardada.');
    }

    public function cerrar(Ticket $ticket)
    {
        $ticket->forceFill([
            'estado' => 'cerrado',
            'atendido_por' => auth()->id(),
        ])->save();

        AuditLog::registrar('ticket.cerrado', $ticket);

        return back()->with('status', 'Caso cerrado.');
    }
}
