@extends('layouts.base')

@section('title', 'Espacio ONG — VoluntMatch')

@php
    $user = auth()->user();
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@section('content')
    <div class="dashboard-shell dashboard-ngo">
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
                <span>{{ $initials ?: 'NG' }}</span>
                <div>
                    <b>{{ $user->name }}</b>
                    <small>ONG verificada</small>
                </div>
            </div>
            <nav>
                <button type="button" class="active" data-section="overview" data-title="Resumen">
                    <x-icon name="spark" /> <span>Resumen</span>
                </button>
                <button type="button" data-section="projects" data-title="Oportunidades">
                    <x-icon name="heart" /> <span>Oportunidades</span>
                </button>
                <button type="button" data-section="talent" data-title="Buscar talento">
                    <x-icon name="users" /> <span>Buscar talento</span>
                </button>
                <button type="button" data-section="candidates" data-title="Candidatos">
                    <x-icon name="check" /> <span>Candidatos</span>
                </button>
                <button type="button" data-section="impact" data-title="Métricas">
                    <x-icon name="spark" /> <span>Métricas</span>
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
                    <p>ESPACIO VOLUNTMATCH · RESUMEN</p>
                    <h2 data-dash-heading>Hola, equipo {{ explode(' ', trim($user->name))[0] }}.</h2>
                </div>
                <div class="dashboard-top-actions">
                    <button type="button" class="dash-create-button" data-add-project data-goto="projects">
                        + Crear oportunidad
                    </button>
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
            <div data-panel="overview" data-heading="Hola, equipo {{ explode(' ', trim($user->name))[0] }}.">
                <div class="metric-grid metric-grid-four">
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>48</strong>
                        <p>Voluntarios</p>
                        <small>↗ 3% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>12</strong>
                        <p>Proyectos entregados</p>
                        <small>↗ 4% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="heart" /></span>
                        <strong>684 h</strong>
                        <p>Tiempo ahorrado</p>
                        <small>↗ 5% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>4.8</strong>
                        <p>Valoración</p>
                        <small>↗ 6% este mes</small>
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
                    @foreach ($pendientes as $pendiente)
                        <div class="attention-row">
                            <span class="list-icon"><x-icon name="{{ $pendiente['icon'] }}" /></span>
                            <span class="attention-info">
                                <b>{{ $pendiente['title'] }}</b>
                                <small>{{ $pendiente['detail'] }}</small>
                            </span>
                            <button type="button" data-goto="{{ $pendiente['goto'] }}">
                                {{ $pendiente['action'] }} <x-icon name="arrow" />
                            </button>
                        </div>
                    @endforeach
                </section>
                <div class="overview-grid">
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>ACTIVIDAD DE PROYECTOS</p>
                                <h3>Lo que está ocurriendo</h3>
                            </div>
                            <button type="button" data-goto="projects">
                                Ver todo <x-icon name="arrow" />
                            </button>
                        </div>
                        @foreach ($oportunidades as $op)
                            <button type="button" class="project-list-button" data-project-open
                                data-title="{{ $op['title'] }}" data-org="{{ $op['org'] }}"
                                data-cause="{{ $op['cause'] }}" data-skill="{{ $op['skill'] }}"
                                data-time="{{ $op['time'] }}" data-id="{{ $loop->iteration }}">
                                <span class="list-icon"><x-icon name="heart" /></span>
                                <span>
                                    <b>{{ $op['title'] }}</b>
                                    <small>{{ $op['org'] }} · {{ $op['skill'] }}</small>
                                </span>
                                <em>{{ $op['time'] }}</em>
                                <x-icon name="arrow" />
                            </button>
                        @endforeach
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
                                @foreach ([47, 12, 32] as $img)
                                    <img src="https://i.pravatar.cc/90?img={{ $img }}" alt="" />
                                @endforeach
                            </div>
                            <p>24 estudiantes coinciden con tus proyectos abiertos.</p>
                            <button type="button" data-goto="talent">
                                Buscar talento <x-icon name="arrow" />
                            </button>
                        </section>
                        <section class="dashboard-card milestone-card">
                            <div class="card-heading">
                                <div>
                                    <p>ESTA SEMANA</p>
                                    <h3>Próximos hitos</h3>
                                </div>
                            </div>
                            @foreach ($hitos as $hito)
                                <div class="milestone-row">
                                    <span class="milestone-date">
                                        <b>{{ $hito['date'] }}</b>
                                        <small>{{ $hito['day'] }}</small>
                                    </span>
                                    <span class="milestone-info">
                                        <b>{{ $hito['title'] }}</b>
                                        <small>{{ $hito['meta'] }}</small>
                                    </span>
                                    <em class="milestone-type {{ $hito['type'] }}">
                                        {{ $hito['type'] === 'entrega' ? 'Entrega' : 'Reunión' }}
                                    </em>
                                </div>
                            @endforeach
                        </section>
                    </div>
                </div>
            </div>

            {{-- PROYECTOS --}}
            <div data-panel="projects" data-heading="Oportunidades" hidden>
                <section class="dashboard-card table-card">
                    <div class="card-heading">
                        <div>
                            <p>GESTIÓN DE OPORTUNIDADES</p>
                            <h3>Proyectos y retos publicados</h3>
                        </div>
                        <button type="button" data-add-project>+ Publicar necesidad</button>
                    </div>
                    <div class="application-row" data-project-row>
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>Campaña de educación rural</b>
                            <small>8 candidatos · Actualizado hoy</small>
                        </span>
                        <span class="status activo">Activo</span>
                        <button type="button" data-project-open data-title="Campaña de educación rural" data-org="Fundación Horizonte"
                            data-cause="Educación" data-skill="Docencia" data-time="Proyecto puntual" data-id="3">
                            Gestionar <x-icon name="arrow" />
                        </button>
                    </div>
                    <div class="application-row" data-project-row>
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>Rediseño web accesible</b>
                            <small>6 candidatos · Actualizado hoy</small>
                        </span>
                        <span class="status reclutando">Reclutando</span>
                        <button type="button" data-project-open data-title="Rediseño web accesible" data-org="Fundación Horizonte"
                            data-cause="Inclusión" data-skill="UX/UI" data-time="4 h/semana" data-id="1">
                            Gestionar <x-icon name="arrow" />
                        </button>
                    </div>
                    <div class="application-row" data-project-row>
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span>
                            <b>Análisis de encuestas</b>
                            <small>3 candidatos · Actualizado hoy</small>
                        </span>
                        <span class="status borrador">Borrador</span>
                        <button type="button" data-project-open data-title="Análisis de encuestas" data-org="Fundación Horizonte"
                            data-cause="Educación" data-skill="Análisis de datos" data-time="2 h/semana" data-id="2">
                            Gestionar <x-icon name="arrow" />
                        </button>
                    </div>
                    <div data-projects-new></div>
                </section>
            </div>

            {{-- TALENTO --}}
            <div data-panel="talent" data-heading="Buscar talento" hidden>
                <div class="talent-search">
                    <x-icon name="users" />
                    <input type="text" data-talent-search placeholder="Buscar por habilidad, carrera o nombre..." />
                    <button type="button">
                        Filtros <x-icon name="chevron" />
                    </button>
                </div>
                <div class="talent-grid">
                    @foreach ([['Sofía Martín', 'Diseño · 4º curso', 'Figma', 96, 47], ['Álvaro Vega', 'Informática · 3º curso', 'React', 92, 12], ['Nora Campos', 'Comunicación · 4º curso', 'Estrategia', 89, 32], ['Leo Santos', 'Estadística · 2º curso', 'Python', 87, 11]] as $person)
                        <article class="talent-card" data-talent-card data-search="{{ strtolower($person[0] . ' ' . $person[1] . ' ' . $person[2]) }}">
                            <span class="match-score">{{ $person[3] }}% match</span>
                            <img src="https://i.pravatar.cc/160?img={{ $person[4] }}" alt="{{ $person[0] }}" />
                            <h3>{{ $person[0] }}</h3>
                            <p>{{ $person[1] }}</p>
                            <div>
                                <span>{{ $person[2] }}</span>
                                <span>Trabajo en equipo</span>
                            </div>
                            <button type="button" data-toast-msg="Invitación enviada a {{ $person[0] }}">
                                Invitar a proyecto <x-icon name="arrow" />
                            </button>
                        </article>
                    @endforeach
                </div>
            </div>

            {{-- CANDIDATOS --}}
            <div data-panel="candidates" data-heading="Candidatos" hidden>
                <section class="dashboard-card table-card">
                    <div class="card-heading">
                        <div>
                            <p>MATCH INBOX</p>
                            <h3>Candidatos por revisar</h3>
                        </div>
                        <span>8 nuevos</span>
                    </div>
                    @foreach ([['Sofía Martín', 'Diseño · 4º curso', 'Figma', 96, 47], ['Álvaro Vega', 'Informática · 3º curso', 'React', 92, 12], ['Nora Campos', 'Comunicación · 4º curso', 'Estrategia', 89, 32], ['Leo Santos', 'Estadística · 2º curso', 'Python', 87, 11]] as $person)
                        <div class="candidate-row">
                            <img src="https://i.pravatar.cc/100?img={{ $person[4] }}" alt="" />
                            <span>
                                <b>{{ $person[0] }}</b>
                                <small>{{ $person[1] }} · {{ $person[2] }}</small>
                            </span>
                            <em>{{ $person[3] }}% match</em>
                            <button type="button" data-toast-msg="{{ $person[0] }} aceptado en el proyecto">
                                <x-icon name="check" /> Aceptar
                            </button>
                            <button type="button" data-toast-msg="Perfil guardado para más tarde">
                                Ver perfil
                            </button>
                        </div>
                    @endforeach
                </section>
            </div>

            {{-- MÉTRICAS --}}
            <div data-panel="impact" data-heading="Métricas" hidden>
                <div class="analytics-layout">
                    <section class="impact-hero-card ngo-impact">
                        <div>
                            <p>IMPACTO DE LA ORGANIZACIÓN</p>
                            <h3>684 horas de talento que multiplican vuestra misión.</h3>
                            <span>
                                Un ahorro estimado de <b>S/ 74.000</b> en servicios profesionales.
                            </span>
                        </div>
                        <div class="impact-orbit">
                            <strong>684<small>HORAS</small></strong>
                            <i></i><i></i><i></i>
                        </div>
                    </section>
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>RESULTADOS</p>
                                <h3>Proyectos entregados</h3>
                            </div>
                        </div>
                        <div class="horizontal-chart">
                            @foreach ([['Diseño', 82], ['Tecnología', 68], ['Comunicación', 91], ['Datos', 54]] as [$label, $value])
                                <div>
                                    <span>{{ $label }}</span>
                                    <i><b style="width: {{ $value }}%"></b></i>
                                    <em>{{ $value }}%</em>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <section class="dashboard-card review-card">
                        <div class="card-heading">
                            <div>
                                <p>VALORACIÓN MUTUA</p>
                                <h3>Evalúa a tus voluntarios</h3>
                            </div>
                        </div>
                        <p>Puntualidad, calidad técnica y actitud.</p>
                        <div class="stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <button type="button" data-star aria-label="{{ $star }} estrellas">★</button>
                            @endfor
                        </div>
                        <textarea placeholder="Escribe una recomendación para su portafolio..."></textarea>
                        <button type="button" data-toast-msg="Evaluación publicada en el perfil">
                            Publicar evaluación
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
                            <span>MG</span>
                            <div>
                                <b>María González</b>
                                <small>¿Te viene bien el jueves?</small>
                            </div>
                            <i>2</i>
                        </button>
                        <button type="button">
                            <span>AV</span>
                            <div>
                                <b>Álvaro Vega</b>
                                <small>Perfecto, muchas gracias</small>
                            </div>
                        </button>
                        <button type="button">
                            <span>NC</span>
                            <div>
                                <b>Nora Campos</b>
                                <small>Tienes un archivo nuevo</small>
                            </div>
                        </button>
                    </aside>
                    <section class="chat-panel">
                        <header>
                            <span>MG</span>
                            <div>
                                <b>María González</b>
                                <small><i></i> En línea · Proyecto Diseño</small>
                            </div>
                            <a href="{{ route('detalle-oportunidad', ['id' => 1]) }}">Ver proyecto</a>
                        </header>
                        <div class="chat-messages" data-chat>
                            <p>HOY</p>
                            <div>
                                <span>¡Hola! Su perfil encaja muy bien con el proyecto.</span>
                                <small>16:20</small>
                            </div>
                            <div class="mine">
                                <span>Muchas gracias. ¿Cuándo podríamos hacer la primera reunión?</span>
                                <small>16:21</small>
                            </div>
                            <div>
                                <span>¿Les viene bien el jueves a las 17:00?</span>
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
                        <h3>Rediseño web accesible</h3>
                        <div>
                            <span>Próxima reunión</span>
                            <b>Jueves · 17:00</b>
                        </div>
                        <div>
                            <span>Entregables</span>
                            <b>2 de 4 listos</b>
                        </div>
                        <a href="{{ route('detalle-oportunidad', ['id' => 1]) }}">
                            Abrir ficha <x-icon name="arrow" />
                        </a>
                    </aside>
                </div>
            </div>
        </main>

        {{-- FICHA DEL PROYECTO (modal del panel de la ONG) --}}
        <div class="project-modal-backdrop" data-project-modal data-project-base="{{ url('detalle-oportunidad') }}/" hidden>
            <article class="project-detail-modal" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
                <button type="button" class="modal-close" data-project-close aria-label="Cerrar ficha">
                    <x-icon name="close" />
                </button>
                <div class="project-detail-cover">
                    <span data-project-cause>Inclusión</span>
                    <x-icon name="heart" :size="42" />
                </div>
                <div class="project-detail-content">
                    <p class="eyebrow dark-text">FICHA COMPARTIDA DEL RETO</p>
                    <h2 id="project-modal-title" data-project-title>Rediseño web accesible</h2>
                    <p class="project-org">
                        <b data-project-org>Fundación Horizonte</b>
                        <span><x-icon name="check" /> ONG verificada</span>
                    </p>
                    <p>
                        Buscamos una persona con ganas de aportar su talento para crear una solución útil,
                        accesible y sostenible junto a nuestro equipo.
                    </p>
                    <div class="project-requirements">
                        <span data-project-skill>UX/UI</span>
                        <span data-project-time>4 h/semana</span>
                        <span>Remoto</span>
                    </div>
                    <h3>Lo que conseguirás</h3>
                    <ul>
                        <li><x-icon name="check" /> Experiencia real para tu portafolio</li>
                        <li><x-icon name="check" /> Certificado digital y valoración</li>
                        <li><x-icon name="check" /> Acompañamiento de la organización</li>
                    </ul>
                    <div class="role-modal-actions">
                        <button type="button" data-toast-msg="Editor de proyecto preparado">
                            Editar proyecto
                        </button>
                        <button type="button" data-goto="candidates" data-project-close>
                            Ver postulantes <x-icon name="arrow" />
                        </button>
                    </div>
                    <a class="project-modal-link" data-project-link
                        href="{{ route('detalle-oportunidad', ['id' => 1]) }}">
                        Ver ficha pública <x-icon name="arrow" />
                    </a>
                </div>
            </article>
        </div>

        <div class="dashboard-toast" data-toast hidden>
            <x-icon name="check" /> <span data-toast-text></span>
        </div>
    </div>
@endsection
