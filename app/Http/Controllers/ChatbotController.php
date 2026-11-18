<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\GuardrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Endpoints del widget del asistente.
 *
 * La sesión, el CSRF y el middleware web se aplican igual que en el resto
 * de la aplicación. El rol y el dueño de la conversación se resuelven en el
 * servidor; el navegador solo envía un UUID opaco.
 */
class ChatbotController extends Controller
{
    public function mensaje(Request $request, ChatbotService $chatbot): JsonResponse
    {
        if (! $chatbot->enabled()) {
            return $this->noDisponible();
        }

        $datos = $request->validate([
            'message' => ['required', 'string', 'max:'.(int) config('chatbot.prompt.max_input_chars', 1200)],
            'conversation_id' => ['nullable', 'uuid'],
        ]);

        try {
            $respuesta = $chatbot->respond(
                (string) $datos['message'],
                $datos['conversation_id'] ?? null,
                $request
            );
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }

        return response()->json($respuesta)->header('Cache-Control', 'no-store, private');
    }

    public function historial(Request $request, ChatbotService $chatbot): JsonResponse
    {
        if (! $chatbot->enabled()) {
            return $this->noDisponible();
        }

        $datos = $request->validate([
            'conversation_id' => ['nullable', 'uuid'],
        ]);

        try {
            $respuesta = $chatbot->history($request, $datos['conversation_id'] ?? null);
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }

        return response()
            ->json($respuesta)
            ->header('Cache-Control', 'no-store, private');
    }

    public function confirmar(Request $request, ChatbotService $chatbot): JsonResponse
    {
        if (! $chatbot->enabled()) {
            return $this->noDisponible();
        }

        $datos = $request->validate([
            'token' => ['required', 'string', 'max:128'],
            'decision' => ['required', 'in:accept,cancel'],
        ]);

        try {
            $respuesta = $chatbot->confirm(
                (string) $datos['token'],
                $datos['decision'] === 'accept',
                $request
            );
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }

        return response()->json($respuesta)->header('Cache-Control', 'no-store, private');
    }

    public function reiniciar(Request $request, ChatbotService $chatbot): JsonResponse
    {
        if (! $chatbot->enabled()) {
            return $this->noDisponible();
        }

        try {
            $respuesta = $chatbot->reset($request);
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }

        return response()
            ->json($respuesta)
            ->header('Cache-Control', 'no-store, private');
    }

    private function errorInterno(Throwable $exception): JsonResponse
    {
        $requestId = (string) Str::uuid();

        Log::error('chatbot.http_error', [
            'request_id' => $requestId,
            'message' => app(GuardrailService::class)->redact(Str::limit($exception->getMessage(), 200)),
        ]);

        return response()->json([
            'ok' => false,
            'request_id' => $requestId,
            'message' => 'No pude procesar tu solicitud en este momento. No se guardó ninguna acción. Inténtalo de nuevo o escríbenos desde la página de contacto.',
        ], 500)->header('Cache-Control', 'no-store, private');
    }

    private function noDisponible(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'El asistente no está disponible en este momento. Puedes escribirnos desde la página de contacto.',
        ], 503);
    }
}
