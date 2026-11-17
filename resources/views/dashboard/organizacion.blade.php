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
                                data-time="{{ $op['time'] }}" data-slug="{{ $op['slug'] }}">
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
            @php
                $estados = [
                    'all' => 'Todos',
                    'activo' => 'Activos',
                    'reclutando' => 'Reclutando',
                    'borrador' => 'Borradores',
                    'finalizado' => 'Finalizados',
                ];
                $porEstado = collect($proyectos)->groupBy('status');
            @endphp
            <div data-panel="projects" data-heading="Oportunidades" hidden>
                <section class="dashboard-card table-card project-manage">
                    <div class="card-heading">
                        <div>
                            <p>GESTIÓN DE OPORTUNIDADES</p>
                            <h3>Proyectos y retos publicados</h3>
                        </div>
                        <button type="button" data-add-project>+ Publicar necesidad</button>
                    </div>
                    <div class="project-filters">
                        @foreach ($estados as $key => $label)
                            <button type="button" class="{{ $loop->first ? 'active' : '' }}"
                                data-project-filter="{{ $key }}">
                                {{ $label }}
                                <b>{{ $key === 'all' ? count($proyectos) : ($porEstado[$key]?->count() ?? 0) }}</b>
                            </button>
                        @endforeach
                    </div>
                    <div class="talent-search project-search">
                        <x-icon name="users" />
                        <input type="text" data-project-search placeholder="Buscar proyecto por título, causa o habilidad..." />
                    </div>
                    @foreach ($proyectos as $proyecto)
                        <div class="application-row" data-project-row data-status="{{ $proyecto['status'] }}"
                            data-search="{{ strtolower($proyecto['title'] . ' ' . $proyecto['cause'] . ' ' . $proyecto['skill']) }}">
                            <span class="list-icon"><x-icon name="heart" /></span>
                            <span>
                                <b>{{ $proyecto['title'] }}</b>
                                <small>{{ $proyecto['meta'] }}</small>
                            </span>
                            @if ($proyecto['progress'])
                                @php $avance = $proyecto['progress']; @endphp
                                <span class="row-progress" data-progress>
                                    <small>{{ $avance['label'] }}: {{ $avance['done'] }}/{{ $avance['total'] }}</small>
                                    <i><b style="width: {{ min(100, round($avance['done'] / $avance['total'] * 100)) }}%"></b></i>
                                </span>
                            @endif
                            <span class="row-metrics" data-metrics>
                                @if ($proyecto['views'])
                                    <b>{{ $proyecto['views'] }}</b>
                                    <small>visualizaciones</small>
                                    <em>{{ $proyecto['conversion'] }}% postulan</em>
                                @else
                                    <b>—</b>
                                    <small>sin publicar</small>
                                @endif
                            </span>
                            <span class="status {{ $proyecto['status'] }}">{{ $proyecto['status_label'] }}</span>
                            @unless ($proyecto['status'] === 'borrador')
                                <button type="button" data-duplicate-project>Duplicar</button>
                            @endunless
                            <button type="button" data-project-open data-title="{{ $proyecto['title'] }}"
                                data-org="{{ $user->name }}" data-cause="{{ $proyecto['cause'] }}"
                                data-skill="{{ $proyecto['skill'] }}" data-time="{{ $proyecto['time'] }}"
                                data-slug="{{ $proyecto['slug'] }}">
                                Gestionar <x-icon name="arrow" />
                            </button>
                        </div>
                    @endforeach
                    <div data-projects-new></div>
                </section>
            </div>

            {{-- TALENTO --}}
            @php
                $talentFiltros = [
                    [
                        'tipo' => 'career',
                        'label' => 'Carrera',
                        'pills' => [
                            ['label' => 'Diseño', 'value' => 'Diseño'],
                            ['label' => 'Informática', 'value' => 'Informática'],
                            ['label' => 'Comunicación', 'value' => 'Comunicación'],
                            ['label' => 'Estadística', 'value' => 'Estadística'],
                        ],
                    ],
                    [
                        'tipo' => 'skill',
                        'label' => 'Habilidad',
                        'pills' => [
                            ['label' => 'Figma', 'value' => 'Figma'],
                            ['label' => 'React', 'value' => 'React'],
                            ['label' => 'Estrategia', 'value' => 'Estrategia'],
                            ['label' => 'Python', 'value' => 'Python'],
                        ],
                    ],
                    [
                        'tipo' => 'hours',
                        'label' => 'Disponibilidad',
                        'pills' => [
                            ['label' => '≤ 5 h/sem', 'value' => 'low'],
                            ['label' => '6–10 h/sem', 'value' => 'mid'],
                            ['label' => '+10 h/sem', 'value' => 'high'],
                        ],
                    ],
                    [
                        'tipo' => 'match',
                        'label' => '% Match',
                        'pills' => [
                            ['label' => '90%+', 'value' => '90'],
                            ['label' => '95%+', 'value' => '95'],
                        ],
                    ],
                ];
            @endphp
            <div data-panel="talent" data-heading="Buscar talento" hidden>
                <div class="talent-search">
                    <x-icon name="users" />
                    <input type="text" data-talent-search placeholder="Buscar por habilidad, carrera o nombre..." />
                </div>
                <div class="talent-filters">
                    @foreach ($talentFiltros as $grupo)
                        <span class="talent-filter-label">{{ $grupo['label'] }}</span>
                        @foreach ($grupo['pills'] as $pill)
                            <button type="button" data-talent-filter="{{ $grupo['tipo'] }}"
                                data-filter-value="{{ $pill['value'] }}">
                                {{ $pill['label'] }}
                            </button>
                        @endforeach
                    @endforeach
                    <button type="button" data-talent-filter="fav">
                        <x-icon name="star" :size="11" /> Favoritos <b data-fav-count>0</b>
                    </button>
                </div>
                <div class="talent-grid">
                    @foreach ($talentos as $talento)
                        <article class="talent-card" data-talent-card
                            data-search="{{ strtolower($talento['name'] . ' ' . $talento['detail'] . ' ' . implode(' ', $talento['skills'])) }}"
                            data-career="{{ $talento['career'] }}"
                            data-skills="{{ strtolower(implode(' ', $talento['skills'])) }}"
                            data-hours="{{ $talento['hours'] }}"
                            data-match="{{ $talento['match'] }}">
                            <button type="button" class="talent-save" data-talent-save
                                aria-label="Guardar {{ $talento['name'] }} en favoritos" aria-pressed="false">
                                <x-icon name="star" :size="13" />
                            </button>
                            <span class="match-score">{{ $talento['match'] }}% match</span>
                            <img src="https://i.pravatar.cc/160?img={{ $talento['img'] }}" alt="{{ $talento['name'] }}" />
                            <h3>{{ $talento['name'] }}</h3>
                            <p>{{ $talento['detail'] }}</p>
                            <div>
                                @foreach ($talento['skills'] as $skill)
                                    <span>{{ $skill }}</span>
                                @endforeach
                            </div>
                            <span class="talent-availability">
                                <x-icon name="clock" :size="11" /> Disponible: {{ $talento['hours'] }} h/sem
                            </span>
                            <div class="talent-actions">
                                <button type="button" class="talent-invite"
                                    data-toast-msg="Invitación enviada a {{ $talento['name'] }}">
                                    Invitar a proyecto <x-icon name="arrow" :size="12" />
                                </button>
                                <button type="button" class="talent-secondary"
                                    data-toast-msg="Perfil de {{ $talento['name'] }} — portafolio y LinkedIn muy pronto">
                                    Ver perfil
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="talent-empty" data-talent-empty hidden>
                    No hay perfiles que coincidan con la búsqueda y los filtros activos.
                </p>
            </div>

            {{-- CANDIDATOS --}}
            @php
                $inboxStages = [
                    ['key' => 'por-revisar', 'label' => 'Por revisar'],
                    ['key' => 'entrevista', 'label' => 'En entrevista'],
                    ['key' => 'aceptado', 'label' => 'Aceptado'],
                    ['key' => 'rechazado', 'label' => 'Rechazado'],
                ];
                $inboxStageLabel = function ($key) use ($inboxStages) {
                    foreach ($inboxStages as $stage) {
                        if ($stage['key'] === $key) {
                            return $stage['label'];
                        }
                    }

                    return $key;
                };
            @endphp
            <div data-panel="candidates" data-heading="Candidatos" hidden>
                <section class="dashboard-card table-card">
                    <div class="card-heading">
                        <div>
                            <p>MATCH INBOX</p>
                            <h3>Candidatos por revisar</h3>
                        </div>
                        <div class="inbox-actions">
                            <span data-inbox-new>0 por revisar</span>
                            <div class="inbox-view-toggle">
                                <button type="button" data-inbox-view="list" class="active">Lista</button>
                                <button type="button" data-inbox-view="kanban">Kanban</button>
                            </div>
                        </div>
                    </div>

                    {{-- Vista lista: fila por candidato con acciones directas --}}
                    <div data-inbox-list>
                        @foreach ($talentos as $talento)
                            <div class="candidate-item" data-candidate-item
                                data-candidate="{{ $talento['name'] }}"
                                data-project="{{ $talento['proyecto'] }}">
                                <div class="candidate-row" data-candidate-row data-stage="{{ $talento['stage'] }}">
                                    <img src="https://i.pravatar.cc/100?img={{ $talento['img'] }}" alt="" />
                                    <span>
                                        <b>{{ $talento['name'] }}</b>
                                        <small>{{ $talento['detail'] }} · {{ $talento['skills'][0] }}</small>
                                        <small class="candidate-project">Postula a: {{ $talento['proyecto'] }}</small>
                                    </span>
                                    <em>{{ $talento['match'] }}% match</em>
                                    <span class="status candidate-stage" data-stage-badge
                                        data-stage="{{ $talento['stage'] }}">{{ $inboxStageLabel($talento['stage']) }}</span>
                                    <button type="button" data-candidate-accept>
                                        <x-icon name="check" :size="11" /> Aceptar
                                    </button>
                                    <button type="button" data-candidate-reject>
                                        <x-icon name="close" :size="11" /> Rechazar
                                    </button>
                                    <button type="button" data-candidate-notes-toggle aria-expanded="false">
                                        <x-icon name="menu" :size="11" /> Nota
                                    </button>
                                    <button type="button" data-toast-msg="Perfil de {{ $talento['name'] }} — portafolio y LinkedIn muy pronto">
                                        Ver perfil
                                    </button>
                                </div>
                                {{-- Notas internas del equipo (se guardan en el navegador) --}}
                                <div class="candidate-notes" data-candidate-notes hidden>
                                    <textarea data-candidate-note-input rows="2"
                                        placeholder="Nota interna sobre {{ $talento['name'] }} (solo la ve tu equipo)..."></textarea>
                                    <div class="candidate-note-actions">
                                        <button type="button" data-candidate-note-save>Guardar nota</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Vista kanban: candidatos por etapa --}}
                    <div class="candidate-kanban" data-inbox-kanban hidden>
                        @foreach ($inboxStages as $stage)
                            <div class="kanban-column" data-kanban-column="{{ $stage['key'] }}">
                                <header>
                                    <span>{{ $stage['label'] }}</span>
                                    <b data-kanban-count>0</b>
                                </header>
                                <div class="kanban-cards" data-kanban-cards>
                                    @foreach ($talentos as $talento)
                                        @if ($talento['stage'] === $stage['key'])
                                            <div class="kanban-card" data-candidate-card
                                                data-candidate="{{ $talento['name'] }}"
                                                data-stage="{{ $talento['stage'] }}">
                                                <b>{{ $talento['name'] }}</b>
                                                <small>{{ $talento['match'] }}% match · {{ $talento['skills'][0] }}</small>
                                                <span>Postula a: {{ $talento['proyecto'] }}</span>
                                                <div class="kanban-card-actions">
                                                    <button type="button" data-candidate-move="prev"
                                                        aria-label="Mover a la etapa anterior">
                                                        <x-icon name="arrow" :size="12" />
                                                    </button>
                                                    <button type="button" data-candidate-notes-jump
                                                        aria-label="Ver nota interna de {{ $talento['name'] }}">
                                                        <x-icon name="menu" :size="12" />
                                                    </button>
                                                    <button type="button" data-candidate-move="next"
                                                        aria-label="Mover a la etapa siguiente">
                                                        <x-icon name="arrow" :size="12" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- MÉTRICAS --}}
            <div data-panel="impact" data-heading="Métricas" hidden>
                {{-- Barra de herramientas: rango de fechas y exportación --}}
                <section class="impact-toolbar">
                    <div class="impact-ranges">
                        @foreach ([
                            ['key' => 'mes', 'label' => 'Último mes'],
                            ['key' => 'trimestre', 'label' => 'Trimestre'],
                            ['key' => 'anio', 'label' => 'Año'],
                            ['key' => 'historico', 'label' => 'Histórico'],
                        ] as $rango)
                            <button type="button" data-impact-range="{{ $rango['key'] }}"
                                @if ($rango['key'] === 'historico') class="active" @endif>
                                {{ $rango['label'] }}
                            </button>
                        @endforeach
                    </div>
                    <div class="impact-export">
                        <button type="button" data-impact-export="csv">
                            <x-icon name="download" :size="12" /> CSV
                        </button>
                        <button type="button" data-impact-export="pdf">
                            <x-icon name="download" :size="12" /> PDF
                        </button>
                    </div>
                </section>

                <div class="analytics-layout">
                    <section class="impact-hero-card ngo-impact">
                        <div>
                            <p>IMPACTO DE LA ORGANIZACIÓN</p>
                            <h3><span data-impact-hours-label>684</span> horas de talento que multiplican vuestra misión.</h3>
                            <span>
                                Un ahorro estimado de <b data-impact-savings>S/ 74.000</b> en servicios profesionales.
                            </span>
                        </div>
                        <div class="impact-orbit">
                            <strong><span data-impact-hours-label>684</span><small>HORAS</small></strong>
                            <i></i><i></i><i></i>
                        </div>
                    </section>
                    <section class="dashboard-card evolution-card">
                        <div class="card-heading">
                            <div>
                                <p>EVOLUCIÓN DEL VOLUNTARIADO</p>
                                <h3>Horas aportadas</h3>
                            </div>
                            <span data-impact-period-label>Por año</span>
                        </div>
                        <div class="evolution-chart" data-impact-bars></div>
                    </section>
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>RESULTADOS</p>
                                <h3>Proyectos entregados</h3>
                            </div>
                        </div>
                        <div class="horizontal-chart">
                            @foreach ([['Diseño', 82], ['Tecnología', 68], ['Comunicación', 91], ['Datos', 54]] as [$area, $value])
                                <div data-impact-area>
                                    <span>{{ $area }}</span>
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
                        <div class="review-selects">
                            <select data-review-volunteer aria-label="Voluntario a evaluar">
                                <option value="">Elegir voluntario…</option>
                                @foreach ($talentos as $talento)
                                    <option value="{{ $talento['name'] }}">{{ $talento['name'] }} · {{ $talento['career'] }}</option>
                                @endforeach
                            </select>
                            <select data-review-project aria-label="Proyecto finalizado">
                                <option value="">Elegir proyecto…</option>
                                @foreach ($proyectos as $proyecto)
                                    <option value="{{ $proyecto['title'] }}">{{ $proyecto['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <button type="button" data-star aria-label="{{ $star }} estrellas">★</button>
                            @endfor
                        </div>
                        <textarea placeholder="Escribe una recomendación para su portafolio..."></textarea>
                        <button type="button" data-review-publish>
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
                            <a href="{{ route('detalle-oportunidad', ['oportunidad' => 'rediseno-web-accesible']) }}">Ver proyecto</a>
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
                        {{-- Plantillas de respuesta rápida --}}
                        <div class="chat-quick-templates" role="toolbar" aria-label="Plantillas de mensajes rápidos">
                            @foreach ([
                                'Hola, nos encantó tu perfil. ¿Tienes disponibilidad esta semana para una llamada de 15 min?',
                                'Te confirmamos la entrevista para el jueves a las 17:00. ¿Te funciona?',
                                '¡Felicidades! Fuiste seleccionado/a para el proyecto. Te envío los siguientes pasos.',
                                'Gracias por tu postulación. Seguiremos con otros perfiles por ahora, ¡éxitos!',
                            ] as $plantilla)
                                <button type="button" data-template="{{ $plantilla }}">{{ mb_substr($plantilla, 0, 34) }}…</button>
                            @endforeach
                        </div>
                        <form data-chat-form>
                            <button type="button" data-chat-attach aria-label="Adjuntar archivo">+</button>
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
                            {{-- Agendar reunión sin salir del chat --}}
                            <div class="context-schedule">
                                <button type="button" data-schedule="meet">
                                    <x-icon name="video" :size="11" /> Enlace Meet
                                </button>
                                <button type="button" data-schedule="calendar">
                                    <x-icon name="calendar" :size="11" /> Invitar
                                </button>
                            </div>
                        </div>
                        <div>
                            <span>Entregables</span>
                            <b>2 de 4 listos</b>
                        </div>
                        {{-- Gestor de archivos y enlaces compartidos en el chat --}}
                        <div class="shared-files">
                            <span>Archivos y enlaces</span>
                            @foreach ([
                                ['icon' => 'download', 'label' => 'Brief del proyecto.pdf', 'meta' => 'PDF · 240 KB'],
                                ['icon' => 'download', 'label' => 'Guion de entrevista.docx', 'meta' => 'Word · 18 KB'],
                                ['icon' => 'arrow', 'label' => 'Tablero de Figma', 'meta' => 'Enlace'],
                                ['icon' => 'download', 'label' => 'Cronograma.xlsx', 'meta' => 'Excel · 32 KB'],
                            ] as $archivo)
                                <button type="button" data-shared-file="{{ $archivo['label'] }}">
                                    <x-icon name="{{ $archivo['icon'] }}" :size="11" />
                                    <span>
                                        <b>{{ $archivo['label'] }}</b>
                                        <small>{{ $archivo['meta'] }}</small>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <a class="chat-context-link" href="{{ route('detalle-oportunidad', ['oportunidad' => 'rediseno-web-accesible']) }}">
                            Abrir ficha <x-icon name="arrow" :size="11" />
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
                        href="{{ route('detalle-oportunidad', ['oportunidad' => 'rediseno-web-accesible']) }}">
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
