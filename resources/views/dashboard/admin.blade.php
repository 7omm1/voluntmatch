@extends('layouts.base')

@section('title', 'Centro de control — VoluntMatch')

@php
    $user = auth()->user();
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@section('content')
    <div class="dashboard-shell dashboard-admin">
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
                <span>{{ $initials ?: 'AD' }}</span>
                <div>
                    <b>{{ $user->name }}</b>
                    <small>Administración · VoluntMatch</small>
                </div>
            </div>
            <nav>
                <button type="button" class="active" data-section="overview" data-title="Dashboard maestro">
                    <x-icon name="spark" /> <span>Dashboard maestro</span>
                </button>
                <button type="button" data-section="verification" data-title="Verificar ONG">
                    <x-icon name="check" /> <span>Verificar ONG</span>
                </button>
                <button type="button" data-section="users" data-title="Usuarios y reportes">
                    <x-icon name="users" /> <span>Usuarios y reportes</span>
                </button>
                <button type="button" data-section="analytics" data-title="Analíticas">
                    <x-icon name="heart" /> <span>Analíticas</span>
                </button>
                <button type="button" data-section="algorithm" data-title="Algoritmo">
                    <x-icon name="spark" /> <span>Algoritmo</span>
                </button>
                <button type="button" data-section="support" data-title="Soporte">
                    <x-icon name="users" /> <span>Soporte</span>
                </button>
            </nav>
            <div class="sidebar-help">
                <x-icon name="heart" />
                <b>¿Necesitas ayuda?</b>
                <small>Estamos contigo.</small>
                <button type="button" data-goto="support">Contactar</button>
            </div>
            <a class="logout-button" href="{{ route('logout') }}">
                Cerrar sesión <x-icon name="arrow" />
            </a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-top">
                <div>
                    <p>CENTRO DE CONTROL · DASHBOARD MAESTRO</p>
                    <h2 data-dash-heading>El pulso de VoluntMatch.</h2>
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

            {{-- DASHBOARD MAESTRO --}}
            <div data-panel="overview" data-heading="El pulso de VoluntMatch.">
                <div class="metric-grid metric-grid-four">
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>2.842</strong>
                        <p>Estudiantes activos</p>
                        <small>↗ 3% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>186</strong>
                        <p>ONG verificadas</p>
                        <small>↗ 4% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="heart" /></span>
                        <strong>1.304</strong>
                        <p>Matches exitosos</p>
                        <small>↗ 5% este mes</small>
                    </article>
                    <article>
                        <span><x-icon name="spark" /></span>
                        <strong>12.4K</strong>
                        <p>Horas generadas</p>
                        <small>↗ 6% este mes</small>
                    </article>
                </div>
                <div class="overview-grid">
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>CRECIMIENTO DEL ECOSISTEMA</p>
                                <h3>Actividad de los últimos 7 días</h3>
                            </div>
                            <button type="button" data-goto="analytics">
                                Ver todo <x-icon name="arrow" />
                            </button>
                        </div>
                        <div class="master-chart">
                            @foreach ([42, 58, 46, 72, 66, 84, 91] as $height)
                                <span style="height: {{ $height }}%"></span>
                            @endforeach
                        </div>
                    </section>
                    <section class="dashboard-card quick-card">
                        <div class="card-heading">
                            <div>
                                <p>ACCIÓN RÁPIDA</p>
                                <h3>Tareas pendientes</h3>
                            </div>
                        </div>
                        <div class="admin-alerts">
                            <b>2</b>
                            <span>ONG pendientes de verificar</span>
                        </div>
                        <div class="admin-alerts coral">
                            <b>3</b>
                            <span>Reportes por revisar</span>
                        </div>
                        <div class="quick-admin-actions">
                            <button type="button" data-goto="verification">
                                Verificar ONG <x-icon name="arrow" />
                            </button>
                            <button type="button" data-goto="users">
                                Revisar reportes <x-icon name="arrow" />
                            </button>
                        </div>
                    </section>
                </div>
            </div>

            {{-- VERIFICACIÓN DE ONG --}}
            <div data-panel="verification" data-heading="Verificación de organizaciones" hidden>
                <section class="dashboard-card verification-card">
                    <div class="card-heading">
                        <div>
                            <p>SEGURIDAD DEL ECOSISTEMA</p>
                            <h3>Verificación de organizaciones</h3>
                        </div>
                        <span>2 pendientes</span>
                    </div>
                    @foreach ([
                        ['Fundación Brújula', 'G-884102', 'pendiente'],
                        ['Bosques Vivos', 'G-719830', 'pendiente'],
                        ['Red de Apoyo Joven', 'G-239114', 'verificada'],
                    ] as [$org, $id, $estado])
                        <div class="verification-row">
                            <span class="org-seal">
                                <x-icon name="users" />
                            </span>
                            <div>
                                <b>{{ $org }}</b>
                                <small>ID fiscal {{ $id }} · Documentación completa</small>
                            </div>
                            <span class="status {{ $estado }}">{{ ucfirst($estado) }}</span>
                            <button type="button" data-toast-msg="Documentos de {{ $org }} abiertos">
                                Ver documentos
                            </button>
                            <div class="admin-actions">
                                <button type="button" data-toast-msg="{{ $org }} verificada">
                                    <x-icon name="check" />
                                </button>
                                <button type="button" data-toast-msg="{{ $org }} rechazada">
                                    <x-icon name="close" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </section>
            </div>

            {{-- USUARIOS Y REPORTES --}}
            <div data-panel="users" data-heading="Usuarios y reportes" hidden>
                <section class="dashboard-card table-card">
                    <div class="card-heading">
                        <div>
                            <p>MODERACIÓN</p>
                            <h3>Usuarios y reportes</h3>
                        </div>
                        <button type="button" data-toast-msg="Registro exportado">Exportar registro</button>
                    </div>
                    @foreach ([
                        ['Daniel Rivas', 'Estudiante', 'activo', 0],
                        ['Manos Abiertas', 'ONG', 'activo', 2],
                        ['Lucía Pérez', 'Estudiante', 'activo', 1],
                    ] as [$nombre, $tipo, $estado, $reportes])
                        <div class="candidate-row">
                            <span class="list-icon">
                                <x-icon name="users" />
                            </span>
                            <span>
                                <b>{{ $nombre }}</b>
                                <small>{{ $tipo }} · {{ $reportes }} reportes</small>
                            </span>
                            <span class="status {{ $estado }}">{{ ucfirst($estado) }}</span>
                            <button type="button" data-toast-msg="Historial de {{ $nombre }} abierto">
                                Revisar
                            </button>
                            <button type="button" data-toast-msg="{{ $estado === 'activo' ? $nombre . ' bloqueado' : $nombre . ' reactivado' }}">
                                {{ $estado === 'activo' ? 'Bloquear' : 'Reactivar' }}
                            </button>
                        </div>
                    @endforeach
                </section>
            </div>

            {{-- ANALÍTICAS --}}
            <div data-panel="analytics" data-heading="Analíticas" hidden>
                <div class="analytics-layout">
                    <section class="dashboard-card analytics-main">
                        <div class="card-heading">
                            <div>
                                <p>DEMANDA</p>
                                <h3>Habilidades más solicitadas</h3>
                            </div>
                            <button type="button">Últimos 30 días <x-icon name="chevron" /></button>
                        </div>
                        <div class="horizontal-chart">
                            @foreach ([
                                ['Diseño UX/UI', 92],
                                ['Comunicación', 81],
                                ['Python y datos', 74],
                                ['Marketing digital', 68],
                                ['Idiomas', 53],
                            ] as [$label, $value])
                                <div>
                                    <span>{{ $label }}</span>
                                    <i><b style="width: {{ $value }}%"></b></i>
                                    <em>{{ $value }}%</em>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <section class="dashboard-card">
                        <div class="card-heading">
                            <div>
                                <p>UNIVERSIDADES</p>
                                <h3>Carreras más activas</h3>
                            </div>
                        </div>
                        <div class="donut-wrap">
                            <div class="donut">
                                <span>2.8K<small>estudiantes</small></span>
                            </div>
                            <ul>
                                <li><i></i> Comunicación 34%</li>
                                <li><i></i> Ingeniería 28%</li>
                                <li><i></i> Diseño 22%</li>
                                <li><i></i> Otras 16%</li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>

            {{-- ALGORITMO --}}
            <div data-panel="algorithm" data-heading="Algoritmo de matching" hidden>
                <section class="dashboard-card algorithm-card">
                    <div class="card-heading">
                        <div>
                            <p>RECOMENDACIÓN</p>
                            <h3>Ajuste del algoritmo de matching</h3>
                        </div>
                        <span>Modo seguro</span>
                    </div>
                    @foreach ([
                        ['Afinidad de habilidades', 72],
                        ['Compatibilidad horaria', 58],
                        ['Interés por la causa', 84],
                        ['Proximidad geográfica', 35],
                    ] as [$label, $value])
                        <label>
                            <span>
                                <b>{{ $label }}</b>
                                <em data-range-value>{{ $value }}%</em>
                            </span>
                            <input type="range" min="0" max="100" value="{{ $value }}" data-range />
                            <small>Ajusta el peso de este factor en las recomendaciones.</small>
                        </label>
                    @endforeach
                    <button type="button" class="jelly-button" data-toast-msg="Configuración del algoritmo guardada">
                        Guardar configuración <x-icon name="check" />
                    </button>
                </section>
            </div>

            {{-- SOPORTE --}}
            <div data-panel="support" data-heading="Soporte técnico" hidden>
                <section class="dashboard-card table-card">
                    <div class="card-heading">
                        <div>
                            <p>SOPORTE TÉCNICO</p>
                            <h3>Casos abiertos</h3>
                        </div>
                        <span>3 prioritarios</span>
                    </div>
                    @foreach ([
                        ['#2048', 'No puedo registrar mis horas', 'Estudiante', 'alta'],
                        ['#2047', 'Error al adjuntar CIF', 'ONG', 'media'],
                        ['#2046', 'Disputa sobre entregables', 'Compartido', 'alta'],
                    ] as [$id, $titulo, $tipo, $prioridad])
                        <div class="application-row">
                            <b>{{ $id }}</b>
                            <span>
                                <b>{{ $titulo }}</b>
                                <small>{{ $tipo }} · Abierto hace 2 h</small>
                            </span>
                            <span class="priority {{ $prioridad }}">{{ ucfirst($prioridad) }}</span>
                            <button type="button" data-toast-msg="Caso {{ $id }} asignado a tu cuenta">
                                Resolver <x-icon name="arrow" />
                            </button>
                        </div>
                    @endforeach
                </section>
            </div>
        </main>

        <div class="dashboard-toast" data-toast hidden>
            <x-icon name="check" /> <span data-toast-text></span>
        </div>
    </div>
@endsection
