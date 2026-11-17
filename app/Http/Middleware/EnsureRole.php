<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limita una ruta a los roles indicados.
 *
 * Uso en routes/web.php:
 *   ->middleware('role:admin')
 *   ->middleware('role:organizacion,voluntario')  // varios roles permitidos
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
