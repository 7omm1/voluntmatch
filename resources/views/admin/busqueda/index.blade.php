@extends('layouts.admin')

@section('title', 'Búsqueda — VoluntMatch')
@section('panel-seccion', 'BÚSQUEDA')
@section('panel-titulo', $q !== '' ? 'Resultados para «'.$q.'»' : 'Búsqueda global.')

@section('admin-content')
    @if (! $hayBusqueda)
        <section class="dashboard-card">
            <p class="admin-empty">
                Escribe al menos dos caracteres en el buscador superior para encontrar
                cuentas, organizaciones, oportunidades, reportes o casos de soporte.
            </p>
        </section>
    @else
        @php
            $sinResultados = $usuarios->isEmpty()
                && $organizaciones->isEmpty()
                && $oportunidades->isEmpty()
                && $reportes->isEmpty()
                && $tickets->isEmpty();
        @endphp

        @if ($sinResultados)
            <section class="dashboard-card">
                <p class="admin-empty">No encontramos resultados para «{{ $q }}».</p>
            </section>
        @endif

        @if ($usuarios->isNotEmpty())
            <section class="dashboard-card">
                <div class="card-heading"><div><p>CUENTAS</p><h3>Usuarios</h3></div></div>
                @foreach ($usuarios as $usuario)
                    <div class="admin-row compact">
                        <span class="admin-avatar">{{ mb_strtoupper(mb_substr($usuario->name, 0, 2)) }}</span>
                        <span class="admin-row-main">
                            <b>{{ $usuario->name }}</b>
                            <small>{{ $usuario->email }} · {{ $usuario->rolLegible() }}</small>
                        </span>
                        <a class="admin-btn tiny" href="{{ route('admin.usuarios.show', $usuario) }}">Ver ficha</a>
                    </div>
                @endforeach
            </section>
        @endif

        @if ($organizaciones->isNotEmpty())
            <section class="dashboard-card">
                <div class="card-heading"><div><p>ORGANIZACIONES</p><h3>ONG</h3></div></div>
                @foreach ($organizaciones as $organizacion)
                    <div class="admin-row compact">
                        <span class="org-seal"><x-icon name="users" /></span>
                        <span class="admin-row-main">
                            <b>{{ $organizacion->nombre }}</b>
                            <small>RUC {{ $organizacion->ruc ?: '—' }} · {{ $organizacion->sector ?: 'Sin sector' }}</small>
                        </span>
                        <span class="status {{ $organizacion->verificada ? 'verificada' : 'pendiente' }}">
                            {{ $organizacion->verificada ? 'Verificada' : 'Pendiente' }}
                        </span>
                        <a class="admin-btn tiny" href="{{ route('admin.organizaciones.index', ['q' => $organizacion->nombre]) }}">Revisar</a>
                    </div>
                @endforeach
            </section>
        @endif

        @if ($oportunidades->isNotEmpty())
            <section class="dashboard-card">
                <div class="card-heading"><div><p>CATÁLOGO</p><h3>Oportunidades</h3></div></div>
                @foreach ($oportunidades as $oportunidad)
                    <div class="admin-row compact">
                        <span class="list-icon"><x-icon name="heart" /></span>
                        <span class="admin-row-main">
                            <b>{{ $oportunidad->titulo }}</b>
                            <small>{{ $oportunidad->causa }} · {{ $oportunidad->habilidad }} · {{ $oportunidad->estado }}</small>
                        </span>
                        <a class="admin-btn tiny" href="{{ route('admin.oportunidades.index', ['q' => $oportunidad->titulo]) }}">Gestionar</a>
                    </div>
                @endforeach
            </section>
        @endif

        @if ($reportes->isNotEmpty())
            <section class="dashboard-card">
                <div class="card-heading"><div><p>MODERACIÓN</p><h3>Reportes</h3></div></div>
                @foreach ($reportes as $reporte)
                    <div class="admin-row compact">
                        <span class="list-icon"><x-icon name="close" /></span>
                        <span class="admin-row-main">
                            <b>{{ $reporte->motivo }}</b>
                            <small>{{ $reporte->estado }} · {{ $reporte->created_at?->format('d/m/Y') }}</small>
                        </span>
                        <a class="admin-btn tiny" href="{{ route('admin.reportes.index', ['q' => $reporte->motivo]) }}">Revisar</a>
                    </div>
                @endforeach
            </section>
        @endif

        @if ($tickets->isNotEmpty())
            <section class="dashboard-card">
                <div class="card-heading"><div><p>SOPORTE</p><h3>Casos</h3></div></div>
                @foreach ($tickets as $ticket)
                    <div class="admin-row compact">
                        <span class="list-icon"><x-icon name="users" /></span>
                        <span class="admin-row-main">
                            <b>#{{ $ticket->id }} · {{ $ticket->asunto }}</b>
                            <small>{{ $ticket->estado }} · {{ $ticket->prioridad }}</small>
                        </span>
                        <a class="admin-btn tiny" href="{{ route('admin.tickets.index', ['q' => $ticket->asunto]) }}">Atender</a>
                    </div>
                @endforeach
            </section>
        @endif
    @endif
@endsection
