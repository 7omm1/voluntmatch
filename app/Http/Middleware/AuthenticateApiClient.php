<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación de la API por token Bearer.
 *
 * El token se compara contra el hash SHA-256 guardado; nunca se busca en
 * claro. El cliente queda disponible en $request->attributes('api_client')
 * y, si está atado a una cuenta, esa cuenta pasa a ser el usuario de la
 * petición. Los scopes se exigen como parámetros del middleware.
 */
class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        if (! config('api.enabled', true)) {
            return $this->error('La API está desactivada en este entorno.', 503);
        }

        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $this->error('Falta el token de acceso. Envía Authorization: Bearer <token>.', 401);
        }

        $client = ApiClient::findFromToken($token);

        if (! $client || ! $client->estaVigente()) {
            return $this->error('El token no es válido, expiró o fue revocado.', 401);
        }

        if (! $client->allowsAll($scopes)) {
            return $this->error('El token no tiene permiso para esta operación.', 403, [
                'required_scopes' => $scopes,
                'granted_scopes' => $client->scopes,
            ]);
        }

        $user = $client->user;

        if ($user && $user->estaBloqueado()) {
            return $this->error('La cuenta asociada al token está suspendida.', 403);
        }

        if ($user) {
            auth()->setUser($user);
        }

        $request->attributes->set('api_client', $client);

        $client->forceFill([
            'last_used_at' => now(),
            'last_ip' => $request->ip(),
        ])->saveQuietly();

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function error(string $message, int $status, array $extra = []): Response
    {
        return response()->json(array_merge([
            'ok' => false,
            'error' => $message,
        ], $extra), $status, ['Cache-Control' => 'no-store, private']);
    }
}
