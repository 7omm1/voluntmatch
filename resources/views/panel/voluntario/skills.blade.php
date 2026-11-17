@extends('layouts.panel')

@section('title', 'Skills Passport — VoluntMatch')
@section('panel-section', 'SKILLS PASSPORT')
@section('panel-heading', 'Tu mapa de fortalezas.')

@section('panel-content')
    <div data-panel="passport">
        <div class="skills-layout">
            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>SKILLS PASSPORT</p>
                        <h3>Tu mapa de fortalezas</h3>
                    </div>
                    <span>{{ $porcentaje }}% de perfil completo</span>
                </div>
                <div class="radar-wrap">
                    <div class="radar-chart">
                        <span class="radar-shape"></span>
                        @foreach (array_keys($skills) as $i => $name)
                            <b style="transform: rotate({{ $i * 72 }}deg) translateY(-102px) rotate({{ -$i * 72 }}deg)">{{ $name }}</b>
                        @endforeach
                    </div>
                </div>
                <p class="skills-note">
                    <x-icon name="spark" />
                    Destacas especialmente en {{ mb_strtolower($destacadas[0]) }} y {{ mb_strtolower($destacadas[1]) }}.
                </p>

                @unless ($puedeHacerMatch)
                    <p class="skills-note">
                        <x-icon name="clock" />
                        Para recibir matches completos te falta: {{ collect($faltantesClave)->pluck('label')->implode(', ') }}.
                    </p>
                @endunless
            </section>

            <form method="POST" action="{{ route('perfil.skills') }}" class="dashboard-card skill-editor">
                @csrf
                <div class="card-heading">
                    <div>
                        <p>AUTODESCUBRIMIENTO</p>
                        <h3>Ajusta tus fortalezas</h3>
                    </div>
                </div>
                @foreach ($skills as $name => $value)
                    <label>
                        <span>
                            <b>{{ $name }}</b>
                            <em data-range-value>{{ $value }}%</em>
                        </span>
                        <input type="range" min="20" max="100" value="{{ $value }}"
                            name="skills[{{ $name }}]" data-range />
                    </label>
                @endforeach
                <button type="submit" class="jelly-button">
                    Guardar mi pasaporte <x-icon name="check" />
                </button>
            </form>
        </div>
    </div>
@endsection
