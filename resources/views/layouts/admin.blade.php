@extends('layouts.base')

@section('title', 'Administración — VoluntMatch')

@php
    $admin = auth()->user();
    $iniciales = collect(explode(' ', trim($admin->name)))
        ->filter()
        ->map(fn ($palabra) => mb_strtoupper(mb_substr($palabra, 0, 1)))
        ->take(2)
        ->implode('');

    $pendientesTotal = array_sum($adminPendientes);
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
                <span>{{ $iniciales ?: 'AD' }}</span>
                <div>
                    <b>{{ $admin->name }}</b>
                    <small>Administración · VoluntMatch</small>
                </div>
            </div>
            <nav>
                <a href="{{ route('dashboard-admin') }}" class="{{ request()->routeIs('dashboard-admin') ? 'active' : '' }}">
                    <x-icon name="spark" /> <span>Dashboard maestro</span>
                </a>
                <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios*') ? 'active' : '' }}">
                    <x-icon name="users" /> <span>Usuarios</span>
                    @if ($adminPendientes['reportes'] > 0)
                        <i>{{ $adminPendientes['reportes'] }}</i>
                    @endif
                </a>
                <a href="{{ route('admin.organizaciones.index') }}" class="{{ request()->routeIs('admin.organizaciones*') ? 'active' : '' }}">
                    <x-icon name="check" /> <span>Verificar ONG</span>
                    @if ($adminPendientes['organizaciones'] > 0)
                        <i>{{ $adminPendientes['organizaciones'] }}</i>
                    @endif
                </a>
                <a href="{{ route('admin.oportunidades.index') }}" class="{{ request()->routeIs('admin.oportunidades*') ? 'active' : '' }}">
                    <x-icon name="heart" /> <span>Oportunidades</span>
                </a>
                <a href="{{ route('admin.reportes.index') }}" class="{{ request()->routeIs('admin.reportes*') ? 'active' : '' }}">
                    <x-icon name="close" /> <span>Reportes</span>
                    @if ($adminPendientes['reportes'] > 0)
                        <i>{{ $adminPendientes['reportes'] }}</i>
                    @endif
                </a>
                <a href="{{ route('admin.tickets.index') }}" class="{{ request()->routeIs('admin.tickets*') ? 'active' : '' }}">
                    <x-icon name="users" /> <span>Soporte</span>
                    @if ($adminPendientes['tickets'] > 0)
                        <i>{{ $adminPendientes['tickets'] }}</i>
                    @endif
                </a>
                <a href="{{ route('admin.anuncios.index') }}" class="{{ request()->routeIs('admin.anuncios*') ? 'active' : '' }}">
                    <x-icon name="spark" /> <span>Anuncios</span>
                </a>
                <a href="{{ route('admin.analiticas') }}" class="{{ request()->routeIs('admin.analiticas') ? 'active' : '' }}">
                    <x-icon name="heart" /> <span>Analíticas</span>
                </a>
                <a href="{{ route('admin.configuracion') }}" class="{{ request()->routeIs('admin.configuracion*') ? 'active' : '' }}">
                    <x-icon name="check" /> <span>Configuración</span>
                </a>
                <a href="{{ route('admin.actividad') }}" class="{{ request()->routeIs('admin.actividad') ? 'active' : '' }}">
                    <x-icon name="clock" /> <span>Auditoría</span>
                </a>
            </nav>
            <div class="sidebar-help">
                <x-icon name="heart" />
                <b>¿Necesitas ayuda?</b>
                <small>{{ $pendientesTotal }} tareas pendientes.</small>
                <a href="{{ route('admin.tickets.index') }}">Ir a soporte</a>
            </div>
            <a class="logout-button" href="{{ route('logout') }}">
                Cerrar sesión <x-icon name="arrow" />
            </a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-top">
                <div>
                    <p>CENTRO DE CONTROL · @yield('panel-seccion', 'RESUMEN')</p>
                    <h2>@yield('panel-titulo', 'El pulso de VoluntMatch.')</h2>
                </div>
                <div class="dashboard-top-actions">
                    <form class="admin-search" method="GET" action="{{ route('admin.buscar') }}" role="search">
                        <x-icon name="users" :size="16" />
                        <input type="search" name="q" value="{{ request('q') }}"
                            placeholder="Buscar en VoluntMatch" aria-label="Buscar usuarios, ONG y oportunidades">
                    </form>
                    <button class="dash-theme-button" type="button" data-theme-toggle
                        aria-label="Alternar modo oscuro">
                        <span class="theme-icon-light"><x-icon name="moon" /></span>
                        <span class="theme-icon-dark"><x-icon name="sun" /></span>
                    </button>
                    <a class="notification-button" href="{{ route('admin.reportes.index') }}"
                        aria-label="{{ $pendientesTotal }} tareas pendientes">
                        <span>{{ $pendientesTotal }}</span>
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

            @yield('admin-content')
        </main>
    </div>
@endsection
