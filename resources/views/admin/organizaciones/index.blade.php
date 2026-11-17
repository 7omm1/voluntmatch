@extends('layouts.admin')

@section('title', 'Verificación de ONG — VoluntMatch')
@section('panel-seccion', 'ORGANIZACIONES')
@section('panel-titulo', 'Verificación de organizaciones.')

@section('admin-content')
    <div class="admin-tabs">
        <a href="{{ route('admin.organizaciones.index') }}" class="{{ $estado === '' ? 'active' : '' }}">
            Todas <b>{{ $totales['todas'] }}</b>
        </a>
        <a href="{{ route('admin.organizaciones.index', ['estado' => 'pendientes']) }}"
            class="{{ $estado === 'pendientes' ? 'active' : '' }}">
            Pendientes <b>{{ $totales['pendientes'] }}</b>
        </a>
        <a href="{{ route('admin.organizaciones.index', ['estado' => 'verificadas']) }}"
            class="{{ $estado === 'verificadas' ? 'active' : '' }}">
            Verificadas <b>{{ $totales['verificadas'] }}</b>
        </a>
    </div>

    <section class="dashboard-card verification-card">
        <div class="card-heading">
            <div>
                <p>SEGURIDAD DEL ECOSISTEMA</p>
                <h3>Expedientes de ONG</h3>
            </div>
            <a class="admin-btn ghost" href="{{ route('admin.organizaciones.exportar') }}">
                <x-icon name="download" :size="14" /> Exportar CSV
            </a>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.organizaciones.index') }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Nombre, RUC, sector o correo">
            <select name="estado" aria-label="Filtrar por estado">
                <option value="">Todas</option>
                <option value="pendientes" @selected($estado === 'pendientes')>Pendientes</option>
                <option value="verificadas" @selected($estado === 'verificadas')>Verificadas</option>
            </select>
            <button class="admin-btn" type="submit">Filtrar</button>
            @if ($q !== '' || $estado !== '')
                <a class="admin-btn ghost" href="{{ route('admin.organizaciones.index') }}">Limpiar</a>
            @endif
        </form>

        @forelse ($organizaciones as $organizacion)
            <article class="admin-org-card">
                <div class="admin-row">
                    <span class="org-seal"><x-icon name="users" /></span>
                    <span class="admin-row-main">
                        <b>{{ $organizacion->nombre }}</b>
                        <small>
                            RUC {{ $organizacion->ruc ?: 'no declarado' }}
                            · {{ $organizacion->sector ?: 'Sector no declarado' }}
                            @if ($organizacion->user)
                                · {{ $organizacion->user->email }}
                            @endif
                        </small>
                    </span>
                    <span class="status {{ $organizacion->verificada ? 'verificada' : 'pendiente' }}">
                        {{ $organizacion->verificada ? 'Verificada' : 'Pendiente' }}
                    </span>
                </div>

                @if ($organizacion->mision)
                    <p class="admin-org-mission">{{ $organizacion->mision }}</p>
                @endif

                <div class="admin-org-meta">
                    <span><b>Contacto:</b> {{ $organizacion->user?->name ?? 'Sin cuenta vinculada' }}</span>
                    <span><b>Teléfono:</b> {{ $organizacion->user?->phone ?: '—' }}</span>
                    <span><b>Ubicación:</b> {{ $organizacion->user?->city ?: '—' }}{{ $organizacion->user?->region ? ', '.$organizacion->user->region : '' }}</span>
                    <span><b>Expediente:</b> {{ $organizacion->created_at?->format('d/m/Y') }}</span>
                    @if ($organizacion->verificada_at)
                        <span><b>Verificada:</b> {{ $organizacion->verificada_at->format('d/m/Y') }}
                            por {{ $organizacion->verificadaPor?->name ?? 'administración' }}</span>
                    @endif
                </div>

                @if ($organizacion->verificacion_nota)
                    <div class="admin-note-box">
                        <b>Nota de verificación</b>
                        <p>{{ $organizacion->verificacion_nota }}</p>
                    </div>
                @endif

                <form class="admin-inline-form" method="POST" action="{{ route('admin.organizaciones.revisar', $organizacion) }}">
                    @csrf
                    @method('patch')
                    <input type="text" name="nota" maxlength="255" placeholder="Nota de la revisión (opcional)">
                    <button class="admin-btn" type="submit" name="accion" value="verificar">Verificar</button>
                    <button class="admin-btn danger" type="submit" name="accion" value="rechazar"
                        onclick="return confirm('¿Rechazar la verificación de esta organización?')">
                        Rechazar
                    </button>
                </form>
            </article>
        @empty
            <p class="admin-empty">No hay organizaciones que coincidan con el filtro.</p>
        @endforelse

        {{ $organizaciones->links('partials.paginacion') }}
    </section>
@endsection
