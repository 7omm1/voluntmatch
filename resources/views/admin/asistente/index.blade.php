@extends('layouts.admin')

@section('title', 'Asistente — VoluntMatch')
@section('panel-seccion', 'ASISTENTE')
@section('panel-titulo', 'Salud, seguridad y uso del asistente.')

@php
    $maxActividad = max(1, $actividad->max('total'));
    $maxIntencion = max(1, $intenciones->max('total'));
    $maxHerramienta = max(1, $herramientas->max('total'));
    $maxRuta = max(1, $rutas->max('total'));
@endphp

@section('admin-content')
    <div class="metric-grid metric-grid-four">
        <article>
            <span><x-icon name="chat" /></span>
            <strong>{{ number_format($metricas['consultas'], 0, ',', '.') }}</strong>
            <p>Consultas registradas</p>
            <small>{{ $metricas['conversaciones'] }} conversaciones · {{ number_format($metricas['mensajes'], 0, ',', '.') }} mensajes</small>
        </article>
        <article>
            <span><x-icon name="spark" /></span>
            <strong>{{ $metricas['tasa_cache'] }}%</strong>
            <p>Aciertos de caché</p>
            <small>{{ $metricas['cache_hits'] }} respuestas reutilizadas</small>
        </article>
        <article>
            <span><x-icon name="clock" /></span>
            <strong>{{ $metricas['latencia_media'] }} ms</strong>
            <p>Latencia media</p>
            <small>P95: {{ $metricas['latencia_p95'] }} ms · {{ $metricas['herramientas'] }} herramientas</small>
        </article>
        <article>
            <span><x-icon name="check" /></span>
            <strong>{{ $metricas['deflexion'] }}%</strong>
            <p>Resueltas sin derivar</p>
            <small>{{ $metricas['handoffs'] }} derivaciones · {{ $metricas['bloqueos'] }} bloqueos de guardrail</small>
        </article>
    </div>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>VOLUMEN</p>
                <h3>Consultas de los últimos 7 días</h3>
            </div>
        </div>
        <div class="master-chart">
            @foreach ($actividad as $dia)
                <span style="height: {{ max(4, (int) round($dia['total'] / $maxActividad * 100)) }}%"
                    title="{{ $dia['label'] }}: {{ $dia['total'] }} consultas"></span>
            @endforeach
        </div>
        <div class="admin-chart-labels">
            @foreach ($actividad as $dia)
                <span>{{ $dia['label'] }} · {{ $dia['total'] }}</span>
            @endforeach
        </div>
    </section>

    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>ROUTER</p><h3>Intenciones detectadas</h3></div>
            </div>
            <div class="horizontal-chart">
                @forelse ($intenciones as $intencion)
                    <div>
                        <span>{{ $intencion->etiqueta }}</span>
                        <i><b style="width: {{ round($intencion->total / $maxIntencion * 100) }}%"></b></i>
                        <em>{{ $intencion->total }}</em>
                    </div>
                @empty
                    <p class="admin-empty">Aún no hay consultas registradas.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>RUTAS</p><h3>Resolución por capa</h3></div>
            </div>
            <div class="horizontal-chart">
                @forelse ($rutas as $ruta)
                    <div>
                        <span>{{ $ruta->etiqueta }}</span>
                        <i><b style="width: {{ round($ruta->total / $maxRuta * 100) }}%"></b></i>
                        <em>{{ $ruta->total }}</em>
                    </div>
                @empty
                    <p class="admin-empty">Sin rutas ejecutadas.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>MCP / HERRAMIENTAS</p><h3>Herramientas más usadas</h3></div>
            </div>
            <div class="horizontal-chart">
                @forelse ($herramientas as $herramienta)
                    <div>
                        <span>{{ $herramienta->tool }}</span>
                        <i><b style="width: {{ round($herramienta->total / $maxHerramienta * 100) }}%"></b></i>
                        <em>{{ $herramienta->total }}</em>
                    </div>
                @empty
                    <p class="admin-empty">Todavía no se invocaron herramientas.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>SEGURIDAD</p><h3>Controles activos</h3></div>
            </div>
            <div class="admin-row compact">
                <span class="status activo">Activo</span>
                <span class="admin-row-main"><b>Guardrails de entrada y salida</b><small>Prompt injection, PII, secretos, HTML y canario.</small></span>
            </div>
            <div class="admin-row compact">
                <span class="status activo">Activo</span>
                <span class="admin-row-main"><b>Acceso por audiencia</b><small>Herramientas y documentos filtrados por rol verificado en sesión.</small></span>
            </div>
            <div class="admin-row compact">
                <span class="status {{ config('chatbot.mcp.enabled') ? 'activo' : 'pendiente' }}">
                    {{ config('chatbot.mcp.enabled') ? 'Activo' : 'Desactivado' }}
                </span>
                <span class="admin-row-main"><b>Servidores MCP</b><small>Solo URLs y herramientas declaradas en configuración.</small></span>
            </div>
            <div class="admin-row compact">
                <span class="status {{ config('chatbot.cache.enabled') ? 'activo' : 'pendiente' }}">
                    {{ config('chatbot.cache.enabled') ? 'Activo' : 'Desactivado' }}
                </span>
                <span class="admin-row-main"><b>Caché segura</b><small>Solo respuestas públicas no personalizadas; no se cachean herramientas ni confirmaciones.</small></span>
            </div>
        </section>
    </div>

    <section class="dashboard-card table-card">
        <div class="card-heading">
            <div><p>TRAZAS</p><h3>Últimas consultas</h3></div>
            <span>{{ $metricas['consultas'] }} totales</span>
        </div>

        @forelse ($recientes as $traza)
            @php
                $flags = (array) ($traza->guardrails['flags'] ?? []);
                $estado = $traza->status === 'blocked' ? 'rechazada' : ($traza->status === 'error' ? 'pendiente' : 'activo');
            @endphp
            <article class="admin-row compact">
                <span class="status {{ $estado }}">{{ $traza->status }}</span>
                <span class="admin-row-main">
                    <b>{{ $traza->intent ?? 'sin intención' }} · {{ $traza->route ?? 'sin ruta' }}</b>
                    <small>
                        {{ $traza->user?->name ?? 'Visitante' }}
                        @if ($traza->user)
                            ({{ $traza->user->role }})
                        @endif
                        · {{ $traza->created_at?->format('d/m/Y H:i') }}
                        · {{ $traza->latency_ms }} ms
                        @if ($traza->cache_hit) · caché @endif
                        @if ($flags !== []) · flags: {{ implode(', ', $flags) }} @endif
                    </small>
                </span>
                <span class="priority {{ $traza->status === 'blocked' ? 'alta' : 'baja' }}">
                    {{ $traza->model ?? 'local' }}
                </span>
                <em title="{{ $traza->request_id }}">#{{ substr($traza->request_id, 0, 8) }}</em>
            </article>
        @empty
            <p class="admin-empty">Aún no hay consultas. El widget está disponible en toda la web.</p>
        @endforelse
    </section>
@endsection
