@extends('layouts.app')

@section('title', 'VoluntMatch — Tu talento tiene un lugar donde hacer match')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('main')
    {{-- HERO con slider de videos --}}
    <section class="hero" id="inicio">
        <div class="hero-media">
            <video data-hero-video autoplay muted loop playsinline
                poster="https://images.unsplash.com/photo-1780291117301-25e3e7130f44?auto=format&fit=crop&w=2000&q=85">
                <source src="https://videos.pexels.com/video-files/3195394/3195394-hd_1920_1080_25fps.mp4" type="video/mp4" />
            </video>
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <div class="hero-slide active" data-hero-slide
                data-video="https://videos.pexels.com/video-files/3195394/3195394-hd_1920_1080_25fps.mp4"
                data-poster="https://images.unsplash.com/photo-1780291117301-25e3e7130f44?auto=format&fit=crop&w=2000&q=85">
                <p class="eyebrow light-text">
                    <span></span>
                    <x-icon name="spark" :size="16" /> Una conexión puede cambiarlo todo
                </p>
                <h1>Tu talento tiene un lugar donde <em>hacer match.</em></h1>
                <p class="hero-copy">
                    Conectamos estudiantes con ganas de aportar y organizaciones que necesitan exactamente lo que saben hacer.
                </p>
            </div>
            <div class="hero-slide" data-hero-slide
                data-video="https://videos.pexels.com/video-files/853800/853800-hd_1920_1080_30fps.mp4"
                data-poster="https://images.unsplash.com/photo-1784802006016-11f2af3e851c?auto=format&fit=crop&w=2000&q=85">
                <p class="eyebrow light-text">
                    <span></span>
                    <x-icon name="spark" :size="16" /> Aprende haciendo el bien
                </p>
                <h1>Experiencia real. <em>Impacto real.</em></h1>
                <p class="hero-copy">
                    Diseño, tecnología, comunicación o finanzas: convierte tus habilidades en soluciones que importan.
                </p>
            </div>
            <div class="hero-slide" data-hero-slide
                data-video="https://videos.pexels.com/video-files/6994336/6994336-hd_1920_1080_25fps.mp4"
                data-poster="https://images.unsplash.com/photo-1778864874888-a23ff77b51d2?auto=format&fit=crop&w=2000&q=85">
                <p class="eyebrow light-text">
                    <span></span>
                    <x-icon name="spark" :size="16" /> Crecemos en comunidad
                </p>
                <h1>Buenas personas. <em>Grandes proyectos.</em></h1>
                <p class="hero-copy">
                    Encuentra una causa que te mueva y una comunidad que valore todo lo que puedes aportar.
                </p>
            </div>

            <div class="hero-ctas">
                <a class="jelly-button" href="{{ route('registro-voluntario') }}">
                    Quiero aportar <x-icon name="arrow" />
                </a>
                <a class="glass-button" href="#como-funciona">
                    <span><x-icon name="play" :size="17" /></span> Descubre cómo funciona
                </a>
            </div>
            @guest
                <a class="hero-ngo-link" href="{{ route('ongs.index') }}">
                    ¿Buscas una ONG? <span>Explora el mapa de Cusco <x-icon name="arrow" :size="14" /></span>
                </a>
            @endguest
        </div>
        <div class="hero-controls">
            <button type="button" data-hero-play aria-label="Pausar video">
                <span class="icon-pause"><x-icon name="pause" :size="18" /></span>
                <span class="icon-play"><x-icon name="play" :size="18" /></span>
            </button>
            <div class="slide-lines">
                <button type="button" class="active" data-hero-dot aria-label="Ver historia 1"><span></span></button>
                <button type="button" data-hero-dot aria-label="Ver historia 2"><span></span></button>
                <button type="button" data-hero-dot aria-label="Ver historia 3"><span></span></button>
            </div>
            <span data-hero-counter>01 / 03</span>
        </div>
        <a href="#como-funciona" class="scroll-cue">
            <span>DESLIZA PARA CONOCERNOS</span>
            <i><x-icon name="chevron" /></i>
        </a>
    </section>

    {{-- CÓMO FUNCIONA --}}
    <section class="intro-section" id="como-funciona">
        <div class="orb orb-one"></div>
        <div class="reveal section-heading">
            <p class="eyebrow dark-text">
                <span></span> ASÍ DE FÁCIL
            </p>
            <h2>
                Tu vocación encuentra<br />
                <em>su causa.</em>
            </h2>
            <p>
                VoluntMatch convierte tus ganas de ayudar en experiencias que transforman comunidades y también tu futuro.
            </p>
        </div>
        <div class="steps-grid">
            <div class="reveal step-card">
                <div class="step-visual visual-1">
                    <span class="step-number">01</span>
                    <div class="skill-cloud">
                        <b>UX/UI</b>
                        <b>Idiomas</b>
                        <b>Marketing</b>
                        <b>Datos</b>
                    </div>
                </div>
                <h3>Cuéntanos qué sabes hacer</h3>
                <p>Crea tu perfil, elige tus habilidades y las causas que te importan.</p>
            </div>
            <div class="reveal step-card">
                <div class="step-visual visual-2">
                    <span class="step-number">02</span>
                    <div class="match-visual">
                        <span>V</span>
                        <i><x-icon name="heart" /></i>
                        <span>ONG</span>
                    </div>
                </div>
                <h3>Descubre tu match</h3>
                <p>Te mostramos proyectos donde tu talento encaja de verdad. Sin búsquedas eternas.</p>
            </div>
            <div class="reveal step-card">
                <div class="step-visual visual-3">
                    <span class="step-number">03</span>
                    <div class="impact-visual">
                        <strong>+24h</strong>
                        <small>DE IMPACTO</small>
                        <span><x-icon name="spark" /></span>
                    </div>
                </div>
                <h3>Crea impacto</h3>
                <p>Colabora, aprende y construye experiencia mientras haces algo que importa.</p>
            </div>
        </div>
    </section>

    {{-- MAPA DE ONG DEL CUSCO --}}
    <section class="map-section" id="ongs">
        <div class="map-section-head reveal">
            <div>
                <p class="eyebrow dark-text"><span></span> LA RED QUE YA ESTÁ EN MARCHA</p>
                <h2>
                    Encuentra tu causa<br />
                    <em>en el mapa.</em>
                </h2>
            </div>
            <div class="map-section-copy">
                <p>
                    Recorre el Cusco y descubre dónde trabaja cada organización. Toca un punto para
                    conocer su misión, cómo se organiza y qué proyectos tiene abiertos.
                </p>
                <div class="map-stats">
                    <span><b>{{ $totalOngs }}</b> ONG verificadas</span>
                    <span><b>{{ $totalOportunidades }}</b> oportunidades abiertas</span>
                </div>
                <a class="inline-link" href="{{ route('ongs.index') }}">
                    Abrir el directorio completo <x-icon name="arrow" />
                </a>
            </div>
        </div>

        <div class="home-map-card reveal" data-ongs-explorer>
            <div class="home-map-canvas" data-ongs-map data-center-lat="{{ $cusco['lat'] }}"
                data-center-lng="{{ $cusco['lng'] }}" data-zoom="13">
                <script type="application/json" data-ongs-markers>{!! json_encode($marcadores, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                <p class="map-aviso">Cargando el mapa de Cusco…</p>
            </div>
            <div class="home-map-list">
                <p class="map-list-title">ONGS EN EL CUSCO <span>{{ $ongsMapa->count() }}</span></p>
                <div class="map-list-scroll">
                    @forelse ($ongsMapa as $ong)
                        <a class="home-map-ong" href="{{ $ong->urlPublica() }}" data-ong-item="{{ $ong->id }}"
                            data-sector="{{ $ong->sector }}"
                            data-search="{{ mb_strtolower($ong->nombre.' '.$ong->sector.' '.$ong->ciudad) }}">
                            <span class="ong-avatar">
                                @if ($ong->logo_url)
                                    <img src="{{ $ong->logo_url }}" alt="" loading="lazy" />
                                @else
                                    <b>{{ $ong->iniciales() }}</b>
                                @endif
                            </span>
                            <span class="home-map-ong-copy">
                                <strong>{{ $ong->nombre }}</strong>
                                <small>{{ $ong->sector }}{{ $ong->ciudad ? ' · '.$ong->ciudad : '' }}</small>
                            </span>
                            <x-icon name="arrow" :size="14" />
                        </a>
                    @empty
                        <p class="map-list-empty">Aún no hay organizaciones con ubicación publicada.</p>
                    @endforelse
                </div>
                <a class="map-list-all" href="{{ route('ongs.index') }}">
                    Ver todas las ONG <x-icon name="arrow" :size="14" />
                </a>
            </div>
        </div>
    </section>

    {{-- MANIFIESTO / IMPACTO --}}
    <section class="manifesto-section" id="impacto">
        <div class="manifesto-image">
            <img
                src="https://images.unsplash.com/photo-1778864875183-9a89ea0ac26f?auto=format&fit=crop&w=1400&q=85"
                alt="Voluntarios colaborando con su comunidad" />
            <div class="floating-stat">
                <strong>12.4K</strong>
                <span>horas de talento donadas</span>
            </div>
        </div>
        <div class="reveal manifesto-copy">
            <p class="eyebrow dark-text">
                <span></span> MUCHO MÁS QUE VOLUNTARIADO
            </p>
            <h2>
                Cuando das lo mejor de ti, <em>todo vuelve multiplicado.</em>
            </h2>
            <p class="lead">
                Creemos que la solidaridad también puede impulsar carreras, crear amistades y devolvernos la confianza en lo colectivo.
            </p>
            <ul>
                <li>
                    <span><x-icon name="check" /></span>
                    <div>
                        <b>Experiencia que cuenta</b>
                        <p>Proyectos reales para tu portafolio y tu futuro.</p>
                    </div>
                </li>
                <li>
                    <span><x-icon name="check" /></span>
                    <div>
                        <b>Personas que suman</b>
                        <p>Una red diversa con la misma energía que tú.</p>
                    </div>
                </li>
                <li>
                    <span><x-icon name="check" /></span>
                    <div>
                        <b>Impacto que se ve</b>
                        <p>Sigue el valor concreto de cada hora que aportas.</p>
                    </div>
                </li>
            </ul>
            <a href="#comunidad" class="inline-link">
                Conoce nuestro impacto <x-icon name="arrow" />
            </a>
        </div>
    </section>

    {{-- CAUSAS / COMUNIDAD --}}
    <section class="causes-section" id="comunidad">
        <div class="reveal section-heading compact">
            <p class="eyebrow light-text">
                <span></span> HAY UNA CAUSA PARA TI
            </p>
            <h2>¿Qué te mueve?</h2>
            <p>Explora proyectos que necesitan tu energía justo ahora.</p>
        </div>
        <div class="cause-grid">
            <div class="reveal cause-card coral">
                <span class="cause-index">01</span>
                <div>
                    <h3>Educación</h3>
                    <p>Mentorías y acceso al conocimiento.</p>
                </div>
                <a href="{{ route('oportunidades') }}" aria-label="Explorar Educación"><x-icon name="arrow" /></a>
            </div>
            <div class="reveal cause-card green">
                <span class="cause-index">02</span>
                <div>
                    <h3>Medio ambiente</h3>
                    <p>Acciones por un planeta más sano.</p>
                </div>
                <a href="{{ route('oportunidades') }}" aria-label="Explorar Medio ambiente"><x-icon name="arrow" /></a>
            </div>
            <div class="reveal cause-card blue">
                <span class="cause-index">03</span>
                <div>
                    <h3>Inclusión</h3>
                    <p>Oportunidades para todas las personas.</p>
                </div>
                <a href="{{ route('oportunidades') }}" aria-label="Explorar Inclusión"><x-icon name="arrow" /></a>
            </div>
        </div>
        <div class="reveal community-strip">
            <span class="avatars">
                @foreach ([11, 12, 13, 14] as $img)
                    <img src="https://i.pravatar.cc/100?img={{ $img }}" alt="" />
                @endforeach
            </span>
            <p>
                <b>2.800+ estudiantes</b> ya encontraron su causa
            </p>
            <a class="jelly-button pale" href="{{ route('registro-voluntario') }}">
                Únete a la comunidad <x-icon name="arrow" />
            </a>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="final-cta" id="nosotros">
        <div class="reveal">
            <span class="cta-icon">
                <x-icon name="heart" :size="31" />
            </span>
            <p class="eyebrow dark-text">TU TALENTO YA ESTÁ LISTO</p>
            <h2>
                Haz match con<br />
                <em>algo que importa.</em>
            </h2>
            <p>El próximo proyecto que cambie una comunidad puede empezar contigo.</p>
            <a class="jelly-button" href="{{ route('registro-voluntario') }}">
                Crear mi perfil gratis <x-icon name="arrow" />
            </a>
            @guest
                <a class="final-ngo-link" href="{{ route('registro-organizacion') }}" data-ngo-modal-open>
                    Soy una ONG y quiero registrar mi organización <x-icon name="arrow" :size="14" />
                </a>
            @endguest
            <small>Sin costes. Sin compromiso. Con mucho propósito.</small>
        </div>
    </section>
@endsection
