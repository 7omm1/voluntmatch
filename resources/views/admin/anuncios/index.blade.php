@extends('layouts.admin')

@section('title', 'Anuncios — VoluntMatch')
@section('panel-seccion', 'COMUNICACIÓN')
@section('panel-titulo', 'Anuncios para la comunidad.')

@section('admin-content')
    <div class="admin-columns">
        <section class="dashboard-card">
            <div class="card-heading">
                <div><p>NUEVO COMUNICADO</p><h3>Publicar anuncio</h3></div>
            </div>
            <form class="admin-stack" method="POST" action="{{ route('admin.anuncios.store') }}">
                @csrf
                <label>Título
                    <input type="text" name="titulo" required maxlength="160" placeholder="Ej. Nueva convocatoria de voluntariado">
                </label>
                <label>Audiencia
                    <select name="audiencia" required>
                        <option value="todos">Toda la comunidad</option>
                        <option value="voluntario">Solo voluntarios</option>
                        <option value="organizacion">Solo organizaciones</option>
                    </select>
                </label>
                <label>Contenido
                    <textarea name="cuerpo" rows="6" required maxlength="3000"
                        placeholder="Escribe el mensaje que verá la comunidad"></textarea>
                </label>
                <button class="admin-btn" type="submit">Publicar anuncio</button>
            </form>
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <div>
                    <p>HISTORIAL</p>
                    <h3>Anuncios publicados</h3>
                </div>
                <span>{{ $totales['activos'] }} activos de {{ $totales['todos'] }}</span>
            </div>

            @forelse ($anuncios as $anuncio)
                <article class="admin-org-card">
                    <div class="admin-row">
                        <span class="list-icon"><x-icon name="spark" /></span>
                        <span class="admin-row-main">
                            <b>{{ $anuncio->titulo }}</b>
                            <small>
                                {{ ucfirst($anuncio->audiencia) }}
                                · {{ $anuncio->created_at?->format('d/m/Y H:i') }}
                            </small>
                        </span>
                        <span class="status {{ $anuncio->activo ? 'activo' : 'archivada' }}">
                            {{ $anuncio->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <p class="admin-org-mission">{{ $anuncio->cuerpo }}</p>
                    <div class="admin-actions-bar">
                        <form method="POST" action="{{ route('admin.anuncios.toggle', $anuncio) }}">
                            @csrf
                            @method('patch')
                            <button class="admin-btn tiny ghost" type="submit">
                                {{ $anuncio->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.anuncios.destroy', $anuncio) }}">
                            @csrf
                            @method('delete')
                            <button class="admin-btn tiny danger" type="submit"
                                onclick="return confirm('¿Eliminar este anuncio?')">Eliminar</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="admin-empty">Todavía no se han publicado anuncios.</p>
            @endforelse

            {{ $anuncios->links('partials.paginacion') }}
        </section>
    </div>
@endsection
