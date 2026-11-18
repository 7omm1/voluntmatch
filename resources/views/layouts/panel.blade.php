@extends('layouts.base')

@section('title', 'Mi espacio — VoluntMatch')

@php
    $esOng = auth()->user()->role === 'organizacion';
    $panelClass = $esOng ? 'dashboard-ngo' : 'dashboard-student';
    $zona = $esOng ? 'PANEL ONG' : 'ESPACIO VOLUNTMATCH';
    $rolLegible = $esOng
        ? (($organizacion->verificada ?? false) ? 'ONG verificada' : 'ONG pendiente de verificación')
        : 'Estudiante · VoluntMatch';
    $alertas = $noLeidos ?? 0;
@endphp

@section('content')
    <div class="dashboard-shell {{ $panelClass }}">
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
                <span>{{ $initials ?: ($esOng ? 'NG' : 'NX') }}</span>
                <div>
                    <b>{{ $user->name }}</b>
                    <small>{{ $rolLegible }}</small>
                </div>
            </div>
            <nav>
                @include($esOng ? 'panel.partials.nav-ong' : 'panel.partials.nav-voluntario')
            </nav>
            <div class="sidebar-help">
                <span class="sidebar-help-icon"><x-icon name="heart" /></span>
                <b>¿Necesitas ayuda?</b>
                <small>Estamos contigo. Respondemos en menos de 24 h hábiles.</small>
                <button type="button" class="sidebar-help-button" data-support-open>
                    Contactar soporte <x-icon name="arrow" :size="12" />
                </button>
                <a class="sidebar-help-link" href="{{ $esOng ? route('ong.mensajes') : route('voluntario.mensajes') }}">
                    Escribir al equipo
                </a>
            </div>
            <a class="logout-button" href="{{ route('logout') }}">
                Cerrar sesión <x-icon name="arrow" />
            </a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-top">
                <div>
                    <p>{{ $zona }} · @yield('panel-section', 'RESUMEN')</p>
                    <h2>@yield('panel-heading', 'Hola de nuevo.')</h2>
                </div>
                <div class="dashboard-top-actions">
                    @yield('panel-actions')
                    <button class="dash-theme-button" type="button" data-theme-toggle
                        aria-label="Alternar modo oscuro">
                        <span class="theme-icon-light"><x-icon name="moon" /></span>
                        <span class="theme-icon-dark"><x-icon name="sun" /></span>
                    </button>
                    <a class="notification-button" href="{{ $esOng ? route('ong.candidatos') : route('voluntario.postulaciones') }}"
                        aria-label="Notificaciones pendientes">
                        <span>{{ $alertas }}</span>
                        <x-icon name="spark" />
                    </a>
                    <a class="dashboard-close" href="{{ route('index') }}">
                        <x-icon name="close" /> Volver a la web
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="admin-flash success" role="status">
                    <x-icon name="check" :size="16" /> {{ session('status') }}
                </div>
            @endif
            @if (session('success'))
                <div class="admin-flash success" role="status">
                    <x-icon name="check" :size="16" /> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="admin-flash error" role="alert">
                    <x-icon name="close" :size="16" /> {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="admin-flash error" role="alert">
                    <x-icon name="close" :size="16" />
                    <span>Revisa los datos: {{ $errors->first() }}</span>
                </div>
            @endif

            @yield('panel-content')
        </main>

        <div class="panel-modals-slot" data-panel-modals>
            @yield('panel-modals')
        </div>

        @if (! $esOng)
            @include('panel.partials.perfil-modal')
        @endif

        <button type="button" class="panel-help-fab" data-support-open aria-label="Contactar soporte">
            <x-icon name="heart" :size="18" />
            <span>Ayuda</span>
        </button>

        <div class="project-modal-backdrop" data-support-modal hidden>
            <article class="support-modal" role="dialog" aria-modal="true"
                aria-labelledby="support-modal-title">
                <button type="button" class="modal-close" data-support-close aria-label="Cerrar">
                    <x-icon name="close" />
                </button>

                <div data-support-intro>
                    <p class="eyebrow dark-text"><span></span> SOPORTE VOLUNTMATCH</p>
                    <h2 id="support-modal-title">¿En qué te ayudamos?</h2>
                    <p class="support-lead">
                        Cuéntanos qué necesitas y una persona del equipo te responderá a tu correo
                        registrado. También puedes escribirnos por los canales rápidos.
                    </p>

                    <div class="support-channels">
                        <a class="support-channel"
                            href="{{ $esOng ? route('ong.mensajes') : route('voluntario.mensajes') }}">
                            <span class="support-channel-icon"><x-icon name="users" :size="17" /></span>
                            <b>Mensajes del equipo</b>
                            <small>Conversa con la organización o con soporte dentro del panel.</small>
                        </a>
                        <a class="support-channel" href="{{ route('contacto') }}" target="_blank" rel="noopener">
                            <span class="support-channel-icon"><x-icon name="arrow" :size="17" /></span>
                            <b>Correo y contacto</b>
                            <small>Formulario público y horario de atención de lunes a viernes.</small>
                        </a>
                    </div>

                    <div class="support-faq" data-support-faq>
                        <details>
                            <summary>¿Por qué no veo nuevas tarjetas en Descubrir matches?</summary>
                            <p>La baraja muestra retos a los que aún no te postulaste. Si ya viste todos,
                                aparecerán nuevos cuando las ONG publiquen más oportunidades.</p>
                        </details>
                        <details>
                            <summary>¿Cómo puedo mejorar mis matches?</summary>
                            <p>Completa tu Skills Passport: la habilidad, la disponibilidad y las causas
                                que eliges se usan para calcular tu afinidad con cada reto.</p>
                        </details>
                        <details>
                            <summary>¿Cómo sigo el estado de mi postulación?</summary>
                            <p>Abre <b>Postulaciones</b> en el menú. Al tocar una fila verás el estado,
                                la línea de tiempo y los siguientes pasos.</p>
                        </details>
                        <details>
                            <summary>Tengo un problema con mi cuenta o con una ONG</summary>
                            <p>Escríbenos con el asunto «Cuenta» o «Organización». Si puedes, incluye el
                                correo de tu cuenta y el nombre de la oportunidad para ubicar el caso.</p>
                        </details>
                    </div>

                    <form class="support-form" data-support-form method="POST"
                        action="{{ route('soporte.store') }}">
                        @csrf
                        <div class="support-form-grid">
                            <label>Asunto
                                <input type="text" name="asunto" required maxlength="160"
                                    placeholder="Ej. No puedo enviar mi postulación" />
                            </label>
                            <label>Prioridad
                                <select name="prioridad">
                                    <option value="media">Normal</option>
                                    <option value="alta">Urgente</option>
                                    <option value="baja">Puede esperar</option>
                                </select>
                            </label>
                        </div>
                        <label>Cuéntanos qué pasa
                            <textarea name="mensaje" rows="4" required maxlength="3000"
                                placeholder="Incluye el nombre de la oportunidad, el correo de tu cuenta o cualquier dato que nos ayude a revisar tu caso."></textarea>
                        </label>
                        <div class="form-error" data-support-error hidden></div>
                        <div class="support-form-actions">
                            <small><x-icon name="check" :size="12" /> Te responderemos a
                                {{ auth()->user()->email }}</small>
                            <button type="submit" class="jelly-button">
                                Enviar a soporte <x-icon name="arrow" :size="14" />
                            </button>
                        </div>
                    </form>
                </div>

                <div class="support-success" data-support-success hidden>
                    <span class="support-success-icon"><x-icon name="check" :size="26" /></span>
                    <h2>¡Caso recibido!</h2>
                    <p data-support-success-text>
                        Guardamos tu mensaje y el equipo de soporte te responderá muy pronto.
                    </p>
                    <div class="support-success-actions">
                        <a class="jelly-button small"
                            href="{{ $esOng ? route('ong.mensajes') : route('voluntario.mensajes') }}">
                            Ver mis mensajes <x-icon name="arrow" :size="14" />
                        </a>
                        <button type="button" class="ghost-button small" data-support-reset>
                            Enviar otro caso
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <div class="dashboard-toast" data-toast hidden>
            <x-icon name="check" /> <span data-toast-text></span>
        </div>
    </div>
@endsection
