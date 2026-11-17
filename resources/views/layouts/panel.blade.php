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
                <x-icon name="heart" />
                <b>¿Necesitas ayuda?</b>
                <small>Estamos contigo.</small>
                <a href="{{ $esOng ? route('ong.mensajes') : route('voluntario.mensajes') }}">Contactar</a>
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

        @yield('panel-modals')

        <div class="dashboard-toast" data-toast hidden>
            <x-icon name="check" /> <span data-toast-text></span>
        </div>
    </div>
@endsection
