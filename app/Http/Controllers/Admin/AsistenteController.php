<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotToolCall;
use App\Models\ChatbotTrace;
use App\Services\Chatbot\LlmClient;
use Illuminate\Support\Collection;

/**
 * Observabilidad del asistente para el equipo de administración.
 * No muestra el contenido de las conversaciones: solo metadatos,
 * rendimiento, rutas, caché, herramientas y bloqueos de seguridad.
 */
class AsistenteController extends Controller
{
    public function index()
    {
        $llm = app(LlmClient::class);
        $totalTrazas = ChatbotTrace::count();
        $ultimas = ChatbotTrace::query()->latest()->limit(500)->get();
        $latencies = $ultimas->pluck('latency_ms')->sort()->values();

        $cacheHits = ChatbotTrace::where('cache_hit', true)->count();
        $handoffs = ChatbotTrace::where('route', 'handoff')->count();
        $bloqueos = $ultimas->filter(function (ChatbotTrace $trace) {
            $flags = (array) ($trace->guardrails['flags'] ?? []);

            return in_array('prompt_injection', $flags, true)
                || in_array('illegal', $flags, true)
                || $trace->status === 'blocked';
        })->count();

        return view('admin.asistente.index', [
            'metricas' => [
                'conversaciones' => ChatbotConversation::count(),
                'mensajes' => ChatbotMessage::count(),
                'consultas' => $totalTrazas,
                'cache_hits' => $cacheHits,
                'tasa_cache' => $totalTrazas > 0 ? round($cacheHits / $totalTrazas * 100) : 0,
                'handoffs' => $handoffs,
                'deflexion' => $totalTrazas > 0 ? round((1 - $handoffs / $totalTrazas) * 100) : 0,
                'latencia_media' => (int) round($latencies->avg() ?? 0),
                'latencia_p95' => (int) ($latencies->isEmpty() ? 0 : $latencies[(int) floor(($latencies->count() - 1) * 0.95)]),
                'bloqueos' => $bloqueos,
                'herramientas' => ChatbotToolCall::count(),
                'proveedor' => [
                    'driver' => $llm->driver(),
                    'modelo' => $llm->isConfigured() ? $llm->modelFor('small') : 'local',
                    'configurado' => $llm->isConfigured(),
                ],
            ],
            'intenciones' => $this->agrupado('intent'),
            'rutas' => $this->agrupado('route'),
            'herramientas' => ChatbotToolCall::query()
                ->selectRaw('tool, count(*) as total')
                ->groupBy('tool')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'actividad' => $this->actividadDiaria(),
            'recientes' => ChatbotTrace::query()
                ->with(['user:id,name,role', 'conversation:id,public_id,role'])
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function agrupado(string $columna): Collection
    {
        return ChatbotTrace::query()
            ->selectRaw("{$columna} as etiqueta, count(*) as total")
            ->whereNotNull($columna)
            ->groupBy($columna)
            ->orderByDesc('total')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, array{label: string, total: int}>
     */
    private function actividadDiaria(): Collection
    {
        $desde = now()->subDays(6)->startOfDay();

        $totales = ChatbotTrace::query()
            ->where('created_at', '>=', $desde)
            ->pluck('created_at')
            ->countBy(fn ($fecha) => $fecha->format('Y-m-d'));

        return collect(range(0, 6))->map(function (int $dia) use ($desde, $totales) {
            $fecha = $desde->copy()->addDays($dia);

            return [
                'label' => $fecha->translatedFormat('d M'),
                'total' => (int) ($totales[$fecha->format('Y-m-d')] ?? 0),
            ];
        });
    }
}
