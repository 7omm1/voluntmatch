@extends('layouts.panel')

@section('title', 'Oportunidades — Panel ONG')
@section('panel-section', 'OPORTUNIDADES')
@section('panel-heading', 'Proyectos y retos publicados.')

@section('panel-content')
    <div data-panel="projects">
        @if (request()->boolean('nueva'))
            <section class="dashboard-card project-create" id="nueva-oportunidad">
                <div class="card-heading">
                    <div>
                        <p>NUEVA OPORTUNIDAD</p>
                        <h3>Publica lo que necesitas</h3>
                    </div>
                    <a href="{{ route('ong.oportunidades') }}">Cancelar</a>
                </div>
                <form method="POST" action="{{ route('ong.oportunidades.store') }}" class="project-create-form">
                    @csrf
                    <label>
                        <span>Título del reto</span>
                        <input type="text" name="titulo" value="{{ old('titulo') }}" required maxlength="255"
                            placeholder="Ej. Rediseño de nuestra web" />
                    </label>
                    <label>
                        <span>Causa</span>
                        <input type="text" name="causa" value="{{ old('causa') }}" required maxlength="120"
                            placeholder="Ej. Educación" />
                    </label>
                    <label>
                        <span>Habilidad buscada</span>
                        <input type="text" name="habilidad" value="{{ old('habilidad') }}" required maxlength="120"
                            placeholder="Ej. UX/UI" />
                    </label>
                    <label>
                        <span>Dedicación</span>
                        <input type="text" name="dedicacion" value="{{ old('dedicacion') }}" required maxlength="80"
                            placeholder="Ej. 4 h/semana" />
                    </label>
                    <label>
                        <span>Modalidad</span>
                        <select name="modalidad" required>
                            @foreach (['Remoto', 'Híbrido', 'Presencial', 'Mixto'] as $modalidad)
                                <option value="{{ $modalidad }}" @selected(old('modalidad') === $modalidad)>{{ $modalidad }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="project-create-wide">
                        <span>Resumen</span>
                        <textarea name="resumen" rows="3" required maxlength="1000"
                            placeholder="Explica el reto y el impacto esperado...">{{ old('resumen') }}</textarea>
                    </label>
                    <div class="project-create-wide">
                        <button type="submit" class="jelly-button">
                            Publicar oportunidad <x-icon name="arrow" />
                        </button>
                    </div>
                </form>
            </section>
        @endif

        <section class="dashboard-card table-card project-manage">
            <div class="card-heading">
                <div>
                    <p>GESTIÓN DE OPORTUNIDADES</p>
                    <h3>Proyectos y retos publicados</h3>
                </div>
                <a class="admin-btn" href="{{ route('ong.oportunidades', ['nueva' => 1]) }}">+ Publicar necesidad</a>
            </div>

            <div class="project-filters">
                @foreach ([
                    'all' => ['label' => 'Todos', 'total' => $totales['todas']],
                    'publicada' => ['label' => 'Publicadas', 'total' => $totales['publicadas']],
                    'borrador' => ['label' => 'Borradores', 'total' => $totales['borradores']],
                    'archivada' => ['label' => 'Archivadas', 'total' => $totales['archivadas']],
                ] as $key => $filtro)
                    <button type="button" class="{{ $loop->first ? 'active' : '' }}"
                        data-project-filter="{{ $key }}">
                        {{ $filtro['label'] }} <b>{{ $filtro['total'] }}</b>
                    </button>
                @endforeach
            </div>

            <div class="talent-search project-search">
                <x-icon name="users" />
                <input type="text" data-project-search placeholder="Buscar proyecto por título, causa o habilidad..." />
            </div>

            @forelse ($oportunidades as $oportunidad)
                <div class="application-row" data-project-row data-status="{{ $oportunidad->estado }}"
                    data-search="{{ mb_strtolower($oportunidad->titulo.' '.$oportunidad->causa.' '.$oportunidad->habilidad) }}">
                    <span class="list-icon"><x-icon name="heart" /></span>
                    <span>
                        <b>{{ $oportunidad->titulo }}</b>
                        <small>{{ $oportunidad->postulaciones_count }} candidatos · {{ $oportunidad->causa }}</small>
                    </span>
                    <span class="row-metrics">
                        <b>{{ $oportunidad->vistas }}</b>
                        <small>visualizaciones</small>
                        <em>{{ $oportunidad->dedicacion }}</em>
                    </span>
                    <span class="status {{ $oportunidad->estado === 'publicada' ? 'activo' : ($oportunidad->estado === 'borrador' ? 'borrador' : 'finalizado') }}">
                        {{ ucfirst($oportunidad->estado) }}
                    </span>
                    <a class="row-action" href="{{ route('ong.candidatos', ['proyecto' => $oportunidad->id]) }}">
                        Ver postulantes <x-icon name="arrow" />
                    </a>
                </div>
            @empty
                <p class="panel-empty">Todavía no has publicado oportunidades. Usa «Publicar necesidad» para crear la primera.</p>
            @endforelse
        </section>
    </div>
@endsection
