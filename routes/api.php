<?php

use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\McpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 del asistente y servidor MCP
|--------------------------------------------------------------------------
|
| Autenticación: Authorization: Bearer vm_…
| Cada token tiene rol, scopes y límites propios. El rol nunca lo decide
| el cuerpo de la petición.
|
*/

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/health', fn () => response()->json([
        'ok' => true,
        'service' => 'voluntmatch-assistant',
        'version' => '1.0.0',
        'mcp' => (bool) config('api.mcp.enabled', true),
    ]))->name('health');

    Route::middleware(['throttle:api-ip'])->group(function () {
        Route::middleware(['api.client:assistant:chat', 'throttle:api'])->group(function () {
            Route::post('/assistant/messages', [AssistantController::class, 'mensaje'])->name('assistant.messages');
            Route::get('/assistant/history', [AssistantController::class, 'historial'])->name('assistant.history');
            Route::post('/assistant/reset', [AssistantController::class, 'reiniciar'])->name('assistant.reset');
            Route::post('/chat/completions', [AssistantController::class, 'completions'])->name('chat.completions');
        });

        Route::middleware(['api.client:assistant:confirm', 'throttle:api'])
            ->post('/assistant/confirm', [AssistantController::class, 'confirmar'])
            ->name('assistant.confirm');

        Route::middleware(['api.client:assistant:tools', 'throttle:api'])->group(function () {
            Route::get('/tools', [AssistantController::class, 'herramientas'])->name('tools.index');
            Route::post('/tools/{tool}', [AssistantController::class, 'invocar'])->name('tools.invoke');
        });

        Route::middleware(['api.client:mcp:use', 'throttle:api'])
            ->match(['get', 'post'], '/mcp', McpController::class)
            ->name('mcp');
    });
});
