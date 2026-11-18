<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Canal de soporte para las cuentas de la comunidad (voluntarios y ONG).
 * Cada mensaje se guarda como un caso en la mesa de ayuda del panel
 * administrador, asociado a la cuenta que lo abrio.
 */
class SoporteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'asunto' => ['required', 'string', 'max:160'],
            'mensaje' => ['required', 'string', 'max:3000'],
            'prioridad' => ['nullable', Rule::in(Ticket::PRIORIDADES)],
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'asunto' => $datos['asunto'],
            'mensaje' => $datos['mensaje'],
            'prioridad' => $datos['prioridad'] ?? 'media',
            'estado' => 'abierto',
        ]);

        return response()->json([
            'ok' => true,
            'id' => $ticket->id,
            'mensaje' => "Caso #{$ticket->id} recibido. Te responderemos muy pronto.",
        ], 201);
    }
}
