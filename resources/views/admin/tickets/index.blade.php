@extends('layouts.admin')

@section('title', 'Soporte — VoluntMatch')
@section('panel-seccion', 'SOPORTE')
@section('panel-titulo', 'Mesa de ayuda.')

@section('admin-content')
    <div class="admin-tabs">
        <a href="{{ route('admin.tickets.index') }}" class="{{ $estado === '' ? 'active' : '' }}">
            Todos <b>{{ $totales['todos'] }}</b>
        </a>
        <a href="{{ route('admin.tickets.index', ['estado' => 'abierto']) }}"
            class="{{ $estado === 'abierto' ? 'active' : '' }}">
            Abiertos <b>{{ $totales['abiertos'] }}</b>
        </a>
        <a href="{{ route('admin.tickets.index', ['estado' => 'en_proceso']) }}"
            class="{{ $estado === 'en_proceso' ? 'active' : '' }}">
            En proceso <b>{{ $totales['enProceso'] }}</b>
        </a>
        <a href="{{ route('admin.tickets.index', ['estado' => 'cerrado']) }}"
            class="{{ $estado === 'cerrado' ? 'active' : '' }}">
            Cerrados <b>{{ $totales['cerrados'] }}</b>
        </a>
    </div>

    <details class="dashboard-card admin-create-card">
        <summary>+ Registrar caso recibido por otro canal</summary>
        <form class="admin-form-grid" method="POST" action="{{ route('admin.tickets.store') }}">
            @csrf
            <label>Asunto
                <input type="text" name="asunto" required maxlength="160" placeholder="Ej. No puedo registrar mis horas">
            </label>
            <label>Prioridad
                <select name="prioridad" required>
                    <option value="alta">Alta</option>
                    <option value="media" selected>Media</option>
                    <option value="baja">Baja</option>
                </select>
            </label>
            <label>Cuenta asociada (opcional)
                <select name="user_id">
                    <option value="">Sin cuenta</option>
                    @foreach (\App\Models\User::orderBy('name')->get(['id', 'name', 'email']) as $cuenta)
                        <option value="{{ $cuenta->id }}">{{ $cuenta->name }} ({{ $cuenta->email }})</option>
                    @endforeach
                </select>
            </label>
            <label class="span-two">Descripción
                <textarea name="mensaje" rows="4" required maxlength="3000"
                    placeholder="Resumen del caso y datos de contacto"></textarea>
            </label>
            <button class="admin-btn" type="submit">Registrar caso</button>
        </form>
    </details>

    <section class="dashboard-card table-card">
        <div class="card-heading">
            <div>
                <p>MESA DE AYUDA</p>
                <h3>Casos de la comunidad</h3>
            </div>
            <span>{{ $totales['abiertos'] + $totales['enProceso'] }} activos</span>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.tickets.index') }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Asunto, mensaje o cuenta">
            <select name="estado" aria-label="Filtrar por estado">
                <option value="">Todos</option>
                <option value="abierto" @selected($estado === 'abierto')>Abiertos</option>
                <option value="en_proceso" @selected($estado === 'en_proceso')>En proceso</option>
                <option value="cerrado" @selected($estado === 'cerrado')>Cerrados</option>
            </select>
            <select name="prioridad" aria-label="Filtrar por prioridad">
                <option value="">Toda prioridad</option>
                <option value="alta" @selected($prioridad === 'alta')>Alta</option>
                <option value="media" @selected($prioridad === 'media')>Media</option>
                <option value="baja" @selected($prioridad === 'baja')>Baja</option>
            </select>
            <button class="admin-btn" type="submit">Filtrar</button>
            @if ($q !== '' || $estado !== '' || $prioridad !== '')
                <a class="admin-btn ghost" href="{{ route('admin.tickets.index') }}">Limpiar</a>
            @endif
        </form>

        @forelse ($tickets as $ticket)
            <article class="admin-org-card">
                <div class="admin-row">
                    <span class="list-icon"><x-icon name="users" /></span>
                    <span class="admin-row-main">
                        <b>#{{ $ticket->id }} · {{ $ticket->asunto }}</b>
                        <small>
                            {{ $ticket->user?->name ?? 'Sin cuenta' }}
                            @if ($ticket->user)
                                ({{ $ticket->user->email }})
                            @endif
                            · {{ $ticket->created_at?->format('d/m/Y H:i') }}
                        </small>
                    </span>
                    <span class="priority {{ $ticket->prioridad }}">{{ ucfirst($ticket->prioridad) }}</span>
                    <span class="status {{ $ticket->estado }}">{{ str_replace('_', ' ', ucfirst($ticket->estado)) }}</span>
                </div>

                <p class="admin-org-mission">{{ $ticket->mensaje }}</p>

                @if ($ticket->respuesta)
                    <div class="admin-note-box">
                        <b>Respuesta · {{ $ticket->atendidoPor?->name ?? 'administración' }}</b>
                        <p>{{ $ticket->respuesta }}</p>
                    </div>
                @endif

                @if ($ticket->estado !== 'cerrado')
                    <details class="admin-details">
                        <summary>Responder caso</summary>
                        <form class="admin-stack" method="POST" action="{{ route('admin.tickets.responder', $ticket) }}">
                            @csrf
                            @method('patch')
                            <textarea name="respuesta" rows="4" required maxlength="3000"
                                placeholder="Respuesta para el usuario">{{ $ticket->respuesta }}</textarea>
                            <label class="admin-check">
                                <input type="checkbox" name="cerrar" value="1" @checked($ticket->estado === 'en_proceso')>
                                Cerrar el caso al guardar
                            </label>
                            <div class="admin-actions-bar">
                                <button class="admin-btn" type="submit">Guardar respuesta</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.tickets.cerrar', $ticket) }}">
                            @csrf
                            @method('patch')
                            <button class="admin-btn tiny danger" type="submit">Cerrar sin respuesta</button>
                        </form>
                    </details>
                @endif
            </article>
        @empty
            <p class="admin-empty">No hay casos que coincidan con el filtro.</p>
        @endforelse

        {{ $tickets->links('partials.paginacion') }}
    </section>
@endsection
