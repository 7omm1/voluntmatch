@extends('layouts.admin')

@section('title', 'Usuarios — VoluntMatch')
@section('panel-seccion', 'USUARIOS')
@section('panel-titulo', 'Comunidad y moderación.')

@section('admin-content')
    <div class="admin-tabs">
        <a href="{{ route('admin.usuarios.index') }}" class="{{ $rol === '' && $estado === '' ? 'active' : '' }}">
            Todos <b>{{ $totales['todos'] }}</b>
        </a>
        <a href="{{ route('admin.usuarios.index', ['rol' => 'voluntario']) }}" class="{{ $rol === 'voluntario' ? 'active' : '' }}">
            Voluntarios <b>{{ $totales['voluntarios'] }}</b>
        </a>
        <a href="{{ route('admin.usuarios.index', ['rol' => 'organizacion']) }}" class="{{ $rol === 'organizacion' ? 'active' : '' }}">
            ONG <b>{{ $totales['organizaciones'] }}</b>
        </a>
        <a href="{{ route('admin.usuarios.index', ['estado' => 'bloqueados']) }}" class="{{ $estado === 'bloqueados' ? 'active' : '' }}">
            Suspendidos <b>{{ $totales['bloqueados'] }}</b>
        </a>
    </div>

    <section class="dashboard-card table-card">
        <div class="card-heading">
            <div>
                <p>MODERACIÓN</p>
                <h3>Cuentas registradas</h3>
            </div>
            <a class="admin-btn ghost" href="{{ route('admin.usuarios.exportar', request()->query()) }}">
                <x-icon name="download" :size="14" /> Exportar CSV
            </a>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.usuarios.index') }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Nombre, correo, DNI o RUC">
            <select name="rol" aria-label="Filtrar por rol">
                <option value="">Todos los roles</option>
                <option value="voluntario" @selected($rol === 'voluntario')>Voluntarios</option>
                <option value="organizacion" @selected($rol === 'organizacion')>Organizaciones</option>
                <option value="admin" @selected($rol === 'admin')>Administradores</option>
            </select>
            <select name="estado" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="activos" @selected($estado === 'activos')>Activos</option>
                <option value="bloqueados" @selected($estado === 'bloqueados')>Suspendidos</option>
            </select>
            <button class="admin-btn" type="submit">Filtrar</button>
            @if ($q !== '' || $rol !== '' || $estado !== '')
                <a class="admin-btn ghost" href="{{ route('admin.usuarios.index') }}">Limpiar</a>
            @endif
        </form>

        @forelse ($usuarios as $usuario)
            <div class="admin-row">
                <span class="admin-avatar">{{ mb_strtoupper(mb_substr($usuario->name, 0, 2)) }}</span>
                <span class="admin-row-main">
                    <b>{{ $usuario->name }}</b>
                    <small>
                        {{ $usuario->email }}
                        · {{ $usuario->rolLegible() }}
                        @if ($usuario->city)
                            · {{ $usuario->city }}
                        @endif
                        · Alta {{ $usuario->created_at?->format('d/m/Y') }}
                    </small>
                </span>
                @if ($usuario->estaBloqueado())
                    <span class="status bloqueado">Suspendido</span>
                @else
                    <span class="status activo">Activo</span>
                @endif
                <a class="admin-btn tiny" href="{{ route('admin.usuarios.show', $usuario) }}">Ver ficha</a>
                @if (! $usuario->is(auth()->user()) && $usuario->role !== 'admin')
                    <form method="POST"
                        action="{{ $usuario->estaBloqueado() ? route('admin.usuarios.reactivar', $usuario) : route('admin.usuarios.bloquear', $usuario) }}">
                        @csrf
                        @method('patch')
                        <button class="admin-btn tiny {{ $usuario->estaBloqueado() ? '' : 'danger' }}" type="submit"
                            onclick="return confirm('{{ $usuario->estaBloqueado() ? '¿Reactivar esta cuenta?' : '¿Suspender esta cuenta? El usuario perderá el acceso.' }}')">
                            {{ $usuario->estaBloqueado() ? 'Reactivar' : 'Suspender' }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <p class="admin-empty">No hay cuentas que coincidan con el filtro.</p>
        @endforelse

        {{ $usuarios->links('partials.paginacion') }}
    </section>
@endsection
