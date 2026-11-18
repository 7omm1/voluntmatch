@extends('layouts.app')

@section('title', $ong->nombre.' — ONG en '.($ong->ciudad ?: 'Cusco').' | VoluntMatch')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('main')
    @php
        $esDueno = auth()->check() && auth()->id() === $ong->user_id;
        $esAdmin = auth()->check() && auth()->user()->role === 'admin';
    @endphp

    <section class="ong-profile-hero">
        <div class="ong-profile-cover"
            @if ($ong->portada_url) style="background-image: url('{{ $ong->portada_url }}')" @endif>
            <span class="ong-profile-cover-shade"></span>
        </div>

        <div class="ong-profile-hero-inner">
            <a class="ongs-back light" href="{{ route('ongs.index') }}">
                <x-icon name="arrow" :size="14" /> Volver al directorio
            </a>

            @if (! $ong->verificada)
                <div class="ong-profile-notice">
                    <x-icon name="clock" :size="16" />
                    <span>
                        Esta ficha aún es privada porque está pendiente de verificación. Solo tú y el equipo
                        de VoluntMatch pueden verla.
                    </span>
                </div>
            @endif

            <div class="ong-profile-head">
                <span class="ong-profile-logo">
                    @if ($ong->logo_url)
                        <img src="{{ $ong->logo_url }}" alt="Logo de {{ $ong->nombre }}" />
                    @else
                        <b>{{ $ong->iniciales() }}</b>
                    @endif
                </span>

                <div class="ong-profile-title">
                    <div class="ong-profile-tags">
                        <span class="ong-tag">{{ $ong->sector }}</span>
                        @if ($ong->verificada)
                            <span class="ong-tag verified"><x-icon name="check" :size="12" /> Verificada</span>
                        @endif
                        @if ($ong->ciudad)
                            <span class="ong-tag ghost"><x-icon name="pin" :size="12" /> {{ $ong->ciudad }}</span>
                        @endif
                    </div>
                    <h1>{{ $ong->nombre }}</h1>
                    <p>{{ $ong->mision }}</p>
                </div>

                <div class="ong-profile-actions">
                    <a class="jelly-button" href="#oportunidades">
                        Ver oportunidades <x-icon name="arrow" />
                    </a>
                    <a class="glass-button" href="#contacto">
                        <x-icon name="mail" :size="16" /> Contacto
                    </a>
                    @if ($esDueno || $esAdmin)
                        <a class="ong-profile-edit" href="{{ $esDueno ? route('ong.perfil') : route('admin.organizaciones.index') }}">
                            Editar ficha <x-icon name="arrow" :size="14" />
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="ong-profile-body wrap">
        <main class="ong-profile-main">
            <section class="ong-profile-block">
                <p class="eyebrow dark-text"><span></span> QUIÉNES SOMOS</p>
                <h2>La historia detrás de <em>{{ $ong->nombre }}.</em></h2>
                <div class="ong-profile-text">
                    <p>{{ $ong->descripcion ?: $ong->mision }}</p>
                </div>
            </section>

            @if ($ong->como_participar)
                <section class="ong-profile-block">
                    <p class="eyebrow dark-text"><span></span> CÓMO NOS ORGANIZAMOS</p>
                    <h2>Así se trabaja aquí.</h2>
                    <div class="ong-profile-text">
                        <p>{{ $ong->como_participar }}</p>
                    </div>
                </section>
            @endif

            <section class="ong-profile-block" id="oportunidades">
                <div class="ong-profile-block-head">
                    <div>
                        <p class="eyebrow dark-text"><span></span> QUÉ OFRECE Y QUÉ BUSCA</p>
                        <h2>Proyectos abiertos.</h2>
                    </div>
                    <span class="ong-offer-count">{{ $oportunidades->count() }}
                        {{ $oportunidades->count() === 1 ? 'reto' : 'retos' }}</span>
                </div>

                @forelse ($oportunidades as $oportunidad)
                    <a class="ong-offer" href="{{ route('detalle-oportunidad', ['oportunidad' => $oportunidad->slug]) }}">
                        <div class="ong-offer-main">
                            <span class="skill-pill">{{ $oportunidad->causa }}</span>
                            <h3>{{ $oportunidad->titulo }}</h3>
                            <p>{{ $oportunidad->resumen }}</p>
                        </div>
                        <div class="ong-offer-meta">
                            <span><b>{{ $oportunidad->habilidad }}</b> habilidad buscada</span>
                            <span><b>{{ $oportunidad->dedicacion }}</b> {{ $oportunidad->modalidad }}</span>
                        </div>
                        <span class="ong-offer-arrow"><x-icon name="arrow" /></span>
                    </a>
                @empty
                    <div class="ong-offers-empty">
                        <x-icon name="spark" :size="24" />
                        <div>
                            <h3>Por ahora no hay proyectos publicados.</h3>
                            <p>La organización está preparando nuevas oportunidades. Vuelve pronto o contáctala directamente.</p>
                        </div>
                    </div>
                @endforelse

                @guest
                    <div class="ong-profile-join">
                        <div>
                            <h3>¿Te gustaría aportar tu talento aquí?</h3>
                            <p>Crea tu perfil de estudiante y postula a sus proyectos cuando estén abiertos.</p>
                        </div>
                        <a class="jelly-button" href="{{ route('registro-voluntario') }}">
                            Quiero participar <x-icon name="arrow" />
                        </a>
                    </div>
                @endguest
            </section>

            @if ($relacionadas->isNotEmpty())
                <section class="ong-profile-block">
                    <p class="eyebrow dark-text"><span></span> TAMBIÉN TE PUEDE INTERESAR</p>
                    <h2>Otras causas cercanas.</h2>
                    <div class="ong-related">
                        @foreach ($relacionadas as $relacionada)
                            <a class="ong-related-card" href="{{ $relacionada->urlPublica() }}">
                                <span class="ong-avatar small">
                                    @if ($relacionada->logo_url)
                                        <img src="{{ $relacionada->logo_url }}" alt="" loading="lazy" />
                                    @else
                                        <b>{{ $relacionada->iniciales() }}</b>
                                    @endif
                                </span>
                                <span>
                                    <strong>{{ $relacionada->nombre }}</strong>
                                    <small>{{ $relacionada->sector }}{{ $relacionada->ciudad ? ' · '.$relacionada->ciudad : '' }}</small>
                                </span>
                                <x-icon name="arrow" :size="14" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <aside class="ong-profile-aside">
            <div class="ong-contact-card" id="contacto">
                <p>CONTACTO DIRECTO</p>
                <h3>Hablemos de tu talento.</h3>

                <ul>
                    @if ($ong->user?->email)
                        <li>
                            <span><x-icon name="mail" :size="15" /></span>
                            <a href="mailto:{{ $ong->user->email }}">{{ $ong->user->email }}</a>
                        </li>
                    @endif
                    @if ($ong->telefono || $ong->user?->phone)
                        <li>
                            <span><x-icon name="phone" :size="15" /></span>
                            <a href="tel:{{ preg_replace('/\s+/', '', $ong->telefono ?: $ong->user->phone) }}">
                                {{ $ong->telefono ?: $ong->user->phone }}
                            </a>
                        </li>
                    @endif
                    @if ($ong->sitio_web || $ong->user?->website)
                        <li>
                            <span><x-icon name="globe" :size="15" /></span>
                            <a href="{{ $ong->sitio_web ?: $ong->user->website }}" target="_blank" rel="noopener">
                                {{ parse_url($ong->sitio_web ?: $ong->user->website, PHP_URL_HOST) }}
                            </a>
                        </li>
                    @endif
                    @if ($ong->direccion)
                        <li>
                            <span><x-icon name="pin" :size="15" /></span>
                            <span>{{ $ong->direccion }}</span>
                        </li>
                    @endif
                </ul>

                <dl class="ong-facts">
                    <div>
                        <dt>Sector</dt>
                        <dd>{{ $ong->sector }}</dd>
                    </div>
                    @if ($ong->anio_fundacion)
                        <div>
                            <dt>Desde</dt>
                            <dd>{{ $ong->anio_fundacion }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Proyectos</dt>
                        <dd>{{ $oportunidades->count() }} abiertos</dd>
                    </div>
                    <div>
                        <dt>Estado</dt>
                        <dd>{{ $ong->verificada ? 'Verificada por VoluntMatch' : 'En revisión' }}</dd>
                    </div>
                </dl>
            </div>

            @if ($ong->tieneUbicacion())
                <div class="ong-mini-map-card">
                    <p>CÓMO LLEGAR</p>
                    <div class="ong-mini-map" data-ongs-map data-center-lat="{{ $ong->latitud }}"
                        data-center-lng="{{ $ong->longitud }}" data-zoom="15">
                        <script type="application/json" data-ongs-markers>{!! json_encode($marcadores, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    </div>
                    @if ($ong->direccion)
                        <small>{{ $ong->direccion }}</small>
                    @endif
                </div>
            @endif
        </aside>
    </div>
@endsection
