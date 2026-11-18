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
                <span>{{ $postulaciones->count() }} en total · <b data-profile-percent>{{ $porcentaje }}%</b> perfil</span>
            </div>

            <p class="application-hint">
                <x-icon name="spark" :size="13" />
                Toca una postulación para ver el detalle, tu estado y completar tu perfil.
            </p>

            @forelse ($postulaciones as $postulacion)
                @php
                    $oportunidad = $postulacion->oportunidad;
                @endphp
                <button type="button" class="application-row" data-application-open
                    data-title="{{ $oportunidad?->titulo ?? 'Oportunidad' }}"
                    data-org="{{ $oportunidad?->organizacion?->nombre ?? 'ONG verificada' }}"
                    data-status="{{ $postulacion->estadoLegible() }}"
                    data-status-class="{{ $postulacion->estadoClase() }}"
                    data-estado="{{ $postulacion->estado }}"
                    data-date="{{ $postulacion->created_at?->diffForHumans() ?? 'Recién enviada' }}"
                    data-date-full="{{ $postulacion->created_at?->translatedFormat('d \d\e F \d\e Y') ?? '' }}"
                    data-note="{{ $postulacion->nota }}"
                    data-skill="{{ $oportunidad?->habilidad ?? 'Tu talento' }}"
                    data-time="{{ $oportunidad?->dedicacion ?? 'A tu ritmo' }}"
                    data-modality="{{ $oportunidad?->modalidad ?? 'Flexible' }}"
                    data-cause="{{ $oportunidad?->causa ?? 'Impacto social' }}"
                    data-percent="{{ $oportunidad?->afinidad ?? 80 }}"
                    data-url="{{ route('detalle-oportunidad', ['oportunidad' => $oportunidad?->slug ?? 'rediseno-web-accesible']) }}"
                    data-profile-incomplete="{{ $porcentaje < 100 ? '1' : '0' }}"
                    data-missing="{{ collect($faltantes)->take(3)->pluck('label')->implode(', ') }}">
                    <span class="list-icon"><x-icon name="heart" /></span>
                    <span>
                        <b>{{ $oportunidad?->titulo ?? 'Oportunidad' }}</b>
                        <small>{{ $oportunidad?->organizacion?->nombre ?? 'ONG verificada' }}</small>
                    </span>
                    <span class="status {{ $postulacion->estadoClase() }}">{{ $postulacion->estadoLegible() }}</span>
                    <em>{{ $postulacion->created_at?->diffForHumans() }}</em>
                    <x-icon name="arrow" />
                </button>
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

@section('panel-modals')
    <div class="project-modal-backdrop" data-application-modal hidden>
        <article class="application-detail-modal" role="dialog" aria-modal="true"
            aria-labelledby="application-modal-title">
            <button type="button" class="modal-close" data-application-close aria-label="Cerrar">
                <x-icon name="close" />
            </button>

            <header class="application-modal-head">
                <div>
                    <p>DETALLE DE TU POSTULACIÓN</p>
                    <h2 id="application-modal-title" data-application-title>Oportunidad</h2>
                    <span data-application-org>ONG verificada</span>
                </div>
                <span class="status aceptado" data-application-status>En revisión</span>
            </header>

            <div class="application-modal-meta">
                <div><small>Enviada</small><b data-application-date>Recién enviada</b></div>
                <div><small>Match</small><b data-application-percent>80%</b></div>
                <div><small>Dedicación</small><b data-application-time>A tu ritmo</b></div>
                <div><small>Modalidad</small><b data-application-modality>Flexible</b></div>
            </div>

            <div class="application-timeline" data-application-timeline>
                <div data-step="enviada"><span></span><b>Enviada</b></div>
                <div data-step="revision"><span></span><b>En revisión</b></div>
                <div data-step="entrevista"><span></span><b>En entrevista</b></div>
                <div data-step="aceptado"><span></span><b>Aceptado</b></div>
            </div>

            <div class="application-next">
                <x-icon name="spark" />
                <p data-application-next>La ONG revisará tu postulación y te contactará por mensajes.</p>
            </div>

            <div class="application-duties">
                <h3>Qué incluye este reto</h3>
                <ul>
                    <li><x-icon name="check" /> <span data-application-skill>Tu talento</span> puesto al servicio de la causa.</li>
                    <li><x-icon name="check" /> <span data-application-cause>Impacto social</span> con acompañamiento de la ONG.</li>
                    <li><x-icon name="check" /> Certificado digital y valoración para tu portafolio.</li>
                </ul>
            </div>

            <div class="application-note" data-application-note-wrap hidden>
                <small>Tu mensaje a la ONG</small>
                <p data-application-note></p>
            </div>

            <div class="application-profile-box">
                <div>
                    <b>Perfil completo · <span data-profile-percent>{{ $porcentaje }}%</span></b>
                    <small data-application-missing>
                        @if ($porcentaje < 100)
                            Te falta: {{ collect($faltantes)->take(3)->pluck('label')->implode(', ') }}.
                        @else
                            Tu perfil está listo para que las ONG te contacten.
                        @endif
                    </small>
                </div>
                <button type="button" class="jelly-button small" data-profile-open>
                    Completar perfil <x-icon name="arrow" :size="14" />
                </button>
            </div>

            <div class="application-modal-actions">
                <a class="ghost-button" data-application-link href="#">
                    Ver ficha completa <x-icon name="arrow" :size="13" />
                </a>
                <a class="text-project-link" href="{{ route('voluntario.mensajes') }}">
                    Ir a mensajes <x-icon name="arrow" :size="12" />
                </a>
            </div>
        </article>
    </div>
@endsection
