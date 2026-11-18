@extends('layouts.app')

@section('title', 'ONGs en Cusco — VoluntMatch')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('main')
    <section class="ongs-hero">
        <div class="ongs-hero-glow" aria-hidden="true"></div>
        <div class="ongs-hero-inner">
            <a class="ongs-back light" href="{{ route('index') }}">
                <x-icon name="arrow" :size="14" /> Volver al inicio
            </a>
            <p class="eyebrow light-text"><span></span> DIRECTORIO DE ORGANIZACIONES</p>
            <h1>
                Conoce a las ONG<br />
                que <em>mueven el Cusco.</em>
            </h1>
            <p class="ongs-hero-copy">
                Un mapa vivo de la red solidaria cusqueña. Encuentra una organización cerca de ti,
                entiende cómo trabaja y descubre dónde puedes aportar tu talento.
            </p>
            <div class="ongs-hero-stats">
                <span><b>{{ $organizaciones->count() }}</b> organizaciones publicadas</span>
                <span><b>{{ $marcadores !== [] ? count($marcadores) : 0 }}</b> puntos en el mapa</span>
                <span><b>{{ $organizaciones->sum('oportunidades_count') }}</b> oportunidades abiertas</span>
            </div>
        </div>
    </section>

    <section class="ongs-explorer wrap" data-ongs-explorer>
        <div class="ongs-map-wrap">
            <div class="ongs-map" data-ongs-map data-center-lat="{{ $cusco['lat'] }}"
                data-center-lng="{{ $cusco['lng'] }}" data-zoom="13">
                <script type="application/json" data-ongs-markers>{!! json_encode($marcadores, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                <p class="map-aviso">Cargando el mapa de Cusco…</p>
            </div>
            <div class="ongs-map-tools">
                <button type="button" class="map-recenter" data-ongs-recenter>
                    <x-icon name="spark" :size="14" /> Centrar en Cusco
                </button>
                <span>Toca un punto para ver la ONG</span>
            </div>
        </div>

        <aside class="ongs-map-panel">
            <div class="ongs-panel-head">
                <p>ONGS EN EL MAPA</p>
                <h2>{{ $organizaciones->count() }} organizaciones</h2>
            </div>

            <form class="ongs-search" method="GET" action="{{ route('ongs.index') }}">
                <x-icon name="search" :size="16" />
                <input type="search" name="q" value="{{ $busqueda }}" placeholder="Buscar ONG, causa o ciudad"
                    aria-label="Buscar organizaciones" data-ongs-search />
                @if ($sectorActivo !== '')
                    <input type="hidden" name="sector" value="{{ $sectorActivo }}" />
                @endif
            </form>

            <div class="ongs-sector-chips" aria-label="Filtrar por causa">
                <a class="{{ $sectorActivo === '' ? 'active' : '' }}" href="{{ route('ongs.index', ['q' => $busqueda ?: null]) }}"
                    data-ongs-sector="">Todas</a>
                @foreach ($sectores as $sector)
                    <a class="{{ $sectorActivo === $sector ? 'active' : '' }}"
                        href="{{ route('ongs.index', ['sector' => $sector, 'q' => $busqueda ?: null]) }}"
                        data-ongs-sector="{{ $sector }}">{{ $sector }}</a>
                @endforeach
            </div>

            <div class="ongs-list">
                @forelse ($organizaciones as $ong)
                    <a class="ongs-list-item" href="{{ $ong->urlPublica() }}" data-ong-item="{{ $ong->id }}"
                        data-sector="{{ $ong->sector }}"
                        data-search="{{ mb_strtolower($ong->nombre.' '.$ong->sector.' '.$ong->ciudad) }}">
                        <span class="ong-avatar">
                            @if ($ong->logo_url)
                                <img src="{{ $ong->logo_url }}" alt="" loading="lazy" />
                            @else
                                <b>{{ $ong->iniciales() }}</b>
                            @endif
                        </span>
                        <span class="ongs-list-copy">
                            <strong>{{ $ong->nombre }}</strong>
                            <small>{{ $ong->sector }}{{ $ong->ciudad ? ' · '.$ong->ciudad : '' }}</small>
                        </span>
                        <span class="ongs-list-meta">
                            @if ($ong->oportunidades_count > 0)
                                <b>{{ $ong->oportunidades_count }}</b>
                                <small>{{ $ong->oportunidades_count === 1 ? 'reto' : 'retos' }}</small>
                            @endif
                            <x-icon name="arrow" :size="14" />
                        </span>
                    </a>
                @empty
                    <p class="ongs-list-empty">
                        No encontramos organizaciones con esos filtros.
                        <a href="{{ route('ongs.index') }}">Ver todas</a>
                    </p>
                @endforelse
                <p class="ongs-list-empty" data-ongs-empty hidden>
                    Ninguna ONG coincide con tu búsqueda.
                </p>
            </div>
        </aside>
    </section>

    <section class="ongs-directory wrap">
        <div class="ongs-directory-head">
            <div>
                <p class="eyebrow dark-text"><span></span> TODAS LAS ORGANIZACIONES</p>
                <h2>Una causa para cada talento.</h2>
            </div>
            <p>
                Cada ficha muestra la misión, la forma de trabajo y los proyectos abiertos de la organización.
                Si algo te mueve, puedes postular directamente desde su perfil.
            </p>
        </div>

        @if ($organizaciones->isEmpty())
            <div class="ongs-empty">
                <x-icon name="heart" :size="26" />
                <h3>Aún no hay organizaciones que coincidan.</h3>
                <p>Prueba con otra causa o vuelve a ver el directorio completo.</p>
                <a class="jelly-button" href="{{ route('ongs.index') }}">Ver todas las ONG <x-icon name="arrow" /></a>
            </div>
        @else
            <div class="ongs-grid">
                @foreach ($organizaciones as $ong)
                    <article class="ong-card">
                        <a class="ong-card-cover" href="{{ $ong->urlPublica() }}"
                            aria-label="Ver la ficha de {{ $ong->nombre }}">
                            @if ($ong->portada_url)
                                <img src="{{ $ong->portada_url }}" alt="" loading="lazy" />
                            @else
                                <span class="ong-card-cover-fallback"><x-icon name="heart" :size="30" /></span>
                            @endif
                            <span class="ong-card-logo">
                                @if ($ong->logo_url)
                                    <img src="{{ $ong->logo_url }}" alt="" loading="lazy" />
                                @else
                                    <b>{{ $ong->iniciales() }}</b>
                                @endif
                            </span>
                        </a>
                        <div class="ong-card-body">
                            <span class="skill-pill">{{ $ong->sector }}</span>
                            <h3><a href="{{ $ong->urlPublica() }}">{{ $ong->nombre }}</a></h3>
                            @if ($ong->ciudad)
                                <p class="ong-card-city"><x-icon name="pin" :size="13" /> {{ $ong->ciudad }}</p>
                            @endif
                            <p class="ong-card-mission">{{ $ong->mision }}</p>
                            <div class="ong-card-foot">
                                <span>{{ $ong->oportunidades_count }}
                                    {{ $ong->oportunidades_count === 1 ? 'oportunidad abierta' : 'oportunidades abiertas' }}</span>
                                <a href="{{ $ong->urlPublica() }}">Ver ficha <x-icon name="arrow" :size="14" /></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="ongs-cta wrap">
        <div>
            <p class="eyebrow light-text"><span></span> ¿TIENES UNA ORGANIZACIÓN?</p>
            <h2>Tu causa también merece <em>un lugar en el mapa.</em></h2>
            <p>Registra tu ONG, completa su ficha pública y empieza a recibir talento joven.</p>
        </div>
        @guest
            <a class="jelly-button" href="{{ route('registro-organizacion') }}" data-ngo-modal-open>
                Registrar mi ONG <x-icon name="arrow" />
            </a>
        @else
            <a class="jelly-button" href="{{ route('home') }}">Ir a mi espacio <x-icon name="arrow" /></a>
        @endguest
    </section>
@endsection
