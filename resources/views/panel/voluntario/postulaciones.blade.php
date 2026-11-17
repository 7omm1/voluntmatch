@extends('layouts.panel')

@section('title', 'Postulaciones — VoluntMatch')
@section('panel-section', 'POSTULACIONES')
@section('panel-heading', 'Tus postulaciones, en un solo lugar.')

@section('panel-content')
    <div data-panel="applications">
        <div class="metric-grid metric-grid-four">
            <article>
                <span><x-icon name="check" /></span>
                <strong>{{ $metricas['activas'] }}</strong>
                <p>Activas</p>
                <small>En revisión, entrevista o aceptadas</small>
            </article>
            <article>
                <span><x-icon name="clock" /></span>
                <strong>{{ $metricas['revision'] }}</strong>
                <p>En revisión</p>
                <small>La ONG aún no responde</small>
            </article>
            <article>
                <span><x-icon name="users" /></span>
                <strong>{{ $metricas['entrevista'] }}</strong>
                <p>En entrevista</p>
                <small>Revisa tus mensajes</small>
            </article>
            <article>
                <span><x-icon name="heart" /></span>
                <strong>{{ $metricas['aceptadas'] }}</strong>
                <p>Aceptadas</p>
                <small>Proyectos confirmados</small>
            </article>
        </div>

        <section class="dashboard-card table-card">
            <div class="card-heading">
                <div>
                    <p>SEGUIMIENTO</p>
                    <h3>Mis postulaciones y proyectos</h3>
                </div>
                <span>{{ $postulaciones->count() }} en total</span>
            </div>

            @forelse ($postulaciones as $postulacion)
                <a class="application-row"
                    href="{{ route('detalle-oportunidad', ['oportunidad' => $postulacion->oportunidad?->slug ?? 'rediseno-web-accesible']) }}">
                    <span class="list-icon"><x-icon name="heart" /></span>
                    <span>
                        <b>{{ $postulacion->oportunidad?->titulo ?? 'Oportunidad' }}</b>
                        <small>{{ $postulacion->oportunidad?->organizacion?->nombre ?? 'ONG verificada' }}</small>
                    </span>
                    <span class="status {{ $postulacion->estadoClase() }}">{{ $postulacion->estadoLegible() }}</span>
                    <em>{{ $postulacion->created_at?->diffForHumans() }}</em>
                    <x-icon name="arrow" />
                </a>
            @empty
                <div class="panel-empty">
                    <p>Aún no te has postulado a ninguna oportunidad.</p>
                    <a class="jelly-button small" href="{{ route('voluntario.matches') }}">
                        Descubrir matches <x-icon name="arrow" :size="14" />
                    </a>
                </div>
            @endforelse
        </section>
    </div>
@endsection
