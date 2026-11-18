@extends('layouts.panel')

@section('title', 'Skills Passport — VoluntMatch')
@section('panel-section', 'SKILLS PASSPORT')
@section('panel-heading', 'Tu mapa de fortalezas.')

@php
    $valores = array_values($skills);
    $radarPuntos = [];

    foreach ($valores as $indice => $valor) {
        $angulo = deg2rad(-90 + $indice * 72);
        $radio = ($valor / 100) * 42;
        $radarPuntos[] = round(50 + $radio * cos($angulo), 2).','.round(50 + $radio * sin($angulo), 2);
    }
@endphp

@section('panel-content')
    <div data-panel="passport">
        <div class="skills-layout">
            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>SKILLS PASSPORT</p>
                        <h3>Tu mapa de fortalezas</h3>
                    </div>
                    <span><b data-profile-percent>{{ $porcentaje }}%</b> de perfil completo</span>
                </div>
                <div class="radar-wrap">
                    <div class="radar-chart radar-chart-live" data-radar>
                        <svg class="radar-svg" viewBox="0 0 100 100" role="img"
                            aria-label="Mapa de fortalezas de tu Skills Passport">
                            <g class="radar-grid">
                                @foreach ([0.25, 0.5, 0.75, 1] as $escala)
                                    <polygon points="{{ collect(range(0, 4))->map(function ($i) use ($escala) {
                                        $angulo = deg2rad(-90 + $i * 72);
                                        $radio = 42 * $escala;
                                        return round(50 + $radio * cos($angulo), 2).','.round(50 + $radio * sin($angulo), 2);
                                    })->implode(' ') }}" />
                                @endforeach
                            </g>
                            <g class="radar-axes">
                                @foreach (range(0, 4) as $i)
                                    @php
                                        $angulo = deg2rad(-90 + $i * 72);
                                    @endphp
                                    <line x1="50" y1="50"
                                        x2="{{ round(50 + 42 * cos($angulo), 2) }}"
                                        y2="{{ round(50 + 42 * sin($angulo), 2) }}" />
                                @endforeach
                            </g>
                            <polygon class="radar-shape-live" data-radar-shape
                                points="{{ implode(' ', $radarPuntos) }}" />
                            <g class="radar-points" data-radar-points>
                                @foreach ($valores as $indice => $valor)
                                    @php
                                        $angulo = deg2rad(-90 + $indice * 72);
                                        $radio = ($valor / 100) * 42;
                                    @endphp
                                    <circle cx="{{ round(50 + $radio * cos($angulo), 2) }}"
                                        cy="{{ round(50 + $radio * sin($angulo), 2) }}" r="1.8" />
                                @endforeach
                            </g>
                        </svg>
                        @foreach (array_keys($skills) as $i => $name)
                            <b style="transform: rotate({{ $i * 72 }}deg) translateY(-116px) rotate({{ -$i * 72 }}deg)"
                                data-radar-label="{{ $name }}">{{ $name }}</b>
                        @endforeach
                    </div>
                </div>
                <p class="skills-note">
                    <x-icon name="spark" />
                    Destacas especialmente en <b data-highlight-skill>{{ mb_strtolower($destacadas[0]) }}</b>
                    y <b data-highlight-skill>{{ mb_strtolower($destacadas[1]) }}</b>.
                </p>

                @unless ($puedeHacerMatch)
                    <p class="skills-note">
                        <x-icon name="clock" />
                        Para recibir matches completos te falta: {{ collect($faltantesClave)->pluck('label')->implode(', ') }}.
                    </p>
                @endunless

                <a class="text-project-link skills-profile-link" href="#completar-perfil" data-profile-open>
                    Completar los datos de mi perfil <x-icon name="arrow" :size="12" />
                </a>
            </section>

            <form method="POST" action="{{ route('perfil.skills') }}" class="dashboard-card skill-editor"
                data-skills-form
                data-profile-base="{{ $puntos['base'] }}"
                data-profile-total="{{ $puntos['total'] }}"
                data-skills-weight="{{ $puntos['skillsPeso'] }}">
                @csrf
                <div class="card-heading">
                    <div>
                        <p>AUTODESCUBRIMIENTO</p>
                        <h3>Ajusta tus fortalezas</h3>
                    </div>
                    <span class="skills-live-chip"><i></i> En vivo</span>
                </div>
                @foreach ($skills as $name => $value)
                    <label>
                        <span>
                            <b>{{ $name }}</b>
                            <em data-range-value>{{ $value }}%</em>
                        </span>
                        <input type="range" min="20" max="100" value="{{ $value }}"
                            name="skills[{{ $name }}]" data-range data-skill-range
                            data-skill-name="{{ $name }}" />
                    </label>
                @endforeach
                <button type="submit" class="jelly-button">
                    Guardar mi pasaporte <x-icon name="check" />
                </button>
                <p class="skill-editor-hint">
                    <x-icon name="spark" :size="13" />
                    Cada ajuste actualiza el mapa y suma puntos a tu porcentaje de perfil.
                </p>
            </form>
        </div>
    </div>
@endsection
