@extends('layouts.base')

@section('title', 'Mi espacio — VoluntMatch')

@php
    $user = auth()->user();
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@section('content')
    <div class="dashboard-shell dashboard-student">
        <div class="dashboard-orbs" aria-hidden="true">
            <span></span>
            <span></span>
        </div>

        <aside class="dashboard-sidebar">
            <div class="brand">
                <span class="brand-mark">
                    <span></span>
                    <span></span>
                </span>
                <span>VOLUNTMATCH</span>
            </div>
            <div class="profile-chip">
                <span>{{ $initials ?: 'NX' }}</span>
                <div>
                    <b>{{ $user->name }}</b>
                    <small>Estudiante · VoluntMatch</small>
                </div>
            </div>
            <nav>
                <button type="button" class="active" data-section="overview" data-title="Mi resumen">
                    <x-icon name="spark" /> <span>Mi resumen</span>
                </button>
                <button type="button" data-section="passport" data-title="Skills Passport">
                    <x-icon name="users" /> <span>Skills Passport</span>
                </button>
                <button type="button" data-section="matches" data-title="Descubrir matches">
                    <x-icon name="heart" /> <span>Descubrir matches</span>
                </button>
                <button type="button" data-section="applications" data-title="Postulaciones">
                    <x-icon name="check" /> <span>Postulaciones</span>
                </button>
                <button type="button" data-section="impact" data-title="Mi impacto">
                    <x-icon name="spark" /> <span>Mi impacto</span>
                </button>
                <button type="button" data-section="messages" data-title="Mensajes">
                    <x-icon name="users" /> <span>Mensajes</span> <i>2</i>
                </button>
            </nav>
            <div class="sidebar-help">
                <x-icon name="heart" />
                <b>¿Necesitas ayuda?</b>
                <small>Estamos contigo.</small>
                <button type="button" data-goto="messages">Contactar</button>
            </div>
            <a class="logout-button" href="{{ route('logout') }}">
                Cerrar sesión <x-icon name="arrow" />
            </a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-top">
                <div>
                    <p>ESPACIO VOLUNTMATCH · MI RESUMEN</p>
                    <h2 data-dash-heading>Hola, {{ explode(' ', trim($user->name))[0] }}.</h2>
                </div>
                <div class="dashboard-top-actions">
                    <button class="dash-theme-button" type="button" data-theme-toggle
                        aria-label="Alternar modo oscuro">
                        <span class="theme-icon-light"><x-icon name="moon" /></span>
                        <span class="theme-icon-dark"><x-icon name="sun" /></span>
                    </button>
                    <button type="button" class="notification-button">
                        <span>3</span>
                        <x-icon name="spark" />
                    </button>
                    <a class="dashboard-close" href="{{ route('index') }}">
                        <x-icon name="close" /> Volver a la web
                    </a>
                </div>
            </div>

            {{-- OVERVIEW --}}
            <div data-panel="overview" data-heading="Hola, {{ explode(' ', trim($user->name))[0] }}.">
                @include('partials.anuncios')
                <div class="metric-grid metric-grid-four">
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>24 h</strong>
                        <p>Impacto acumulado</p>
                        <small>↗ 3% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>3</strong>
                        <p>Proyectos completados</p>
                        <small>↗ 4% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="heart" /></span>
                        <strong>4.9</strong>
                        <p>Valoración media</p>
                        <small>↗ 5% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>86%</strong>
                        <p>Perfil completo</p>
                        <small>↗ 6% este mes</small>
                    </article>
                </div>
                <div class="overview-grid">
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>MATCHES PARA TI</p>
                                <h3>Tu próximo reto te espera</h3>
                            </div>
                            <button type="button" data-goto="matches">
                                Ver todo <x-icon name="arrow" />
                            </button>
                        </div>
                        @foreach ($oportunidades as $op)
                            <a class="project-list-button" href="{{ route('detalle-oportunidad', ['oportunidad' => $op['slug']]) }}">
                                <span class="list-icon"><x-icon name="heart" /></span>
                                <span>
                                    <b>{{ $op['title'] }}</b>
                                    <small>{{ $op['org'] }} · {{ $op['skill'] }}</small>
                                </span>
                                <em>{{ $op['time'] }}</em>
                                <x-icon name="arrow" />
                            </a>
                        @endforeach
                    </section>
                    <section class="dashboard-card quick-card">
                        <div class="card-heading">
                            <div>
                                <p>ACCIÓN RÁPIDA</p>
                                <h3>Tu perfil destaca</h3>
                            </div>
                        </div>
                        <div class="profile-ring">
                            <strong>86%</strong>
                            <span>completado</span>
                        </div>
                        <p>Añade una habilidad técnica para mejorar tus matches.</p>
                        <button type="button" data-goto="passport">
                            Completar perfil <x-icon name="arrow" />
                        </button>
                    </section>
                </div>
            </div>

            {{-- SKILLS PASSPORT --}}
            <div data-panel="passport" data-heading="Skills Passport" hidden>
                <div class="skills-layout">
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>SKILLS PASSPORT</p>
                                <h3>Tu mapa de fortalezas</h3>
                            </div>
                            <span>Actualizado hoy</span>
                        </div>
                        <div class="radar-wrap">
                            <div class="radar-chart">
                                <span class="radar-shape"></span>
                                @foreach (['Comunicación', 'Creatividad', 'Liderazgo', 'Análisis', 'Trabajo en equipo'] as $i => $name)
                                    <b style="transform: rotate({{ $i * 72 }}deg) translateY(-102px) rotate({{ -$i * 72 }}deg)">{{ $name }}</b>
                                @endforeach
                            </div>
                        </div>
                        <p class="skills-note">
                            <x-icon name="spark" /> Destacas especialmente en trabajo en equipo y comunicación.
                        </p>
                    </section>
                    <section class="dashboard-card skill-editor">
                        <div class="card-heading">
                            <div>
                                <p>AUTODESCUBRIMIENTO</p>
                                <h3>Ajusta tus fortalezas</h3>
                            </div>
                        </div>
                        @foreach ([['Comunicación', 88], ['Creatividad', 82], ['Liderazgo', 67], ['Análisis', 74], ['Trabajo en equipo', 92]] as [$name, $value])
                            <label>
                                <span>
                                    <b>{{ $name }}</b>
                                    <em data-range-value>{{ $value }}%</em>
                                </span>
                                <input type="range" min="20" max="100" value="{{ $value }}" data-range />
                            </label>
                        @endforeach
                        <button type="button" class="jelly-button" data-toast-msg="Skills Passport actualizado">
                            Guardar mi pasaporte <x-icon name="check" />
                        </button>
                    </section>
                </div>
            </div>

            {{-- MATCHES — baraja estilo Tinder --}}
            <div data-panel="matches" data-heading="Descubrir matches" hidden>
                <div class="match-layout">
                    <section class="match-card-large swipe-deck" data-swipe-deck>
                        <div class="deck-stage" data-deck-stage>
                            @foreach ($oportunidades as $i => $op)
                                <article class="swipe-card" data-swipe-card data-title="{{ $op['title'] }}">
                                    <div class="swipe-photo art-{{ $i + 1 }}">
                                        <img src="{{ $op['image'] }}" alt="{{ $op['title'] }}"
                                            loading="lazy" onerror="this.remove()" draggable="false" />
                                        <span class="swipe-cause">{{ $op['cause'] }}</span>
                                        <b class="swipe-percent">{{ 92 - $i * 2 }}%<small>MATCH</small></b>
                                        <span class="stamp stamp-like">¡ME INTERESA!</span>
                                        <span class="stamp stamp-nope">PASAR</span>
                                        <div class="swipe-overlay"></div>
                                    </div>
                                    <div class="swipe-info">
                                        <span class="skill-pill">{{ $op['skill'] }}</span>
                                        <h3>{{ $op['title'] }}</h3>
                                        <p><b>{{ $op['org'] }}</b> busca tu talento para un proyecto con impacto real.</p>
                                        <div class="match-meta">
                                            <span>{{ $op['time'] }}</span>
                                            <span>Remoto</span>
                                            <span>Certificado</span>
                                        </div>
                                        <a class="text-project-link" href="{{ route('detalle-oportunidad', ['oportunidad' => $op['slug']]) }}">
                                            Ver ficha completa <x-icon name="arrow" />
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="deck-empty" data-deck-empty hidden>
                            <span class="deck-empty-icon"><x-icon name="heart" :size="30" /></span>
                            <b>¡Ya viste todos los matches!</b>
                            <p>Vuelve más tarde para descubrir nuevas oportunidades o repasa la baraja otra vez.</p>
                            <button type="button" class="jelly-button small" data-deck-reset>
                                Repasar de nuevo <x-icon name="arrow" :size="15" />
                            </button>
                        </div>

                        <div class="swipe-actions">
                            <button type="button" data-deck-nope aria-label="Pasar">
                                <x-icon name="close" />
                            </button>
                            <button type="button" data-deck-like aria-label="Postularme">
                                <x-icon name="heart" />
                            </button>
                        </div>
                        <p class="deck-hint">
                            <x-icon name="spark" :size="13" /> Arrastra la tarjeta a la izquierda
                            para pasar o a la derecha para postularte.
                        </p>
                    </section>
                    <aside class="match-side">
                        <p>POR QUÉ ENCAJA CONTIGO</p>
                        <div><x-icon name="check" /> <span>Tu habilidad encaja con el proyecto</span></div>
                        <div><x-icon name="check" /> <span>Compatible con tu horario</span></div>
                        <div><x-icon name="check" /> <span>Causa que te importa</span></div>
                        <hr />
                        <small>Consejo VoluntMatch</small>
                        <p>Los perfiles con Skills Passport completo reciben un 40% más de respuestas.</p>
                    </aside>
                </div>
            </div>

            {{-- POSTULACIONES --}}
            <div data-panel="applications" data-heading="Postulaciones" hidden>
                <section class="dashboard-card table-card">
                    <div class="card-heading">
                        <div>
                            <p>SEGUIMIENTO</p>
                            <h3>Mis postulaciones y proyectos</h3>
                        </div>
                        <span data-applications-count>3 activas</span>
                    </div>
                    <a class="application-row" href="{{ route('detalle-oportunidad', ['oportunidad' => 'mentoria-de-matematicas']) }}">
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>Mentoría de matemáticas</b>
                            <small>Aula Abierta</small>
                        </span>
                        <span class="status en-curso">En curso</span>
                        <em>Inicio 14 mayo</em>
                        <x-icon name="arrow" />
                    </a>
                    <a class="application-row" href="{{ route('detalle-oportunidad', ['oportunidad' => 'campana-de-reforestacion']) }}">
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>Campaña de reforestación</b>
                            <small>Raíz Colectiva</small>
                        </span>
                        <span class="status aceptado">Aceptado</span>
                        <em>Comienza pronto</em>
                        <x-icon name="arrow" />
                    </a>
                    <a class="application-row" href="{{ route('detalle-oportunidad', ['oportunidad' => 'rediseno-web-accesible']) }}">
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>Identidad para Casa Joven</b>
                            <small>Casa Joven</small>
                        </span>
                        <span class="status en-revisión">En revisión</span>
                        <em>Enviada ayer</em>
                        <x-icon name="arrow" />
                    </a>
                    <div data-applications-new></div>
                </section>
            </div>

            {{-- IMPACTO --}}
            <div data-panel="impact" data-heading="Mi impacto" hidden>
                <div class="impact-dashboard">
                    <section class="impact-hero-card">
                        <div>
                            <p>MI IMPACTO</p>
                            <h3>24 horas que ya están cambiando cosas.</h3>
                            <span>
                                Tu aportación equivale a <b>3 días</b> de trabajo profesional para una ONG.
                            </span>
                        </div>
                        <div class="impact-orbit">
                            <strong>24<small>HORAS</small></strong>
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
                            <div><span><x-icon name="spark" /></span> <b>Primer match</b></div>
                            <div><span><x-icon name="heart" /></span> <b>Alma de equipo</b></div>
                            <div><span><x-icon name="users" /></span> <b>10 horas</b></div>
                        </div>
                    </section>
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>PORTAFOLIO</p>
                                <h3>Constancias digitales</h3>
                            </div>
                        </div>
                        <div class="certificate">
                            <x-icon name="check" :size="30" />
                            <span>
                                <b>Mentoría educativa</b>
                                <small>12 horas · Aula Abierta</small>
                            </span>
                            <button type="button" data-toast-msg="Constancia preparada para LinkedIn">
                                Añadir a LinkedIn <x-icon name="arrow" />
                            </button>
                        </div>
                    </section>
                    <section class="dashboard-card review-card">
                        <div class="card-heading">
                            <div>
                                <p>CIERRE COMPARTIDO</p>
                                <h3>Valora tu experiencia</h3>
                            </div>
                        </div>
                        <p>¿Cómo fue colaborar con Aula Abierta?</p>
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

            {{-- MENSAJES --}}
            <div data-panel="messages" data-heading="Mensajes" hidden>
                <div class="messaging-layout">
                    <aside class="conversation-list">
                        <div class="card-heading">
                            <div>
                                <p>MENSAJES</p>
                                <h3>Conversaciones</h3>
                            </div>
                        </div>
                        <button type="button" class="active">
                            <span>AA</span>
                            <div>
                                <b>Aula Abierta</b>
                                <small>¿Te viene bien el jueves?</small>
                            </div>
                            <i>2</i>
                        </button>
                        <button type="button">
                            <span>FH</span>
                            <div>
                                <b>Fundación Horizonte</b>
                                <small>Perfecto, muchas gracias</small>
                            </div>
                        </button>
                        <button type="button">
                            <span>RC</span>
                            <div>
                                <b>Raíz Colectiva</b>
                                <small>Tienes un archivo nuevo</small>
                            </div>
                        </button>
                    </aside>
                    <section class="chat-panel">
                        <header>
                            <span>AA</span>
                            <div>
                                <b>Aula Abierta</b>
                                <small><i></i> En línea · Proyecto Mentoría</small>
                            </div>
                            <a href="{{ route('detalle-oportunidad', ['oportunidad' => 'mentoria-de-matematicas']) }}">Ver proyecto</a>
                        </header>
                        <div class="chat-messages" data-chat>
                            <p>HOY</p>
                            <div>
                                <span>¡Hola! Tu perfil encaja muy bien con el proyecto.</span>
                                <small>16:20</small>
                            </div>
                            <div class="mine">
                                <span>Muchas gracias. ¿Cuándo podríamos hacer la primera reunión?</span>
                                <small>16:21</small>
                            </div>
                            <div>
                                <span>¿Te viene bien el jueves a las 17:00?</span>
                                <small>16:22</small>
                            </div>
                        </div>
                        <form data-chat-form>
                            <button type="button">+</button>
                            <input type="text" placeholder="Escribe un mensaje..." data-chat-input />
                            <button type="submit"><x-icon name="arrow" /></button>
                        </form>
                    </section>
                    <aside class="chat-context">
                        <span class="shared-project-icon"><x-icon name="heart" /></span>
                        <p>PROYECTO COMPARTIDO</p>
                        <h3>Mentoría de matemáticas</h3>
                        <div>
                            <span>Próxima reunión</span>
                            <b>Jueves · 17:00</b>
                        </div>
                        <div>
                            <span>Entregables</span>
                            <b>2 de 4 listos</b>
                        </div>
                        <a href="{{ route('detalle-oportunidad', ['oportunidad' => 'mentoria-de-matematicas']) }}">
                            Abrir ficha <x-icon name="arrow" />
                        </a>
                    </aside>
                </div>
            </div>
        </main>

        <div class="dashboard-toast" data-toast hidden>
            <x-icon name="check" /> <span data-toast-text></span>
        </div>
    </div>
@endsection
