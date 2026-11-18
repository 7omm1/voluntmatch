@extends('layouts.panel')

@section('title', 'Mi resumen — VoluntMatch')
@section('panel-section', 'MI RESUMEN')
@section('panel-heading')
    Hola, {{ explode(' ', trim($user->name))[0] }}.
@endsection

@section('panel-content')
    <div data-panel="overview">
        @include('partials.anuncios')

        <div class="metric-grid metric-grid-four">
            <article>
                <span><x-icon name="check" /></span>
                <strong>{{ $metricas['activas'] }}</strong>
                <p>Postulaciones activas</p>
                <small>{{ $metricas['total'] }} enviadas en total</small>
            </article>
            <article>
                <span><x-icon name="heart" /></span>
                <strong>{{ $metricas['aceptadas'] }}</strong>
                <p>Proyectos aceptados</p>
                <small>Tu talento ya está aportando</small>
            </article>
            <article>
                <span><x-icon name="spark" /></span>
                <strong>{{ $metricas['afinidad'] }}%</strong>
                <p>Afinidad media del catálogo</p>
                <small>Calculada sobre oportunidades publicadas</small>
            </article>
            <article>
                <span><x-icon name="users" /></span>
                <strong data-profile-percent>{{ $porcentaje }}%</strong>
                <p>Perfil completo</p>
                <small>Completa tu Skills Passport</small>
            </article>
        </div>

        <div class="overview-grid">
            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>MATCHES PARA TI</p>
                        <h3>Tu próximo reto te espera</h3>
                    </div>
                    <a href="{{ route('voluntario.matches') }}">
                        Ver todo <x-icon name="arrow" />
                    </a>
                </div>
                @forelse ($oportunidades as $op)
                    <a class="project-list-button"
                        href="{{ route('detalle-oportunidad', ['oportunidad' => $op->slug]) }}">
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>{{ $op->titulo }}</b>
                            <small>{{ $op->organizacion?->nombre ?? 'ONG verificada' }} · {{ $op->habilidad }}</small>
                        </span>
                        <em>{{ $op->dedicacion }}</em>
                        <x-icon name="arrow" />
                    </a>
                @empty
                    <p class="panel-empty">
                        No hay oportunidades nuevas por ahora. Revisa tus postulaciones o vuelve más tarde.
                    </p>
                @endforelse
            </section>

            <section class="dashboard-card quick-card">
                <div class="card-heading">
                    <div>
                        <p>ACCIÓN RÁPIDA</p>
                        <h3>Tu perfil destaca</h3>
                    </div>
                </div>
                <div class="profile-ring">
                    <strong data-profile-percent>{{ $porcentaje }}%</strong>
                    <span>completado</span>
                </div>
                <p>Añade una habilidad técnica para mejorar tus matches.</p>
                <button type="button" class="jelly-button small" data-profile-open>
                    Completar perfil <x-icon name="arrow" />
                </button>
            </section>
        </div>
    </div>
@endsection
