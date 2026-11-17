<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expulsa a las cuentas suspendidas por la administracion, incluso si
 * ya tenian una sesion iniciada antes del bloqueo.
 */
class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->estaBloqueado()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'error',
                'Tu cuenta está suspendida. Escríbenos desde la página de contacto para revisar tu caso.'
            );
        }

        return $next($request);
    }
}
