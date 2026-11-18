<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Services\Chatbot\Mcp\ToolExecutor;
use App\Services\Chatbot\Mcp\ToolRegistry;
use App\Services\Chatbot\Rag\KnowledgeIndexer;
use App\Services\Chatbot\Rag\RagRetriever;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Orquestador del asistente.
 *
 * Orden de ejecución:
 *   1. contexto verificado por sesión (nunca por el mensaje);
 *   2. guardrails de entrada;
 *   3. router de intención;
 *   4. caché (solo respuestas públicas no personalizadas);
 *   5. RAG / herramientas / handoff;
 *   6. guardrails de salida;
 *   7. historial + traza + log estructurado.
 */
class ChatbotService
{
    public function __construct(
        private readonly GuardrailService $guardrails,
        private readonly PromptBuilder $prompts,
        private readonly IntentRouter $router,
        private readonly RagRetriever $rag,
        private readonly ResponseCache $cache,
        private readonly ToolRegistry $tools,
        private readonly ToolExecutor $toolExecutor,
        private readonly LlmClient $llm,
        private readonly LocalAnswerComposer $composer,
        private readonly ChatbotObserver $observer,
        private readonly KnowledgeIndexer $indexer,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('chatbot.enabled', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function respond(string $message, ?string $conversationId, Request $request): array
    {
        $started = hrtime(true);
        $requestId = (string) Str::uuid();
        $context = $this->context($request);
        $conversation = $this->resolveConversation($request, $conversationId, $context);

        $guard = $this->guardrails->inspectInput($message, $context['audience']);
        $text = $guard['text'];

        if ($text !== '') {
            $this->observer->userMessage($conversation, $requestId, $text, [
                'guardrails' => $guard,
            ]);
        }

        if (! $guard['allowed'] || $guard['action'] === 'handoff') {
            $composer = $guard['reason'] === 'self_harm'
                ? $this->composer->guardrailBlocked('self_harm', $context['audience'])
                : $this->composer->guardrailBlocked((string) $guard['reason'], $context['audience']);

            if ($guard['reason'] === 'self_harm' && $conversation->escalated_at === null) {
                $conversation->forceFill(['escalated_at' => now(), 'status' => 'escalated'])->save();
            }

            $route = $guard['reason'] === 'self_harm' ? 'handoff' : 'guardrail';
            $intent = $guard['reason'] === 'self_harm' ? 'seguridad' : 'guardrail';

            return $this->finish(
                $conversation,
                $context,
                $requestId,
                $started,
                $composer,
                [
                    'intent' => $intent,
                    'route' => $route,
                    'status' => $guard['reason'] === 'self_harm' ? 'ok' : 'blocked',
                    'guardrails' => $guard,
                    'model' => 'local',
                ]
            );
        }

        $router = $this->router->classify($text, $context['audience']);

        // Caché: únicamente invitados y respuestas de conocimiento. Nunca
        // se cachean datos de cuenta, herramientas ni acciones pendientes.
        $cacheKey = null;
        if ($this->cacheable($context, $router)) {
            $cacheKey = $this->cache->key(
                $text,
                $context['audience'],
                'rag',
                $this->modelLabel($router),
                $this->indexer->version()
            );

            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $this->finish(
                    $conversation,
                    $context,
                    $requestId,
                    $started,
                    $cached,
                    [
                        'intent' => $router['intent'],
                        'route' => 'rag',
                        'cache_hit' => true,
                        'guardrails' => $guard,
                        'model' => $this->modelLabel($router),
                    ]
                );
            }
        }

        try {
            $result = match ($router['route']) {
                'smalltalk' => $this->composer->smalltalk($router['intent'], $context['audience'], $context['user']),
                'handoff' => $this->handoffResult($context, $conversation),
                'refuse' => $this->composer->refuse($context['audience']),
                'tool' => $this->handleTool($router, $context, $conversation, $requestId),
                default => $this->handleRag($text, $context, $conversation, $requestId, $guard),
            };

            $result['model'] ??= $this->modelLabel($router);
            $result['cacheable'] = $cacheKey !== null
                && empty($result['pending'])
                && empty($result['tool_calls']);

            if ($cacheKey !== null && ($result['cacheable'] ?? false)) {
                $this->cache->put($cacheKey, $this->cachePayload($result));
            }

            return $this->finish(
                $conversation,
                $context,
                $requestId,
                $started,
                $result,
                [
                    'intent' => $router['intent'],
                    'route' => $router['route'],
                    'guardrails' => $guard,
                    'model' => $result['model'],
                    'retrieved' => $result['retrieved'] ?? null,
                    'tool_calls' => $result['tool_calls'] ?? null,
                    'tokens_in' => $result['tokens_in'] ?? 0,
                    'tokens_out' => $result['tokens_out'] ?? 0,
                ]
            );
        } catch (\Throwable $exception) {
            $error = $this->guardrails->redact(Str::limit($exception->getMessage(), 500));

            $this->observer->log('chatbot.error', [
                'request_id' => $requestId,
                'conversation_id' => $conversation->public_id,
                'audience' => $context['audience'],
                'route' => $router['route'],
                'message' => Str::limit($error, 200),
            ]);

            return $this->finish(
                $conversation,
                $context,
                $requestId,
                $started,
                $this->composer->error($context['audience']),
                [
                    'intent' => $router['intent'],
                    'route' => $router['route'],
                    'status' => 'error',
                    'guardrails' => $guard,
                    'error' => $error,
                    'model' => $this->modelLabel($router),
                ]
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function history(Request $request, ?string $conversationId = null): array
    {
        $context = $this->context($request);
        $conversation = $this->findConversation($request, $conversationId, $context);

        if (! $conversation) {
            return [
                'ok' => true,
                'conversation_id' => null,
                'messages' => [],
                'suggestions' => $this->composer->suggestions($context['audience']),
            ];
        }

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->limit((int) config('chatbot.router.history_limit', 20))
            ->get(['id', 'role', 'content', 'created_at', 'citations', 'metadata'])
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'text' => $message->content,
                'time' => $message->created_at?->format('H:i'),
                'sources' => $message->metadata['sources'] ?? [],
            ])
            ->values()
            ->all();

        return [
            'ok' => true,
            'conversation_id' => $conversation->public_id,
            'messages' => $messages,
            'suggestions' => $this->composer->suggestions($context['audience']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reset(Request $request): array
    {
        $context = $this->context($request);
        $conversation = $this->findConversation($request, null, $context);

        if ($conversation) {
            $conversation->forceFill(['status' => 'closed'])->save();
        }

        return [
            'ok' => true,
            'conversation_id' => null,
            'message' => 'Listo. Empezamos una conversación nueva.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirm(string $token, bool $accept, Request $request): array
    {
        $started = hrtime(true);
        $requestId = (string) Str::uuid();
        $context = $this->context($request);
        $conversation = $this->resolveConversation($request, null, $context);

        $this->observer->userMessage(
            $conversation,
            $requestId,
            $accept ? 'Confirmo la acción.' : 'Cancelo la acción.',
            ['intent' => 'confirmacion']
        );

        $result = $this->toolExecutor->confirm($token, $accept, [
            'user' => $context['user'],
            'audience' => $context['audience'],
            'conversation' => $conversation,
            'request_id' => $requestId,
            'owner_key' => $context['owner_key'],
            'api_client' => $context['api_client'] ?? null,
        ]);

        $composer = $accept
            ? $this->composer->tool($result, $context['audience'])
            : [
                'text' => (string) ($result['summary'] ?? 'Entendido. No ejecuté la acción.'),
                'suggestions' => $this->composer->suggestions($context['audience']),
            ];

        $composer['links'] = $result['links'] ?? [];
        $composer['pending'] = null;
        $composer['tool_calls'] = [[
            'tool' => $result['tool'] ?? 'confirmacion',
            'server' => $result['server'] ?? 'local',
            'status' => (bool) ($result['cancelled'] ?? false) ? 'cancelled' : (($result['ok'] ?? false) ? 'ok' : 'error'),
        ]];
        $composer['model'] = 'local';

        return $this->finish(
            $conversation,
            $context,
            $requestId,
            $started,
            $composer,
            [
                'intent' => 'confirmacion',
                'route' => 'tool',
                'status' => ($result['ok'] ?? false) ? 'ok' : 'error',
                'tool_calls' => $composer['tool_calls'],
                'guardrails' => ['flags' => []],
                'model' => 'local',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $router
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function handleTool(array $router, array $context, ChatbotConversation $conversation, string $requestId): array
    {
        if (($router['missing_args'] ?? []) !== []) {
            return $this->composer->clarify($router['missing_args'], $context['audience']);
        }

        $result = $this->toolExecutor->execute(
            (string) $router['tool'],
            (array) ($router['args'] ?? []),
            [
                'user' => $context['user'],
                'audience' => $context['audience'],
                'conversation' => $conversation,
                'request_id' => $requestId,
                'owner_key' => $context['owner_key'],
                'api_client' => $context['api_client'] ?? null,
            ]
        );

        $composer = $this->composer->tool($result, $context['audience']);
        $composer['links'] = $result['links'] ?? [];
        $composer['pending'] = $result['pending'] ?? null;
        if (! empty($composer['pending'])) {
            $composer['suggestions'] = ['Confirmar', 'Cancelar'];
        }
        $composer['tool_calls'] = [[
            'tool' => $router['tool'],
            'server' => $result['server'] ?? 'local',
            'status' => (bool) ($result['requires_confirmation'] ?? false)
                ? 'pending_confirmation'
                : (($result['ok'] ?? false) ? 'ok' : 'error'),
        ]];
        $composer['model'] = 'local';

        return $composer;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function handleRag(
        string $message,
        array $context,
        ChatbotConversation $conversation,
        string $requestId,
        array $guard
    ): array {
        $chunks = $this->rag->retrieve($message, $context['audience']);

        $sources = [];
        foreach ($chunks as $chunk) {
            $key = (string) ($chunk['slug'] ?? $chunk['title']);
            $sources[$key] = [
                'title' => (string) $chunk['title'],
                'slug' => (string) ($chunk['slug'] ?? ''),
                'score' => (float) ($chunk['score'] ?? 0),
            ];
        }
        $sources = array_values($sources);

        if ($this->llm->isConfigured() && $chunks !== []) {
            $llmResult = $this->runLlmWithTools($message, $context, $conversation, $requestId, $chunks);

            if ($llmResult !== null) {
                $llmResult['sources'] = $sources;
                $llmResult['links'] = $llmResult['links'] ?? [];
                $llmResult['retrieved'] = $this->retrievedSummary($chunks);

                return $llmResult;
            }
        }

        $composer = $this->composer->rag($chunks, $context['audience']);
        $composer['sources'] = $sources;
        $composer['links'] = [];
        $composer['retrieved'] = $this->retrievedSummary($chunks);
        $composer['tool_calls'] = [];
        $composer['model'] = 'local';

        return $composer;
    }

    /**
     * Bucle de function calling con herramientas autorizadas.
     *
     * @param  array<string, mixed>  $context
     * @param  list<array<string, mixed>>  $chunks
     * @return array<string, mixed>|null
     */
    private function runLlmWithTools(
        string $message,
        array $context,
        ChatbotConversation $conversation,
        string $requestId,
        array $chunks
    ): ?array {
        $toolDefs = $this->tools->llmTools($context['audience']);
        $toolNames = array_map(fn (array $tool) => (string) ($tool['function']['name'] ?? ''), $toolDefs);

        $messages = [
            ['role' => 'system', 'content' => $this->prompts->system($context['audience'], $context['user'], $toolNames)],
            ['role' => 'user', 'content' => $this->prompts->userMessage($message, $chunks)],
        ];

        $tokensIn = 0;
        $tokensOut = 0;
        $model = null;
        $toolCalls = [];
        $links = [];
        $executed = 0;
        $max = max(1, (int) config('chatbot.tools.max_per_turn', 2));
        $pending = null;

        try {
            for ($turn = 0; $turn < 3; $turn++) {
                $response = $this->llm->chat(
                    $messages,
                    $toolDefs,
                    $this->llm->modelFor($turn === 0 ? 'small' : 'large')
                );

                $tokensIn += $response['tokens_in'];
                $tokensOut += $response['tokens_out'];
                $model = $response['model'];

                if ($response['tool_calls'] === []) {
                    $text = trim($response['content']);

                    if ($text === '') {
                        return null;
                    }

                    return [
                        'text' => $text,
                        'suggestions' => $this->composer->suggestions($context['audience']),
                        'links' => $links,
                        'tool_calls' => $toolCalls,
                        'tokens_in' => $tokensIn,
                        'tokens_out' => $tokensOut,
                        'model' => $model,
                        'pending' => null,
                    ];
                }

                $assistantCalls = [];
                foreach ($response['tool_calls'] as $call) {
                    $assistantCalls[] = [
                        'id' => $call['id'] ?: 'call_'.Str::random(12),
                        'type' => 'function',
                        'function' => [
                            'name' => $call['name'],
                            'arguments' => json_encode($call['arguments'], JSON_UNESCAPED_UNICODE),
                        ],
                    ];
                }

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response['content'] !== '' ? $response['content'] : null,
                    'tool_calls' => $assistantCalls,
                ];

                foreach ($response['tool_calls'] as $index => $call) {
                    if ($executed >= $max) {
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $assistantCalls[$index]['id'],
                            'content' => json_encode(['ok' => false, 'summary' => 'Límite de herramientas por turno alcanzado.']),
                        ];

                        continue;
                    }

                    $executed++;

                    $result = $this->toolExecutor->execute(
                        (string) $call['name'],
                        is_array($call['arguments']) ? $call['arguments'] : [],
                        [
                            'user' => $context['user'],
                            'audience' => $context['audience'],
                            'conversation' => $conversation,
                            'request_id' => $requestId,
                            'owner_key' => $context['owner_key'],
                            'api_client' => $context['api_client'] ?? null,
                        ]
                    );

                    $toolCalls[] = [
                        'tool' => (string) $call['name'],
                        'server' => $result['server'] ?? 'local',
                        'status' => (bool) ($result['requires_confirmation'] ?? false)
                            ? 'pending_confirmation'
                            : (($result['ok'] ?? false) ? 'ok' : 'error'),
                    ];

                    foreach (($result['links'] ?? []) as $link) {
                        if (is_array($link) && isset($link['url'], $link['label'])) {
                            $links[] = ['label' => (string) $link['label'], 'url' => (string) $link['url']];
                        }
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $assistantCalls[$index]['id'],
                        'content' => json_encode([
                            'ok' => (bool) ($result['ok'] ?? false),
                            'summary' => Str::limit((string) ($result['summary'] ?? ''), 2000, '…'),
                            'data' => $result['data'] ?? null,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];

                    if (! empty($result['requires_confirmation']) && ! empty($result['pending'])) {
                        $pending = $result['pending'];

                        return [
                            'text' => (string) ($result['summary'] ?? '¿Confirmas la acción?'),
                            'suggestions' => ['Confirmar', 'Cancelar'],
                            'links' => $links,
                            'tool_calls' => $toolCalls,
                            'tokens_in' => $tokensIn,
                            'tokens_out' => $tokensOut,
                            'model' => $model,
                            'pending' => $pending,
                        ];
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->observer->log('chatbot.llm_fallback', [
                'request_id' => $requestId,
                'message' => Str::limit($exception->getMessage(), 200),
            ]);

            return null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function handoffResult(array $context, ChatbotConversation $conversation): array
    {
        if ($conversation->escalated_at === null) {
            $conversation->forceFill(['escalated_at' => now(), 'status' => 'escalated'])->save();
        }

        $result = $this->composer->handoff($context['audience'], $context['user']);
        $result['links'] = [
            ['label' => 'Formulario de contacto', 'url' => route('contacto')],
        ];

        if (! $context['user']) {
            $result['links'][] = ['label' => 'Iniciar sesión', 'url' => route('login')];
            $result['links'][] = ['label' => 'Crear cuenta', 'url' => route('registro-voluntario')];
        }

        $result['sources'] = [];
        $result['tool_calls'] = [];
        $result['model'] = 'local';

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $trace
     * @return array<string, mixed>
     */
    private function finish(
        ChatbotConversation $conversation,
        array $context,
        string $requestId,
        int $started,
        array $result,
        array $trace
    ): array {
        $output = $this->guardrails->inspectOutput((string) ($result['text'] ?? ''));
        $text = $output['text'];

        $guardrailFlags = array_values(array_unique(array_merge(
            (array) ($trace['guardrails']['flags'] ?? []),
            $output['flags']
        )));

        $sources = $result['sources'] ?? [];
        $links = array_values(array_filter(
            (array) ($result['links'] ?? []),
            fn ($link) => is_array($link) && isset($link['label'], $link['url'])
        ));
        $suggestions = $result['suggestions'] ?? $this->composer->suggestions($context['audience']);
        $pending = $result['pending'] ?? null;
        $toolCalls = $result['tool_calls'] ?? [];

        $this->observer->assistantMessage($conversation, $requestId, $text, [
            'intent' => $trace['intent'] ?? null,
            'route' => $trace['route'] ?? null,
            'citations' => $sources,
            'tool_calls' => $toolCalls,
            'guardrails' => $guardrailFlags,
            'meta' => ['sources' => $sources, 'links' => $links],
        ]);

        $conversation->forceFill([
            'last_activity_at' => now(),
            'role' => $context['audience'],
            'user_id' => $context['user']?->id,
        ])->save();

        $latency = (int) round((hrtime(true) - $started) / 1_000_000);

        $this->observer->trace([
            'request_id' => $requestId,
            'conversation_id' => $conversation->id,
            'user_id' => $context['user']?->id,
            'audience' => $context['audience'],
            'intent' => $trace['intent'] ?? null,
            'route' => $trace['route'] ?? null,
            'model' => $trace['model'] ?? null,
            'cache_hit' => (bool) ($trace['cache_hit'] ?? false),
            'latency_ms' => $latency,
            'tokens_in' => (int) ($trace['tokens_in'] ?? 0),
            'tokens_out' => (int) ($trace['tokens_out'] ?? 0),
            'retrieved' => $trace['retrieved'] ?? null,
            'tool_calls' => $toolCalls,
            'guardrails' => [
                'action' => $trace['status'] ?? 'ok',
                'flags' => $guardrailFlags,
            ],
            'status' => $trace['status'] ?? 'ok',
            'error' => $trace['error'] ?? null,
            'ip_hash' => $context['ip_hash'],
        ]);

        $this->observer->log('chatbot.reply', [
            'request_id' => $requestId,
            'conversation_id' => $conversation->public_id,
            'audience' => $context['audience'],
            'intent' => $trace['intent'] ?? null,
            'route' => $trace['route'] ?? null,
            'status' => $trace['status'] ?? 'ok',
            'cache_hit' => (bool) ($trace['cache_hit'] ?? false),
            'latency_ms' => $latency,
            'guardrail_flags' => $guardrailFlags,
            'tools' => array_map(fn (array $call) => $call['tool'] ?? null, $toolCalls),
        ]);

        return [
            'ok' => ($trace['status'] ?? 'ok') !== 'error',
            'conversation_id' => $conversation->public_id,
            'request_id' => $requestId,
            'message' => $text,
            'sources' => $sources,
            'links' => array_values(array_slice($links, 0, 6)),
            'suggestions' => array_values(array_slice($suggestions, 0, 4)),
            'pending' => $pending,
            'meta' => [
                'intent' => $trace['intent'] ?? null,
                'route' => $trace['route'] ?? null,
                'cache_hit' => (bool) ($trace['cache_hit'] ?? false),
                'latency_ms' => $latency,
                'guardrails' => $guardrailFlags,
                'request_id' => $requestId,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function cachePayload(array $result): array
    {
        return [
            'text' => (string) ($result['text'] ?? ''),
            'sources' => $result['sources'] ?? [],
            'links' => $result['links'] ?? [],
            'suggestions' => $result['suggestions'] ?? [],
            'retrieved' => $result['retrieved'] ?? null,
            'tool_calls' => [],
            'tokens_in' => 0,
            'tokens_out' => 0,
            'model' => $result['model'] ?? 'local',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $router
     */
    private function cacheable(array $context, array $router): bool
    {
        return (bool) config('chatbot.cache.enabled', true)
            && $context['user'] === null
            && $context['audience'] === 'public'
            && $router['route'] === 'rag';
    }

    /**
     * @param  array<string, mixed>  $router
     */
    private function modelLabel(array $router): string
    {
        if (! $this->llm->isConfigured()) {
            return 'local';
        }

        return $this->llm->modelFor('small');
    }

    /**
     * @param  list<array<string, mixed>>  $chunks
     * @return list<array<string, mixed>>
     */
    private function retrievedSummary(array $chunks): array
    {
        return array_map(fn (array $chunk) => [
            'slug' => (string) ($chunk['slug'] ?? ''),
            'title' => (string) ($chunk['title'] ?? ''),
            'score' => (float) ($chunk['score'] ?? 0),
            'confidence' => (float) ($chunk['confidence'] ?? 0),
        ], $chunks);
    }

    /**
     * @return array{user: mixed, audience: string, owner_key: string, ip_hash: string, api_client: mixed, channel: string}
     */
    private function context(Request $request): array
    {
        $user = $request->user();
        $audience = $user?->role ?? 'public';

        if (! in_array($audience, ['public', 'voluntario', 'organizacion', 'admin'], true)) {
            $audience = 'public';
        }

        $apiClient = $request->attributes->get('api_client');

        $ownerKey = $apiClient
            ? 'api:'.$apiClient->id
            : ($user
                ? 'user:'.$user->id
                : 'session:'.substr(hash('sha256', $request->hasSession() ? $request->session()->getId() : $request->ip()), 0, 56));

        return [
            'user' => $user,
            'audience' => $audience,
            'owner_key' => $ownerKey,
            'ip_hash' => $this->guardrails->ipHash((string) $request->ip()),
            'api_client' => $apiClient,
            'channel' => $apiClient ? 'api' : 'web',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveConversation(Request $request, ?string $conversationId, array $context): ChatbotConversation
    {
        $conversation = $this->findConversation($request, $conversationId, $context);

        if ($conversation && $conversation->status !== 'closed') {
            return $conversation;
        }

        return ChatbotConversation::create([
            'user_id' => $context['user']?->id,
            'owner_key' => $context['owner_key'],
            'role' => $context['audience'],
            'channel' => $context['channel'] ?? 'web',
            'status' => 'active',
            'last_activity_at' => now(),
            'metadata' => [
                'user_agent' => Str::limit((string) $request->userAgent(), 180, ''),
                'api_client_id' => $context['api_client']?->id,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function findConversation(Request $request, ?string $conversationId, array $context): ?ChatbotConversation
    {
        if ($conversationId !== null && $conversationId !== '') {
            $conversation = ChatbotConversation::query()
                ->where('public_id', $conversationId)
                ->where('owner_key', $context['owner_key'])
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return ChatbotConversation::query()
            ->where('owner_key', $context['owner_key'])
            ->where('status', '!=', 'closed')
            ->where('last_activity_at', '>=', now()->subDay())
            ->latest('last_activity_at')
            ->first();
    }
}
