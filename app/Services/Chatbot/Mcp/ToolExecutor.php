<?php

namespace App\Services\Chatbot\Mcp;

use App\Models\ChatbotToolCall;
use App\Services\Chatbot\GuardrailService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Única puerta de entrada a una herramienta.
 *
 * Comprueba de nuevo la audiencia, valida el esquema, exige confirmación
 * para escrituras y deja una traza de cada llamada (incluidas las
 * denegadas). La confirmación es de un solo uso y está atada al dueño de
 * la conversación.
 */
class ToolExecutor
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly ArgumentValidator $validator,
        private readonly LocalToolHandler $local,
        private readonly McpManager $mcp,
        private readonly GuardrailService $guardrails,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @param  array{user: mixed, audience: string, conversation: mixed, request_id: string, owner_key: string}  $context
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args, array $context, bool $confirmed = false): array
    {
        $started = hrtime(true);
        $audience = (string) $context['audience'];
        $definition = $this->registry->find($name, $audience);

        if ($definition === null) {
            $candidate = $this->registry->definitionRegardlessOfAudience($name);

            if ($candidate !== null && ($candidate['requires_auth'] ?? false) && empty($context['user'])) {
                $this->record($name, 'denied', $args, $context, $started, 'Se requiere sesión.');

                return [
                    'ok' => false,
                    'denied' => true,
                    'summary' => 'Para consultar datos de tu cuenta necesitas iniciar sesión en VoluntMatch. El asistente nunca muestra información de otras cuentas.',
                    'links' => [
                        ['label' => 'Iniciar sesión', 'url' => route('login')],
                        ['label' => 'Crear cuenta', 'url' => route('registro-voluntario')],
                    ],
                ];
            }

            $this->record($name, 'denied', $args, $context, $started, 'Herramienta no disponible para la audiencia.');

            return [
                'ok' => false,
                'denied' => true,
                'summary' => 'Esa acción no está disponible para tu tipo de cuenta. Si crees que es un error, escribe a soporte.',
            ];
        }

        if (($definition['requires_auth'] ?? false) && empty($context['user'])) {
            $this->record($name, 'denied', $args, $context, $started, 'Se requiere sesión.');

            return [
                'ok' => false,
                'denied' => true,
                'summary' => 'Para hacer eso necesitas iniciar sesión en VoluntMatch. Puedes hacerlo con tu cuenta de estudiante u organización.',
                'links' => [
                    ['label' => 'Iniciar sesión', 'url' => route('login')],
                    ['label' => 'Crear cuenta', 'url' => route('registro-voluntario')],
                ],
            ];
        }

        $validation = $this->validator->validate(
            is_array($definition['input_schema'] ?? null) ? $definition['input_schema'] : [],
            $args
        );

        if (! $validation['valid']) {
            $this->record($name, 'invalid', $args, $context, $started, implode(' ', $validation['errors']));

            return [
                'ok' => false,
                'invalid_arguments' => true,
                'errors' => $validation['errors'],
                'summary' => 'Necesito un poco más de información para continuar. ¿Puedes darme el detalle completo?',
            ];
        }

        $args = $validation['args'];

        // Confirmación explícita vía API/MCP: el cliente canjea el token de un
        // solo uso que devolvió la herramienta de escritura.
        if ($name === 'confirmar_accion') {
            return $this->confirm((string) ($args['token'] ?? ''), true, $context);
        }

        if (($definition['requires_confirmation'] ?? false) && ! $confirmed) {
            $summary = $this->describeAction($definition, $args);
            $token = bin2hex(random_bytes(32));

            Cache::put('chatbot.pending.'.$token, [
                'tool' => $name,
                'args' => $args,
                'owner_key' => $context['owner_key'],
                'user_id' => $context['user']?->id,
                'conversation' => $context['conversation']?->public_id,
                'request_id' => $context['request_id'],
                'summary' => $summary,
            ], (int) config('chatbot.cache.pending_ttl', 300));

            $this->record($name, 'pending_confirmation', $args, $context, $started, $summary);

            return [
                'ok' => true,
                'requires_confirmation' => true,
                'pending' => [
                    'token' => $token,
                    'tool' => $name,
                    'summary' => $summary,
                    'expires_in' => (int) config('chatbot.cache.pending_ttl', 300),
                ],
                'summary' => $summary.' ¿Confirmas que lo haga?',
            ];
        }

        try {
            $result = ($definition['server'] ?? 'local') === 'local'
                ? $this->local->execute($name, $args, $context)
                : $this->mcp->call((string) $definition['server'], (string) $definition['remote_name'], $args);

            $result['tool'] = $name;
            $result['server'] = $definition['server'] ?? 'local';
            $result['summary'] = Str::limit((string) ($result['summary'] ?? ''), (int) config('chatbot.tools.max_result_chars', 5000), '…');

            $this->record($name, ($result['ok'] ?? false) ? 'ok' : 'error', $args, $context, $started, $result['summary'] ?? null, (string) ($definition['server'] ?? 'local'));

            return $result;
        } catch (\Throwable $exception) {
            $this->record($name, 'error', $args, $context, $started, Str::limit($exception->getMessage(), 200), (string) ($definition['server'] ?? 'local'));

            return [
                'ok' => false,
                'summary' => 'No pude completar esa acción en este momento. Puedes intentarlo de nuevo o escribir a soporte.',
            ];
        }
    }

    /**
     * Confirma o cancela una acción pendiente. El token es de un solo uso
     * y solo lo puede usar el dueño de la conversación que lo generó.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function confirm(string $token, bool $accept, array $context): array
    {
        $pending = Cache::get('chatbot.pending.'.$token);

        if (! is_array($pending)) {
            return [
                'ok' => false,
                'expired' => true,
                'summary' => 'La confirmación expiró o ya se usó. Vuelve a pedirme la acción y te la mostraré de nuevo.',
            ];
        }

        if (! hash_equals((string) ($pending['owner_key'] ?? ''), (string) $context['owner_key'])) {
            return [
                'ok' => false,
                'denied' => true,
                'summary' => 'No puedo confirmar una acción de otra conversación.',
            ];
        }

        if ((int) ($pending['user_id'] ?? 0) !== (int) ($context['user']?->id ?? 0)) {
            return [
                'ok' => false,
                'denied' => true,
                'summary' => 'No puedo confirmar una acción de otra cuenta.',
            ];
        }

        Cache::forget('chatbot.pending.'.$token);

        if (! $accept) {
            $this->record((string) $pending['tool'], 'cancelled', (array) $pending['args'], $context, hrtime(true), 'Cancelado por el usuario.');

            return [
                'ok' => true,
                'cancelled' => true,
                'summary' => 'Entendido. No ejecuté la acción. Si quieres, puedo ayudarte con otra cosa.',
            ];
        }

        return $this->execute(
            (string) $pending['tool'],
            (array) $pending['args'],
            $context,
            true
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $args
     */
    private function describeAction(array $definition, array $args): string
    {
        if (array_key_exists('asunto', $args) && ($definition['server'] ?? 'local') === 'local'
            && str_contains((string) ($definition['description'] ?? ''), 'correo')) {
            return sprintf(
                'Voy a enviar un correo a %s con el asunto «%s».',
                (string) ($args['destinatario'] ?? 'tu propio correo'),
                Str::limit((string) $args['asunto'], 120, '…')
            );
        }

        if (array_key_exists('asunto', $args)) {
            $prioridad = (string) ($args['prioridad'] ?? 'media');

            return sprintf(
                'Voy a abrir un caso de soporte con el asunto «%s» y prioridad %s.',
                Str::limit((string) $args['asunto'], 120, '…'),
                $prioridad
            );
        }

        if (array_key_exists('inicio', $args)) {
            return sprintf(
                'Voy a agendar «%s» para el %s.',
                Str::limit((string) ($args['titulo'] ?? 'evento'), 120, '…'),
                (string) $args['inicio']
            );
        }

        if (array_key_exists('etapa', $args) && array_key_exists('nombre', $args)) {
            return sprintf(
                'Voy a registrar a %s en el CRM en la etapa «%s».',
                Str::limit((string) $args['nombre'], 120, '…'),
                (string) $args['etapa']
            );
        }

        if (array_key_exists('titulo', $args) && array_key_exists('email', $args) && ! array_key_exists('asunto', $args)) {
            return sprintf(
                'Voy a enviar el aviso «%s» a %s en su bandeja de VoluntMatch.',
                Str::limit((string) $args['titulo'], 120, '…'),
                (string) $args['email']
            );
        }

        $label = $definition['remote_name'] ?? $definition['server'] ?? 'herramienta';

        return sprintf('Voy a ejecutar la acción «%s» con los datos que me diste.', $label);
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array<string, mixed>  $context
     */
    private function record(string $tool, string $status, array $args, array $context, int $started, ?string $summary = null, string $server = 'local'): void
    {
        if (! config('chatbot.observability.store', true)) {
            return;
        }

        try {
            ChatbotToolCall::create([
                'request_id' => (string) $context['request_id'],
                'conversation_id' => $context['conversation']?->id,
                'user_id' => $context['user']?->id,
                'tool' => Str::limit($tool, 100, ''),
                'server' => Str::limit($server, 50, ''),
                'arguments' => $this->redactArgs($args),
                'status' => $status,
                'latency_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
                'result_summary' => $summary !== null ? $this->guardrails->redact(Str::limit($summary, 500, '…')) : null,
                'error' => $status === 'error' ? $this->guardrails->redact(Str::limit((string) $summary, 300, '…')) : null,
            ]);
        } catch (\Throwable) {
            // La observabilidad nunca debe romper la conversación.
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function redactArgs(array $args): array
    {
        $clean = $this->redactValue($args, 0);

        return is_array($clean) ? $clean : [];
    }

    private function redactValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 4) {
            return '[truncado]';
        }

        if (is_string($value)) {
            return $this->guardrails->redact(Str::limit($value, 300, '…'));
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                if ($key === 'token') {
                    continue;
                }

                $clean[$key] = $this->redactValue($item, $depth + 1);
            }

            return $clean;
        }

        return $value;
    }
}
