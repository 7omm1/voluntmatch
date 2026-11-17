@extends('layouts.admin')

@section('title', 'Dashboard maestro — VoluntMatch')
@section('panel-seccion', 'DASHBOARD MAESTRO')
@section('panel-titulo', 'El pulso de VoluntMatch.')

@section('admin-content')
    <div class="metric-grid metric-grid-four">
        <article>
            <span><x-icon name="users" /></span>
            <strong>{{ number_format($metricas['voluntarios'], 0, ',', '.') }}</strong>
            <p>Voluntarios registrados</p>
            <small>{{ $metricas['nuevosMes'] }} cuentas nuevas este mes</small>
        </article>
        <article>
            <span><x-icon name="check" /></span>
            <strong>{{ number_format($metricas['organizaciones'], 0, ',', '.') }}</strong>
            <p>Organizaciones</p>
            <small>{{ $pendientes['organizaciones'] }} esperan verificación</small>
        </article>
        <article>
            <span><x-icon name="heart" /></span>
            <strong>{{ number_format($metricas['oportunidades'], 0, ',', '.') }}</strong>
            <p>Oportunidades publicadas</p>
            <small>{{ $metricas['postulaciones'] }} postulaciones recibidas</small>
        </article>
        <article>
            <span><x-icon name="spark" /></span>
            <strong>{{ number_format($metricas['matches'], 0, ',', '.') }}</strong>
            <p>Matches concretados</p>
            <small>{{ $tasaAceptacion }}% de conversión</small>
        </article>
    </div>

    <div class="metric-grid metric-grid-four admin-metric-secondary">
        <article>
            <span><x-icon name="clock" /></span>
            <strong>{{ number_format($metricas['horas'], 0, ',', '.') }} h</strong>
            <p>Compromiso semanal</p>
            <small>Horas de los matches aceptados</small>
        </article>
        <article>
            <span><x-icon name="calendar" /></span>
            <strong>{{ $metricas['nuevosMes'] }}</strong>
            <p>Altas del mes</p>
            <small>Registros desde el día 1</small>
        </article>
        <article>
            <span><x-icon name="users" /></span>
            <strong>{{ $metricas['bloqueados'] }}</strong>
            <p>Cuentas suspendidas</p>
            <small><a href="{{ route('admin.usuarios.index', ['estado' => 'bloqueados']) }}">Revisar moderación</a></small>
        </article>
        <article>
            <span><x-icon name="spark" /></span>
            <strong>{{ $pendientes['tickets'] }}</strong>
            <p>Casos de soporte abiertos</p>
            <small><a href="{{ route('admin.tickets.index') }}">Ir a la mesa de ayuda</a></small>
        </article>
    </div>

    <div class="overview-grid">
        <section class="dashboard-card">
            <div class="card-heading">
                <div>
                    <p>CRECIMIENTO DEL ECOSISTEMA</p>
                    <h3>Altas de las últimas 8 semanas</h3>
                </div>
                <a class="admin-link" href="{{ route('admin.analiticas') }}">Ver analíticas <x-icon name="arrow" /></a>
            </div>
            <div class="master-chart">
                @foreach ($semanas as $semana)
                    <span style="height: {{ max(4, (int) round($semana['total'] / $maxSemana * 100)) }}%"
                        title="{{ $semana['label'] }}: {{ $semana['total'] }} registros"></span>
                @endforeach
            </div>
            <div class="admin-chart-labels">
                @foreach ($semanas as $semana)
                    <span>{{ $semana['label'] }}</span>
                @endforeach
            </div>
        </section>

        <section class="dashboard-card quick-card">
            <div class="card-heading">
                <div>
                    <p>ACCIÓN RÁPIDA</p>
                    <h3>Tareas pendientes</h3>
                </div>
            </div>
            <a class="admin-alerts" href="{{ route('admin.organizaciones.index', ['estado' => 'pendientes']) }}">
                <b>{{ $pendientes['organizaciones'] }}</b>
                <span>ONG pendientes de verificar</span>
            </a>
            <a class="admin-alerts coral" href="{{ route('admin.reportes.index', ['estado' => 'pendiente']) }}">
                <b>{{ $pendientes['reportes'] }}</b>
                <span>Reportes por revisar</span>
            </a>
            <a class="admin-alerts neutral" href="{{ route('admin.tickets.index') }}">
                <b>{{ $pendientes['tickets'] }}</b>
                <span>Casos de soporte abiertos</span>
            </a>
            <div class="quick-admin-actions">
                <a class="admin-btn" href="{{ route('admin.organizaciones.index') }}">Verificar ONG <x-icon name="arrow" /></a>
                <a class="admin-btn ghost" href="{{ route('admin.reportes.index') }}">Revisar reportes <x-icon name="arrow" /></a>
            </div>
        </section>
    </div>

    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="card-heading">
                <div>
                    <p>DEMANDA</p>
                    <h3>Oportunidades con más postulaciones</h3>
                </div>
                <a class="admin-link" href="{{ route('admin.oportunidades.index') }}">Gestionar</a>
            </div>
            @forelse ($topOportunidades as $oportunidad)
                <div class="admin-row compact">
                    <span class="list-icon"><x-icon name="heart" /></span>
                    <span class="admin-row-main">
                        <b>{{ $oportunidad->titulo }}</b>
                        <small>{{ $oportunidad->organizacion?->nombre ?? 'Sin organización' }} · {{ $oportunidad->causa }}</small>
                    </span>
                    <span class="status {{ $oportunidad->estado }}">{{ ucfirst($oportunidad->estado) }}</span>
                    <em>{{ $oportunidad->postulaciones_count }} postulaciones</em>
                </div>
            @empty
                <p class="admin-empty">Todavía no hay oportunidades con postulaciones.</p>
            @endforelse
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <div>
                    <p>COMUNIDAD</p>
                    <h3>Últimas cuentas creadas</h3>
                </div>
                <a class="admin-link" href="{{ route('admin.usuarios.index') }}">Ver todas</a>
            </div>
            @forelse ($ultimosUsuarios as $usuario)
                <div class="admin-row compact">
                    <span class="admin-avatar">{{ mb_strtoupper(mb_substr($usuario->name, 0, 2)) }}</span>
                    <span class="admin-row-main">
                        <b>{{ $usuario->name }}</b>
                        <small>{{ $usuario->email }} · {{ $usuario->rolLegible() }}</small>
                    </span>
                    @if ($usuario->estaBloqueado())
                        <span class="status bloqueado">Suspendido</span>
                    @else
                        <span class="status activo">Activo</span>
                    @endif
                    <a class="admin-btn tiny" href="{{ route('admin.usuarios.show', $usuario) }}">Ficha</a>
                </div>
            @empty
                <p class="admin-empty">Sin cuentas registradas.</p>
            @endforelse
        </section>
    </div>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>AUDITORÍA</p>
                <h3>Actividad administrativa reciente</h3>
            </div>
            <a class="admin-link" href="{{ route('admin.actividad') }}">Ver bitácora</a>
        </div>
        @forelse ($actividad as $registro)
            <div class="admin-row compact">
                <span class="list-icon"><x-icon name="clock" /></span>
                <span class="admin-row-main">
                    <b>{{ $registro->accion }}</b>
                    <small>
                        {{ $registro->user?->name ?? 'Sistema' }}
                        @if ($registro->entidad)
                            · {{ $registro->entidad }} #{{ $registro->entidad_id }}
                        @endif
                        @if ($registro->detalle)
                            · {{ \Illuminate\Support\Str::limit($registro->detalle, 70) }}
                        @endif
                    </small>
                </span>
                <em>{{ $registro->created_at?->diffForHumans() }}</em>
            </div>
        @empty
            <p class="admin-empty">Aún no hay acciones registradas.</p>
        @endforelse
    </section>
@endsection
