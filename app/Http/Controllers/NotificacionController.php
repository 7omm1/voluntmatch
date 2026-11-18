<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Bandeja interna de notificaciones del usuario autenticado.
 * Es el destino por defecto del canal de notificaciones.
 */
class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notificaciones', [
            'notificaciones' => $notificaciones,
            'sinLeer' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function marcarTodas(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Marcaste todas las notificaciones como leídas.');
    }
}
