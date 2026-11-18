<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\GuardrailService;
use App\Services\Chatbot\Mcp\ToolExecutor;
use App\Services\Chatbot\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * API REST del asistente.
 *
 * Es la misma lógica que usa el widget web, sin sesión ni CSRF: el token
 * identifica al cliente y, si aplica, a la cuenta que actúa en su nombre.
 * Las escrituras siguen exigiendo confirmación de un solo uso.
 */
class AssistantController extends Controller
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

        return $this->json($respuesta);
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
            return $this->json($chatbot->history($request, $datos['conversation_id'] ?? null));
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }
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
            return $this->json($chatbot->confirm(
                (string) $datos['token'],
                $datos['decision'] === 'accept',
                $request
            ));
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }
    }

    public function reiniciar(Request $request, ChatbotService $chatbot): JsonResponse
    {
        if (! $chatbot->enabled()) {
            return $this->noDisponible();
        }

        try {
            return $this->json($chatbot->reset($request));
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }
    }

    /**
     * Interfaz compatible con Chat Completions de OpenAI.
     *
     * Permite enchufar a Vera en cualquier cliente que hable el protocolo
     * (LibreChat, Continue, n8n, un CRM con "AI block"…) sin adaptadores
     * propios. No soporta streaming: devuelve la respuesta completa.
     */
    public function completions(Request $request, ChatbotService $chatbot): JsonResponse
    {
        if (! $chatbot->enabled()) {
            return $this->noDisponible();
        }

        $datos = $request->validate([
            'model' => ['nullable', 'string', 'max:120'],
            'messages' => ['required', 'array', 'min:1', 'max:40'],
            'messages.*.role' => ['required', 'in:user,assistant,system'],
            'messages.*.content' => ['required', 'string', 'max:'.(int) config('chatbot.prompt.max_input_chars', 1200)],
            'conversation_id' => ['nullable', 'uuid'],
            'user' => ['nullable', 'string', 'max:120'],
        ]);

        $mensajes = array_values(array_filter(
            $datos['messages'],
            fn (array $mensaje) => $mensaje['role'] === 'user'
        ));

        if ($mensajes === []) {
            return $this->json([
                'error' => [
                    'message' => 'La petición debe incluir al menos un mensaje con rol user.',
                    'type' => 'invalid_request_error',
                ],
            ], 422);
        }

        try {
            $ultimo = end($mensajes);

            $respuesta = $chatbot->respond(
                (string) $ultimo['content'],
                $datos['conversation_id'] ?? null,
                $request
            );
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }

        return $this->json([
            'id' => 'chatcmpl-'.Str::random(24),
            'object' => 'chat.completion',
            'created' => now()->timestamp,
            'model' => $datos['model'] ?? (string) config('chatbot.llm.model', 'voluntmatch-vera'),
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => (string) ($respuesta['message'] ?? ''),
                ],
                'finish_reason' => 'stop',
            ]],
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
            'voluntmatch' => [
                'conversation_id' => $respuesta['conversation_id'] ?? null,
                'request_id' => $respuesta['request_id'] ?? null,
                'sources' => $respuesta['sources'] ?? [],
                'links' => $respuesta['links'] ?? [],
                'pending' => $respuesta['pending'] ?? null,
            ],
        ]);
    }

    /**
     * Catálogo de herramientas autorizadas para el rol del token.
     */
    public function herramientas(Request $request, ToolRegistry $registry): JsonResponse
    {
        $audience = $this->audience($request);
        $tools = [];

        foreach ($registry->all($audience) as $name => $definition) {
            $tools[] = [
                'name' => $name,
                'description' => $definition['description'] ?? '',
                'audiences' => $definition['audiences'] ?? [],
                'requires_auth' => (bool) ($definition['requires_auth'] ?? false),
                'read_only' => (bool) ($definition['read_only'] ?? true),
                'requires_confirmation' => (bool) ($definition['requires_confirmation'] ?? false),
                'input_schema' => $definition['input_schema'] ?? ['type' => 'object', 'properties' => []],
            ];
        }

        return $this->json(['ok' => true, 'audience' => $audience, 'tools' => $tools]);
    }

    /**
     * Invocación directa de una herramienta (sin pasar por el modelo).
     */
    public function invocar(Request $request, string $tool, ToolExecutor $executor): JsonResponse
    {
        $datos = $request->validate([
            'arguments' => ['nullable', 'array'],
            'conversation_id' => ['nullable', 'uuid'],
        ]);

        $contexto = $this->contexto($request);

        try {
            $resultado = $executor->execute($tool, (array) ($datos['arguments'] ?? []), $contexto);
        } catch (Throwable $exception) {
            return $this->errorInterno($exception);
        }

        return $this->json([
            'ok' => (bool) ($resultado['ok'] ?? false),
            'tool' => $tool,
            'result' => $resultado,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function contexto(Request $request): array
    {
        $client = $request->attributes->get('api_client');

        return [
            'user' => $request->user(),
            'audience' => $this->audience($request),
            'conversation' => null,
            'request_id' => (string) Str::uuid(),
            'owner_key' => 'api:'.$client?->id,
            'api_client' => $client,
        ];
    }

    private function audience(Request $request): string
    {
        $client = $request->attributes->get('api_client');

        return $client?->audience() ?? 'public';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)
            ->header('Cache-Control', 'no-store, private');
    }

    private function noDisponible(): JsonResponse
    {
        return $this->json([
            'ok' => false,
            'error' => 'El asistente no está disponible en este momento.',
        ], 503);
    }

    private function errorInterno(Throwable $exception): JsonResponse
    {
        $requestId = (string) Str::uuid();

        Log::error('chatbot.api_error', [
            'request_id' => $requestId,
            'message' => app(GuardrailService::class)->redact(Str::limit($exception->getMessage(), 200)),
        ]);

        return $this->json([
            'ok' => false,
            'request_id' => $requestId,
            'error' => 'No pude procesar la solicitud en este momento. No se guardó ninguna acción.',
        ], 500);
    }
}
