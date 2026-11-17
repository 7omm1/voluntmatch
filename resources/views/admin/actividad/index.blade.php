@extends('layouts.admin')

@section('title', 'Auditoría — VoluntMatch')
@section('panel-seccion', 'AUDITORÍA')
@section('panel-titulo', 'Bitácora de acciones.')

@section('admin-content')
    <section class="dashboard-card table-card">
        <div class="card-heading">
            <div>
                <p>TRAZABILIDAD</p>
                <h3>Historial administrativo</h3>
            </div>
            <span>{{ $actividad->total() }} registros</span>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.actividad') }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Acción, detalle o administrador">
            <select name="entidad" aria-label="Filtrar por entidad">
                <option value="">Todas las entidades</option>
                @foreach ($entidades as $nombre)
                    <option value="{{ $nombre }}" @selected($entidad === $nombre)>{{ $nombre }}</option>
                @endforeach
            </select>
            <button class="admin-btn" type="submit">Filtrar</button>
            @if ($q !== '' || $entidad !== '')
                <a class="admin-btn ghost" href="{{ route('admin.actividad') }}">Limpiar</a>
            @endif
        </form>

        @forelse ($actividad as $registro)
            <div class="admin-row">
                <span class="list-icon"><x-icon name="clock" /></span>
                <span class="admin-row-main">
                    <b>{{ $registro->accion }}</b>
                    <small>
                        {{ $registro->user?->name ?? 'Sistema' }}
                        @if ($registro->user)
                            ({{ $registro->user->email }})
                        @endif
                        @if ($registro->entidad)
                            · {{ $registro->entidad }} #{{ $registro->entidad_id }}
                        @endif
                        @if ($registro->detalle)
                            · {{ \Illuminate\Support\Str::limit($registro->detalle, 90) }}
                        @endif
                    </small>
                </span>
                <em>{{ $registro->created_at?->format('d/m/Y H:i') }}</em>
                <em title="Dirección IP">{{ $registro->ip ?: '—' }}</em>
            </div>
        @empty
            <p class="admin-empty">No hay acciones registradas con ese filtro.</p>
        @endforelse

        {{ $actividad->links('partials.paginacion') }}
    </section>
@endsection
