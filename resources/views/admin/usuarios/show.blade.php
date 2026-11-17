@extends('layouts.admin')

@section('title', 'Ficha de '.$usuario->name.' — VoluntMatch')
@section('panel-seccion', 'USUARIOS')
@section('panel-titulo', $usuario->name)

@section('admin-content')
    <a class="admin-back" href="{{ route('admin.usuarios.index') }}">← Volver a usuarios</a>

    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="admin-profile-head">
                <span class="admin-avatar large">{{ mb_strtoupper(mb_substr($usuario->name, 0, 2)) }}</span>
                <div>
                    <h3>{{ $usuario->name }}</h3>
                    <p>{{ $usuario->email }}</p>
                    <div class="admin-chip-row">
                        <span class="status {{ $usuario->role }}">{{ $usuario->rolLegible() }}</span>
                        <span class="status {{ $usuario->estaBloqueado() ? 'bloqueado' : 'activo' }}">
                            {{ $usuario->estaBloqueado() ? 'Suspendido' : 'Activo' }}
                        </span>
                        @if ($usuario->organizacion)
                            <span class="status {{ $usuario->organizacion->verificada ? 'verificada' : 'pendiente' }}">
                                ONG {{ $usuario->organizacion->verificada ? 'verificada' : 'pendiente' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="admin-detail-grid">
                <div><dt>Teléfono</dt><dd>{{ $usuario->phone ?: '—' }}</dd></div>
                <div><dt>Región</dt><dd>{{ $usuario->region ?: '—' }}</dd></div>
                <div><dt>Ciudad</dt><dd>{{ $usuario->city ?: '—' }}</dd></div>
                <div><dt>DNI</dt><dd>{{ $usuario->dni ?: '—' }}</dd></div>
                <div><dt>RUC</dt><dd>{{ $usuario->ruc ?: '—' }}</dd></div>
                <div><dt>Universidad</dt><dd>{{ $usuario->university ?: '—' }}</dd></div>
                <div><dt>Carrera</dt><dd>{{ $usuario->career ?: '—' }}</dd></div>
                <div><dt>Ciclo</dt><dd>{{ $usuario->cycle ?: '—' }}</dd></div>
                <div><dt>Disponibilidad</dt><dd>{{ $usuario->availability ?: '—' }}</dd></div>
                <div><dt>Sector</dt><dd>{{ $usuario->sector ?: '—' }}</dd></div>
                <div><dt>Sitio web</dt><dd>{{ $usuario->website ?: '—' }}</dd></div>
                <div><dt>Registro</dt><dd>{{ $usuario->created_at?->format('d/m/Y H:i') }}</dd></div>
                <div><dt>Último acceso</dt><dd>{{ $usuario->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd></div>
                <div><dt>Reportes recibidos</dt><dd>{{ $reportes->count() }}</dd></div>
            </dl>

            @if ($usuario->mission)
                <div class="admin-note-box">
                    <b>Misión declarada</b>
                    <p>{{ $usuario->mission }}</p>
                </div>
            @endif

            @if ($usuario->estaBloqueado() && $usuario->blocked_reason)
                <div class="admin-flash error">
                    <x-icon name="close" :size="16" />
                    Motivo de suspensión: {{ $usuario->blocked_reason }}
                </div>
            @endif

            <h4 class="admin-subtitle">Postulaciones ({{ $usuario->postulaciones->count() }})</h4>
            @forelse ($usuario->postulaciones as $postulacion)
                <div class="admin-row compact">
                    <span class="list-icon"><x-icon name="heart" /></span>
                    <span class="admin-row-main">
                        <b>{{ $postulacion->oportunidad?->titulo ?? 'Oportunidad eliminada' }}</b>
                        <small>{{ $postulacion->created_at?->format('d/m/Y') }} · {{ $postulacion->nota ?: 'Sin mensaje' }}</small>
                    </span>
                    <span class="status {{ str_replace(' ', '-', $postulacion->estado) }}">{{ ucfirst($postulacion->estado) }}</span>
                </div>
            @empty
                <p class="admin-empty">Esta cuenta todavía no tiene postulaciones.</p>
            @endforelse
        </section>

        <div class="admin-side-stack">
            <section class="dashboard-card">
                <div class="card-heading">
                    <div><p>ACCIONES</p><h3>Moderación</h3></div>
                </div>

                @if (! $usuario->is(auth()->user()) && $usuario->role !== 'admin')
                    @if ($usuario->estaBloqueado())
                        <form class="admin-stack" method="POST" action="{{ route('admin.usuarios.reactivar', $usuario) }}">
                            @csrf
                            @method('patch')
                            <button class="admin-btn" type="submit">Reactivar cuenta</button>
                        </form>
                    @else
                        <form class="admin-stack" method="POST" action="{{ route('admin.usuarios.bloquear', $usuario) }}">
                            @csrf
                            @method('patch')
                            <label>Motivo de la suspensión
                                <input type="text" name="motivo" maxlength="255" placeholder="Ej. suplantación de identidad">
                            </label>
                            <button class="admin-btn danger" type="submit"
                                onclick="return confirm('¿Suspender esta cuenta? El usuario perderá el acceso.')">
                                Suspender cuenta
                            </button>
                        </form>
                    @endif
                @else
                    <p class="admin-empty">Las cuentas administradoras no se pueden suspender desde aquí.</p>
                @endif

                @if (! $usuario->is(auth()->user()))
                    <form class="admin-stack" method="POST" action="{{ route('admin.usuarios.rol', $usuario) }}">
                        @csrf
                        @method('patch')
                        <label>Rol de la cuenta
                            <select name="role">
                                <option value="voluntario" @selected($usuario->role === 'voluntario')>Voluntario</option>
                                <option value="organizacion" @selected($usuario->role === 'organizacion')>Organización</option>
                                <option value="admin" @selected($usuario->role === 'admin')>Administrador</option>
                            </select>
                        </label>
                        <button class="admin-btn ghost" type="submit">Cambiar rol</button>
                    </form>
                @endif
            </section>

            <section class="dashboard-card">
                <div class="card-heading">
                    <div><p>SEGUIMIENTO</p><h3>Notas internas</h3></div>
                </div>
                <form class="admin-stack" method="POST" action="{{ route('admin.usuarios.notas', $usuario) }}">
                    @csrf
                    @method('patch')
                    <textarea name="admin_notes" rows="6" maxlength="2000"
                        placeholder="Solo visible para administración">{{ $usuario->admin_notes }}</textarea>
                    <button class="admin-btn" type="submit">Guardar notas</button>
                </form>
            </section>

            <section class="dashboard-card">
                <div class="card-heading">
                    <div><p>INCIDENCIAS</p><h3>Registrar reporte</h3></div>
                </div>
                <form class="admin-stack" method="POST" action="{{ route('admin.reportes.store', $usuario) }}">
                    @csrf
                    <label>Motivo
                        <input type="text" name="motivo" required maxlength="120" placeholder="Ej. perfil falso">
                    </label>
                    <label>Detalle
                        <textarea name="detalle" rows="4" maxlength="2000" placeholder="Contexto para el equipo"></textarea>
                    </label>
                    <button class="admin-btn ghost" type="submit">Registrar incidencia</button>
                </form>

                @forelse ($reportes as $reporte)
                    <div class="admin-row compact">
                        <span class="list-icon"><x-icon name="close" /></span>
                        <span class="admin-row-main">
                            <b>{{ $reporte->motivo }}</b>
                            <small>{{ $reporte->created_at?->format('d/m/Y') }} · {{ $reporte->reportadoPor?->name ?? 'Anónimo' }}</small>
                        </span>
                        <span class="status {{ $reporte->estado }}">{{ ucfirst($reporte->estado) }}</span>
                    </div>
                @empty
                    <p class="admin-empty">Sin reportes asociados.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
