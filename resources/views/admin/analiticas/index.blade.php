@extends('layouts.admin')

@section('title', 'Analíticas — VoluntMatch')
@section('panel-seccion', 'ANALÍTICAS')
@section('panel-titulo', 'Qué está pasando en el ecosistema.')

@php
    $maxMes = max(1, $meses->max('total'));
    $maxCausa = max(1, $porCausa->max('total'));
    $maxHabilidad = max(1, $habilidades->max('total'));
    $maxRegion = max(1, $regiones->max('total'));
    $totalUsuarios = $usuariosPorRol->sum();
    $totalPostulaciones = $postulacionesPorEstado->sum();
@endphp

@section('admin-content')
    <div class="metric-grid metric-grid-four">
        <article>
            <span><x-icon name="users" /></span>
            <strong>{{ number_format($totalUsuarios, 0, ',', '.') }}</strong>
            <p>Cuentas totales</p>
            <small>{{ $usuariosPorRol->get('voluntario', 0) }} voluntarios · {{ $usuariosPorRol->get('organizacion', 0) }} ONG</small>
        </article>
        <article>
            <span><x-icon name="heart" /></span>
            <strong>{{ number_format($totalPostulaciones, 0, ',', '.') }}</strong>
            <p>Postulaciones</p>
            <small>{{ $conversion['aceptadas'] }} aceptadas</small>
        </article>
        <article>
            <span><x-icon name="spark" /></span>
            <strong>{{ $conversion['tasa'] }}%</strong>
            <p>Tasa de aceptación</p>
            <small>Postulaciones que terminan en match</small>
        </article>
        <article>
            <span><x-icon name="check" /></span>
            <strong>{{ $organizaciones['verificadas'] }}/{{ $organizaciones['total'] }}</strong>
            <p>ONG verificadas</p>
            <small>{{ $organizaciones['pendientes'] }} pendientes</small>
        </article>
    </div>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>CRECIMIENTO</p>
                <h3>Altas mensuales de los últimos 6 meses</h3>
            </div>
        </div>
        <div class="master-chart">
            @foreach ($meses as $mes)
                <span style="height: {{ max(4, (int) round($mes['total'] / $maxMes * 100)) }}%"
                    title="{{ $mes['label'] }}: {{ $mes['total'] }} registros"></span>
            @endforeach
        </div>
        <div class="admin-chart-labels">
            @foreach ($meses as $mes)
                <span>{{ $mes['label'] }} · {{ $mes['total'] }}</span>
            @endforeach
        </div>
    </section>

    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>DEMANDA</p><h3>Causas con más oportunidades</h3></div>
            </div>
            <div class="horizontal-chart">
                @forelse ($porCausa as $causa)
                    <div>
                        <span>{{ $causa->causa }}</span>
                        <i><b style="width: {{ round($causa->total / $maxCausa * 100) }}%"></b></i>
                        <em>{{ $causa->total }}</em>
                    </div>
                @empty
                    <p class="admin-empty">Sin datos todavía.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>TALENTO</p><h3>Habilidades más solicitadas</h3></div>
            </div>
            <div class="horizontal-chart">
                @forelse ($habilidades as $habilidad)
                    <div>
                        <span>{{ $habilidad->habilidad }}</span>
                        <i><b style="width: {{ round($habilidad->total / $maxHabilidad * 100) }}%"></b></i>
                        <em>{{ $habilidad->total }}</em>
                    </div>
                @empty
                    <p class="admin-empty">Sin datos todavía.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>ALCANCE</p><h3>Regiones con más voluntarios</h3></div>
            </div>
            <div class="horizontal-chart">
                @forelse ($regiones as $region)
                    <div>
                        <span>{{ $region->region }}</span>
                        <i><b style="width: {{ round($region->total / $maxRegion * 100) }}%"></b></i>
                        <em>{{ $region->total }}</em>
                    </div>
                @empty
                    <p class="admin-empty">Aún no hay regiones declaradas.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>CONVERSIÓN</p><h3>Estado de las postulaciones</h3></div>
            </div>
            @php
                $colores = [
                    'en revision' => 'pendiente',
                    'en entrevista' => 'reclutando',
                    'aceptado' => 'aceptado',
                    'rechazado' => 'rechazada',
                ];
            @endphp
            @forelse ($postulacionesPorEstado as $estado => $total)
                <div class="admin-row compact">
                    <span class="status {{ $colores[$estado] ?? 'activo' }}">{{ ucfirst($estado) }}</span>
                    <span class="admin-row-main"><b>{{ $total }} postulaciones</b></span>
                    <em>{{ $totalPostulaciones > 0 ? round($total / $totalPostulaciones * 100) : 0 }}%</em>
                </div>
            @empty
                <p class="admin-empty">Sin postulaciones registradas.</p>
            @endforelse
        </section>
    </div>

    <section class="dashboard-card">
        <div class="card-heading">
            <div><p>RENDIMIENTO</p><h3>Oportunidades más activas</h3></div>
        </div>
        @forelse ($topOportunidades as $oportunidad)
            <div class="admin-row compact">
                <span class="list-icon"><x-icon name="heart" /></span>
                <span class="admin-row-main">
                    <b>{{ $oportunidad->titulo }}</b>
                    <small>{{ $oportunidad->causa }} · {{ $oportunidad->habilidad }} · {{ $oportunidad->estado }}</small>
                </span>
                <em>{{ $oportunidad->postulaciones_count }} postulaciones</em>
                <em>{{ $oportunidad->vistas }} vistas</em>
            </div>
        @empty
            <p class="admin-empty">Aún no hay oportunidades con actividad.</p>
        @endforelse
    </section>
@endsection
