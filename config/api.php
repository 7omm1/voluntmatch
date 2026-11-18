<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API pública del asistente
    |--------------------------------------------------------------------------
    |
    | Permite que otros sistemas (CRM, mesa de ayuda, apps internas, agentes
    | MCP) conversen con Vera sin pasar por la sesión web. Cada consumidor
    | usa un token propio con rol, scopes y límites independientes.
    |
    */
    'enabled' => (bool) env('API_ENABLED', true),

    'token_prefix' => env('API_TOKEN_PREFIX', 'vm_'),

    'rate_limits' => [
        'per_minute' => (int) env('API_RATE_PER_MINUTE', 60),
        'per_day' => (int) env('API_RATE_PER_DAY', 2000),
    ],

    'mcp' => [
        'enabled' => (bool) env('MCP_SERVER_ENABLED', true),
        'protocol_version' => env('MCP_SERVER_PROTOCOL', '2025-06-18'),
        'server_name' => env('MCP_SERVER_NAME', 'voluntmatch'),
        'server_version' => '1.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | assistant:chat   -> POST /messages, historial y reinicio.
    | assistant:tools  -> listar e invocar herramientas directamente.
    | assistant:confirm-> confirmar acciones pendientes (escrituras).
    | mcp:use          -> endpoint MCP (JSON-RPC) para agentes externos.
    |
    */
    'scopes' => [
        'assistant:chat',
        'assistant:tools',
        'assistant:confirm',
        'mcp:use',
    ],

];
