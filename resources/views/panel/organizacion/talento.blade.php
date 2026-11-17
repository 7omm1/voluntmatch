@extends('layouts.panel')

@section('title', 'Buscar talento — Panel ONG')
@section('panel-section', 'BUSCAR TALENTO')
@section('panel-heading', 'Estudiantes listos para aportar.')

@section('panel-content')
    <div data-panel="talent">
        <div class="talent-search">
            <x-icon name="users" />
            <input type="text" data-talent-search placeholder="Buscar por habilidad, carrera o nombre..." />
        </div>

        <div class="talent-filters">
            <span class="talent-filter-label">Carrera</span>
            @foreach (['Diseño', 'Informática', 'Comunicación', 'Estadística'] as $carrera)
                <button type="button" data-talent-filter="career" data-filter-value="{{ $carrera }}">{{ $carrera }}</button>
            @endforeach
            <span class="talent-filter-label">Disponibilidad</span>
            <button type="button" data-talent-filter="hours" data-filter-value="low">≤ 5 h/sem</button>
            <button type="button" data-talent-filter="hours" data-filter-value="mid">6–10 h/sem</button>
            <button type="button" data-talent-filter="hours" data-filter-value="high">+10 h/sem</button>
            <span class="talent-filter-label">% Match</span>
            <button type="button" data-talent-filter="match" data-filter-value="90">90%+</button>
            <button type="button" data-talent-filter="match" data-filter-value="95">95%+</button>
            <button type="button" data-talent-filter="fav">
                <x-icon name="star" :size="11" /> Favoritos <b data-fav-count>0</b>
            </button>
        </div>

        <div class="talent-grid">
            @forelse ($talentos as $talento)
                <article class="talent-card" data-talent-card
                    data-search="{{ mb_strtolower($talento['name'].' '.$talento['detail'].' '.implode(' ', $talento['skills'])) }}"
                    data-career="{{ $talento['career'] }}"
                    data-skills="{{ mb_strtolower(implode(' ', $talento['skills'])) }}"
                    data-hours="{{ $talento['hours'] }}"
                    data-match="{{ $talento['match'] }}">
                    <button type="button" class="talent-save" data-talent-save
                        aria-label="Guardar {{ $talento['name'] }} en favoritos" aria-pressed="false">
                        <x-icon name="star" :size="13" />
                    </button>
                    <span class="match-score">{{ $talento['match'] }}% match</span>
                    <span class="talent-avatar">{{ $talento['initials'] }}</span>
                    <h3>{{ $talento['name'] }}</h3>
                    <p>{{ $talento['detail'] }}</p>
                    <div>
                        @foreach ($talento['skills'] as $skill)
                            <span>{{ $skill }}</span>
                        @endforeach
                    </div>
                    <span class="talent-availability">
                        <x-icon name="clock" :size="11" /> Disponible: {{ $talento['hours'] }} h/sem
                    </span>
                    <div class="talent-actions">
                        <button type="button" class="talent-invite"
                            data-toast-msg="Invitación enviada a {{ $talento['name'] }}">
                            Invitar a proyecto <x-icon name="arrow" :size="12" />
                        </button>
                        <button type="button" class="talent-secondary"
                            data-toast-msg="Perfil de {{ $talento['name'] }} — portafolio y LinkedIn muy pronto">
                            Ver perfil
                        </button>
                    </div>
                </article>
            @empty
                <p class="panel-empty">Aún no hay estudiantes registrados.</p>
            @endforelse
        </div>
        <p class="talent-empty" data-talent-empty hidden>
            No hay perfiles que coincidan con la búsqueda y los filtros activos.
        </p>
    </div>
@endsection
