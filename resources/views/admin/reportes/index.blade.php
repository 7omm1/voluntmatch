@extends('layouts.admin')

@section('title', 'Reportes — VoluntMatch')
@section('panel-seccion', 'MODERACIÓN')
@section('panel-titulo', 'Bandeja de reportes.')

@section('admin-content')
    <div class="admin-tabs">
        <a href="{{ route('admin.reportes.index') }}" class="{{ $estado === '' ? 'active' : '' }}">
            Todos <b>{{ $totales['todos'] }}</b>
        </a>
        <a href="{{ route('admin.reportes.index', ['estado' => 'pendiente']) }}"
            class="{{ $estado === 'pendiente' ? 'active' : '' }}">
            Pendientes <b>{{ $totales['pendientes'] }}</b>
        </a>
        <a href="{{ route('admin.reportes.index', ['estado' => 'resuelto']) }}"
            class="{{ $estado === 'resuelto' ? 'active' : '' }}">
            Resueltos <b>{{ $totales['resueltos'] }}</b>
        </a>
        <a href="{{ route('admin.reportes.index', ['estado' => 'descartado']) }}"
            class="{{ $estado === 'descartado' ? 'active' : '' }}">
            Descartados <b>{{ $totales['descartados'] }}</b>
        </a>
    </div>

    <section class="dashboard-card table-card">
        <div class="card-heading">
            <div>
                <p>SEGURIDAD</p>
                <h3>Reportes de la comunidad</h3>
            </div>
            <span>{{ $totales['pendientes'] }} por revisar</span>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.reportes.index') }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Motivo, cuenta u oportunidad">
            <select name="estado" aria-label="Filtrar por estado">
                <option value="">Todos</option>
                <option value="pendiente" @selected($estado === 'pendiente')>Pendientes</option>
                <option value="resuelto" @selected($estado === 'resuelto')>Resueltos</option>
                <option value="descartado" @selected($estado === 'descartado')>Descartados</option>
            </select>
            <button class="admin-btn" type="submit">Filtrar</button>
            @if ($q !== '' || $estado !== '')
                <a class="admin-btn ghost" href="{{ route('admin.reportes.index') }}">Limpiar</a>
            @endif
        </form>

        @forelse ($reportes as $reporte)
            <article class="admin-org-card">
                <div class="admin-row">
                    <span class="list-icon"><x-icon name="close" /></span>
                    <span class="admin-row-main">
                        <b>{{ $reporte->motivo }}</b>
                        <small>
                            {{ $reporte->created_at?->format('d/m/Y H:i') }}
                            · Reporta: {{ $reporte->reportadoPor?->name ?? 'Anónimo' }}
                            @if ($reporte->user)
                                · Cuenta: {{ $reporte->user->name }} ({{ $reporte->user->email }})
                            @endif
                            @if ($reporte->oportunidad)
                                · Oportunidad: {{ $reporte->oportunidad->titulo }}
                            @endif
                        </small>
                    </span>
                    <span class="status {{ $reporte->estado }}">{{ ucfirst($reporte->estado) }}</span>
                </div>

                @if ($reporte->detalle)
                    <p class="admin-org-mission">{{ $reporte->detalle }}</p>
                @endif

                @if ($reporte->estado !== 'pendiente')
                    <div class="admin-note-box">
                        <b>Resolución · {{ $reporte->resueltoPor?->name ?? 'administración' }}</b>
                        <p>{{ $reporte->resolucion ?: 'Sin nota adicional.' }}</p>
                    </div>
                    @if ($reporte->user)
                        <a class="admin-btn tiny ghost" href="{{ route('admin.usuarios.show', $reporte->user) }}">Ver cuenta</a>
                    @endif
                @else
                    <details class="admin-details">
                        <summary>Gestionar reporte</summary>
                        <div class="admin-actions-bar">
                            <form class="admin-inline-form" method="POST" action="{{ route('admin.reportes.resolver', $reporte) }}">
                                @csrf
                                @method('patch')
                                <input type="text" name="nota" maxlength="2000" placeholder="Nota de resolución (opcional)">
                                <button class="admin-btn tiny" type="submit">Resolver</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reportes.descartar', $reporte) }}">
                                @csrf
                                @method('patch')
                                <button class="admin-btn tiny danger" type="submit">Descartar</button>
                            </form>
                            @if ($reporte->user)
                                <a class="admin-btn tiny ghost" href="{{ route('admin.usuarios.show', $reporte->user) }}">Ver cuenta</a>
                            @endif
                        </div>
                    </details>
                @endif
            </article>
        @empty
            <p class="admin-empty">No hay reportes que coincidan con el filtro.</p>
        @endforelse

        {{ $reportes->links('partials.paginacion') }}
    </section>
@endsection
