@extends('layouts.panel')

@section('title', 'Resumen — Panel ONG')
@section('panel-section', 'RESUMEN')
@section('panel-heading')
    Hola, equipo {{ explode(' ', trim($user->name))[0] }}.
@endsection

@section('panel-actions')
    <a class="dash-create-button" href="{{ route('ong.oportunidades', ['nueva' => 1]) }}">
        + Crear oportunidad
    </a>
@endsection

@section('panel-content')
    <div data-panel="overview">
        @include('partials.anuncios')

        <div class="metric-grid metric-grid-four">
            <article>
                <span><x-icon name="users" /></span>
                <strong>{{ $metricas['voluntarios'] }}</strong>
                <p>Voluntarios postulados</p>
                <small>{{ $metricas['postulaciones'] }} postulaciones recibidas</small>
            </article>
            <article>
                <span><x-icon name="check" /></span>
                <strong>{{ $metricas['aceptados'] }}</strong>
                <p>Candidatos aceptados</p>
                <small>Gestionados desde el Match Inbox</small>
            </article>
            <article>
                <span><x-icon name="heart" /></span>
                <strong>{{ $metricas['oportunidades'] }}</strong>
                <p>Oportunidades publicadas</p>
                <small>{{ $organizacion->verificada ? 'ONG verificada' : 'Verificación pendiente' }}</small>
            </article>
            <article>
                <span><x-icon name="spark" /></span>
                <strong>{{ $metricas['afinidad'] }}%</strong>
                <p>Afinidad media</p>
                <small>Calculada sobre tus oportunidades</small>
            </article>
        </div>

        <section class="dashboard-card attention-card">
            <div class="card-heading">
                <div>
                    <p>REQUIERE TU ATENCIÓN</p>
                    <h3>Tareas pendientes</h3>
                </div>
                <span class="attention-badge">{{ count($pendientes) }} por resolver</span>
            </div>
            @forelse ($pendientes as $pendiente)
                <div class="attention-row">
                    <span class="list-icon"><x-icon name="{{ $pendiente['icon'] }}" /></span>
                    <span class="attention-info">
                        <b>{{ $pendiente['title'] }}</b>
                        <small>{{ $pendiente['detail'] }}</small>
                    </span>
                    <a href="{{ route($pendiente['route']) }}">
                        {{ $pendiente['action'] }} <x-icon name="arrow" />
                    </a>
                </div>
            @empty
                <p class="panel-empty">Todo al día: no tienes postulaciones ni borradores pendientes.</p>
            @endforelse
        </section>

        <div class="overview-grid">
            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>ACTIVIDAD DE PROYECTOS</p>
                        <h3>Lo que está ocurriendo</h3>
                    </div>
                    <a href="{{ route('ong.oportunidades') }}">
                        Ver todo <x-icon name="arrow" />
                    </a>
                </div>
                @forelse ($recientes as $op)
                    <a class="project-list-button" href="{{ route('detalle-oportunidad', ['oportunidad' => $op->slug]) }}">
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>{{ $op->titulo }}</b>
                            <small>{{ $op->postulaciones_count }} candidatos · {{ $op->causa }}</small>
                        </span>
                        <em>{{ $op->dedicacion }}</em>
                        <x-icon name="arrow" />
                    </a>
                @empty
                    <p class="panel-empty">Aún no has publicado oportunidades.</p>
                @endforelse
            </section>

            <div class="overview-aside">
                <section class="dashboard-card quick-card">
                    <div class="card-heading">
                        <div>
                            <p>ACCIÓN RÁPIDA</p>
                            <h3>Encuentra talento</h3>
                        </div>
                    </div>
                    <div class="talent-preview">
                        @foreach ($postulaciones->take(3) as $postulacion)
                            <span class="talent-initials">{{ \Illuminate\Support\Str::of($postulacion->user?->name ?? 'E')->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                        @endforeach
                    </div>
                    <p>{{ $metricas['voluntarios'] }} estudiantes ya postularon a tus proyectos.</p>
                    <a class="jelly-button small" href="{{ route('ong.talento') }}">
                        Buscar talento <x-icon name="arrow" />
                    </a>
                </section>

                <section class="dashboard-card milestone-card">
                    <div class="card-heading">
                        <div>
                            <p>ESTA SEMANA</p>
                            <h3>Próximos hitos</h3>
                        </div>
                    </div>
                    @forelse ($hitos as $hito)
                        <div class="milestone-row">
                            <span class="milestone-date">
                                <b>{{ $hito['date'] }}</b>
                                <small>{{ $hito['day'] }}</small>
                            </span>
                            <span class="milestone-info">
                                <b>{{ $hito['title'] }}</b>
                                <small>{{ $hito['meta'] }}</small>
                            </span>
                            <em class="milestone-type {{ $hito['type'] }}">Reunión</em>
                        </div>
                    @empty
                        <p class="panel-empty">Sin entrevistas agendadas por ahora.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
@endsection
