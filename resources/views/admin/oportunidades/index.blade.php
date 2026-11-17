@extends('layouts.admin')

@section('title', 'Oportunidades — VoluntMatch')
@section('panel-seccion', 'OPORTUNIDADES')
@section('panel-titulo', 'Curaduría del catálogo.')

@section('admin-content')
    <div class="admin-tabs">
        <a href="{{ route('admin.oportunidades.index') }}" class="{{ $estado === '' ? 'active' : '' }}">
            Todas <b>{{ $totales['todas'] }}</b>
        </a>
        <a href="{{ route('admin.oportunidades.index', ['estado' => 'publicada']) }}"
            class="{{ $estado === 'publicada' ? 'active' : '' }}">
            Publicadas <b>{{ $totales['publicadas'] }}</b>
        </a>
        <a href="{{ route('admin.oportunidades.index', ['estado' => 'borrador']) }}"
            class="{{ $estado === 'borrador' ? 'active' : '' }}">
            Borradores <b>{{ $totales['borradores'] }}</b>
        </a>
        <a href="{{ route('admin.oportunidades.index', ['estado' => 'destacadas']) }}"
            class="{{ $estado === 'destacadas' ? 'active' : '' }}">
            Destacadas <b>{{ $totales['destacadas'] }}</b>
        </a>
    </div>

    <section class="dashboard-card table-card">
        <div class="card-heading">
            <div>
                <p>CATÁLOGO</p>
                <h3>Oportunidades publicadas</h3>
            </div>
            <a class="admin-btn ghost" href="{{ route('admin.oportunidades.exportar') }}">
                <x-icon name="download" :size="14" /> Exportar CSV
            </a>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.oportunidades.index') }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Título, habilidad, causa u ONG">
            <select name="estado" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="publicada" @selected($estado === 'publicada')>Publicadas</option>
                <option value="borrador" @selected($estado === 'borrador')>Borradores</option>
                <option value="archivada" @selected($estado === 'archivada')>Archivadas</option>
            </select>
            <button class="admin-btn" type="submit">Filtrar</button>
            @if ($q !== '' || $estado !== '')
                <a class="admin-btn ghost" href="{{ route('admin.oportunidades.index') }}">Limpiar</a>
            @endif
        </form>

        @forelse ($oportunidades as $oportunidad)
            <article class="admin-org-card">
                <div class="admin-row">
                    <span class="list-icon"><x-icon name="heart" /></span>
                    <span class="admin-row-main">
                        <b>
                            @if ($oportunidad->destacada)
                                ★
                            @endif
                            {{ $oportunidad->titulo }}
                        </b>
                        <small>
                            {{ $oportunidad->organizacion?->nombre ?? 'Sin organización' }}
                            · {{ $oportunidad->causa }} · {{ $oportunidad->habilidad }}
                            · {{ $oportunidad->modalidad }}
                        </small>
                    </span>
                    <span class="status {{ $oportunidad->estado }}">{{ ucfirst($oportunidad->estado) }}</span>
                    <em>{{ $oportunidad->postulaciones_count }} postulaciones · {{ $oportunidad->vistas }} vistas</em>
                </div>

                <div class="admin-actions-bar">
                    <form method="POST" action="{{ route('admin.oportunidades.estado', $oportunidad) }}" class="admin-inline-form">
                        @csrf
                        @method('patch')
                        <select name="estado" aria-label="Estado de la oportunidad">
                            <option value="publicada" @selected($oportunidad->estado === 'publicada')>Publicada</option>
                            <option value="borrador" @selected($oportunidad->estado === 'borrador')>Borrador</option>
                            <option value="archivada" @selected($oportunidad->estado === 'archivada')>Archivada</option>
                        </select>
                        <button class="admin-btn tiny" type="submit">Actualizar estado</button>
                    </form>

                    <form method="POST" action="{{ route('admin.oportunidades.destacar', $oportunidad) }}">
                        @csrf
                        @method('patch')
                        <button class="admin-btn tiny ghost" type="submit">
                            {{ $oportunidad->destacada ? 'Quitar destaque' : 'Destacar' }}
                        </button>
                    </form>

                    <a class="admin-btn tiny ghost" href="{{ route('detalle-oportunidad', $oportunidad) }}" target="_blank" rel="noopener">
                        Ver pública
                    </a>

                    <form method="POST" action="{{ route('admin.oportunidades.destroy', $oportunidad) }}">
                        @csrf
                        @method('delete')
                        <button class="admin-btn tiny danger" type="submit"
                            onclick="return confirm('Se eliminará la oportunidad y sus postulaciones. ¿Continuar?')">
                            Eliminar
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <p class="admin-empty">No hay oportunidades que coincidan con el filtro.</p>
        @endforelse

        {{ $oportunidades->links('partials.paginacion') }}
    </section>
@endsection
