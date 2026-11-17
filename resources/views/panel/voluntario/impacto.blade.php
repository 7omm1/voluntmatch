@extends('layouts.panel')

@section('title', 'Mi impacto — VoluntMatch')
@section('panel-section', 'MI IMPACTO')
@section('panel-heading', 'Tu tiempo, convertido en impacto.')

@section('panel-content')
    <div data-panel="impact">
        <div class="impact-dashboard">
            <section class="impact-hero-card">
                <div>
                    <p>MI IMPACTO</p>
                    <h3>{{ $horas }} horas que ya están cambiando cosas.</h3>
                    <span>
                        Tu aportación equivale a <b>{{ max(1, (int) round($horas / 8)) }} días</b>
                        de trabajo profesional para una ONG.
                    </span>
                </div>
                <div class="impact-orbit">
                    <strong>{{ $horas }}<small>HORAS</small></strong>
                    <i></i><i></i><i></i>
                </div>
            </section>

            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>INSIGNIAS</p>
                        <h3>Logros desbloqueados</h3>
                    </div>
                </div>
                <div class="badges">
                    @foreach ($insignias as $insignia)
                        <div class="{{ $insignia['done'] ? '' : 'is-locked' }}">
                            <span><x-icon name="{{ $insignia['icon'] }}" /></span>
                            <b>{{ $insignia['label'] }}</b>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>PORTAFOLIO</p>
                        <h3>Constancias digitales</h3>
                    </div>
                </div>
                @forelse ($aceptadas as $postulacion)
                    <div class="certificate">
                        <x-icon name="check" :size="30" />
                        <span>
                            <b>{{ $postulacion->oportunidad?->titulo ?? 'Proyecto' }}</b>
                            <small>{{ $horas }} horas · {{ $postulacion->oportunidad?->organizacion?->nombre ?? 'ONG verificada' }}</small>
                        </span>
                        <button type="button" data-toast-msg="Constancia preparada para LinkedIn">
                            Añadir a LinkedIn <x-icon name="arrow" />
                        </button>
                    </div>
                @empty
                    <p class="panel-empty">Cuando una ONG acepte tu postulación, aquí aparecerá tu constancia digital.</p>
                @endforelse
            </section>

            <section class="dashboard-card review-card">
                <div class="card-heading">
                    <div>
                        <p>CIERRE COMPARTIDO</p>
                        <h3>Valora tu experiencia</h3>
                    </div>
                </div>
                <p>¿Cómo fue colaborar con la organización?</p>
                <div class="stars">
                    @for ($star = 1; $star <= 5; $star++)
                        <button type="button" data-star aria-label="{{ $star }} estrellas">★</button>
                    @endfor
                </div>
                <textarea placeholder="Cuéntanos sobre el trato, las instrucciones y lo que aprendiste..."></textarea>
                <button type="button" data-toast-msg="Valoración enviada. ¡Gracias!">
                    Enviar valoración
                </button>
            </section>
        </div>
    </div>
@endsection
