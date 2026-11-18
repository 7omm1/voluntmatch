@extends('layouts.app')

@section('title', 'Notificaciones — VoluntMatch')

@section('main')
    <section class="section-shell" style="padding-top:clamp(120px, 14vw, 180px)">
        <div class="section-heading">
            <p class="eyebrow">TU BANDEJA</p>
            <h1>Notificaciones.</h1>
            <p>Aquí llegan los avisos del equipo, los cambios de tus postulaciones y los mensajes de las integraciones.</p>
        </div>

        @if (session('status'))
            <div class="admin-flash success" role="status">{{ session('status') }}</div>
        @endif

        <div class="dashboard-card">
            <div class="card-heading">
                <div>
                    <p>BANDEJA INTERNA</p>
                    <h3>{{ $notificaciones->total() }} notificaciones</h3>
                </div>
                @if ($sinLeer > 0)
                    <form method="POST" action="{{ route('notificaciones.leidas') }}">
                        @csrf
                        <button class="admin-btn tiny ghost" type="submit">Marcar las {{ $sinLeer }} como leídas</button>
                    </form>
                @endif
            </div>

            @forelse ($notificaciones as $notificacion)
                @php($datos = $notificacion->data)
                <article class="admin-org-card">
                    <div class="admin-row">
                        <span class="list-icon"><x-icon name="spark" /></span>
                        <span class="admin-row-main">
                            <b>{{ $datos['titulo'] ?? 'Notificación' }}</b>
                            <small>
                                {{ $notificacion->created_at?->translatedFormat('d \d\e F, H:i') }}
                                · {{ $notificacion->read_at ? 'Leída' : 'Nueva' }}
                            </small>
                        </span>
                        @if (! empty($datos['url']))
                            <a class="admin-btn tiny ghost" href="{{ $datos['url'] }}">Abrir</a>
                        @endif
                    </div>
                    <p class="admin-org-mission">{{ $datos['cuerpo'] ?? '' }}</p>
                </article>
            @empty
                <p class="admin-empty">No tienes notificaciones todavía.</p>
            @endforelse

            {{ $notificaciones->links('partials.paginacion') }}
        </div>
    </section>
@endsection
