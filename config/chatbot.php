<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interruptor general
    |--------------------------------------------------------------------------
    |
    | El asistente se puede desactivar sin tocar el resto de la aplicación.
    | Los controladores devuelven 503 con un mensaje amable si está apagado.
    |
    */
    'enabled' => (bool) env('CHATBOT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Proveedor del modelo
    |--------------------------------------------------------------------------
    |
    | 'local' responde con recuperación RAG extractiva y no necesita ninguna
    | API externa. 'openai' usa cualquier endpoint compatible con Chat
    | Completions (OpenAI, Azure OpenAI, Ollama, vLLM, etc.).
    |
    */
    'driver' => env('CHATBOT_DRIVER', 'local'),

    'llm' => [
        'base_url' => rtrim((string) env('CHATBOT_LLM_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'api_key' => env('CHATBOT_LLM_API_KEY'),
        'model' => env('CHATBOT_LLM_MODEL', 'gpt-4.1-mini'),
        'model_small' => env('CHATBOT_LLM_MODEL_SMALL', env('CHATBOT_LLM_MODEL', 'gpt-4.1-mini')),
        'model_large' => env('CHATBOT_LLM_MODEL_LARGE', env('CHATBOT_LLM_MODEL', 'gpt-4.1-mini')),
        'embedding_model' => env('CHATBOT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'temperature' => (float) env('CHATBOT_LLM_TEMPERATURE', 0.2),
        'max_tokens' => (int) env('CHATBOT_LLM_MAX_TOKENS', 700),
        'timeout' => (int) env('CHATBOT_LLM_TIMEOUT', 20),
        'connect_timeout' => (int) env('CHATBOT_LLM_CONNECT_TIMEOUT', 5),
        'retries' => (int) env('CHATBOT_LLM_RETRIES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | System prompt
    |--------------------------------------------------------------------------
    |
    | La versión forma parte de las claves de caché. Al cambiarla se
    | invalida automáticamente todo lo que se respondió con el prompt viejo.
    | El canario permite detectar fugas del prompt en la respuesta.
    |
    */
    'prompt' => [
        'name' => 'Vera',
        'version' => '2026-11-18.2',
        'canary' => 'VM-ASSISTANT-2026-INTERNAL',
        'max_input_chars' => (int) env('CHATBOT_MAX_INPUT_CHARS', 1200),
        'max_output_chars' => (int) env('CHATBOT_MAX_OUTPUT_CHARS', 2400),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG
    |--------------------------------------------------------------------------
    |
    | El recuperador local usa BM25 sobre fragmentos del corpus y no manda
    | documentos a terceros. Si hay embeddings configurados, se combinan
    | con la búsqueda léxica.
    |
    */
    'rag' => [
        'enabled' => (bool) env('CHATBOT_RAG_ENABLED', true),
        'top_k' => (int) env('CHATBOT_RAG_TOP_K', 4),
        'min_confidence' => (float) env('CHATBOT_RAG_MIN_CONFIDENCE', 0.2),
        'max_corpus_chunks' => (int) env('CHATBOT_RAG_MAX_CHUNKS', 800),
        'chunk_chars' => (int) env('CHATBOT_RAG_CHUNK_CHARS', 700),
        'chunk_overlap' => (int) env('CHATBOT_RAG_CHUNK_OVERLAP', 90),
        'cache_ttl' => (int) env('CHATBOT_RAG_CACHE_TTL', 600),
        'embeddings' => (bool) env('CHATBOT_RAG_EMBEDDINGS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caché de respuestas
    |--------------------------------------------------------------------------
    |
    | Nunca se cachean respuestas con datos personales, resultados de
    | herramientas ni acciones pendientes de confirmación. Las claves
    | incluyen versión del prompt, versión del conocimiento y audiencia.
    |
    */
    'cache' => [
        'enabled' => (bool) env('CHATBOT_CACHE_ENABLED', true),
        'store' => env('CHATBOT_CACHE_STORE'),
        'prefix' => env('CHATBOT_CACHE_PREFIX', 'chatbot'),
        'ttl' => (int) env('CHATBOT_CACHE_TTL', 300),
        'pending_ttl' => (int) env('CHATBOT_PENDING_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites de uso
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'per_minute' => (int) env('CHATBOT_RATE_PER_MINUTE', 12),
        'per_day' => (int) env('CHATBOT_RATE_PER_DAY', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Herramientas MCP
    |--------------------------------------------------------------------------
    |
    | Los servidores se declaran aquí, nunca a partir de una URL enviada por
    | el usuario. Cada servidor debe indicar explícitamente qué herramientas
    | expone: por defecto no se confía en ninguna. Para producción usa URLs
    | HTTPS y guarda el token en una variable de entorno.
    |
    | Formato:
    | 'servers' => [
    |     'documentacion' => [
    |         'url' => 'https://mcp.ejemplo.org/mcp',
    |         'token' => env('CHATBOT_MCP_DOCS_TOKEN'),
    |         'allowed_tools' => ['buscar_documento'],
    |         'audiences' => ['admin'],
    |         'read_only' => true,
    |         'timeout' => 8,
    |     ],
    | ],
    |
    */
    'mcp' => [
        'enabled' => (bool) env('CHATBOT_MCP_ENABLED', false),
        'protocol_version' => '2025-06-18',
        'timeout' => (int) env('CHATBOT_MCP_TIMEOUT', 8),
        'tool_cache_ttl' => (int) env('CHATBOT_MCP_TOOL_CACHE_TTL', 300),
        'max_result_chars' => (int) env('CHATBOT_MCP_MAX_RESULT_CHARS', 6000),
        'allow_insecure' => (bool) env('CHATBOT_MCP_ALLOW_INSECURE', false),
        'servers' => json_decode((string) env('CHATBOT_MCP_SERVERS', '{}'), true) ?: [],
    ],

    'tools' => [
        'max_per_turn' => (int) env('CHATBOT_TOOLS_MAX_PER_TURN', 2),
        'max_result_chars' => (int) env('CHATBOT_TOOLS_MAX_RESULT_CHARS', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Observabilidad
    |--------------------------------------------------------------------------
    |
    | Las trazas guardan metadatos, no el contenido de los mensajes. El
    | contenido de la conversación se guarda aparte para poder mostrar el
    | historial al usuario. Se puede desactivar el guardado de contenido.
    |
    */
    'observability' => [
        'store' => (bool) env('CHATBOT_OBSERVABILITY', true),
        'store_message_content' => (bool) env('CHATBOT_STORE_CONTENT', true),
        'log_channel' => env('CHATBOT_LOG_CHANNEL'),
        'anonymize_ip' => (bool) env('CHATBOT_ANONYMIZE_IP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Router
    |--------------------------------------------------------------------------
    |
    | El router determinista decide primero y, si la consulta es ambigua,
    | puede pedir una clasificación al modelo pequeño. Nunca autoriza
    | herramientas: la autorización se comprueba otra vez en el servidor.
    |
    */
    'router' => [
        'llm_fallback' => (bool) env('CHATBOT_ROUTER_LLM', false),
        'history_limit' => (int) env('CHATBOT_HISTORY_LIMIT', 20),
    ],

];
