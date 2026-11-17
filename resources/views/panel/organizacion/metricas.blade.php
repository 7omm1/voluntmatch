@extends('layouts.panel')

@section('title', 'Métricas — Panel ONG')
@section('panel-section', 'MÉTRICAS')
@section('panel-heading', 'El impacto de tu comunidad.')

@section('panel-content')
    <div data-panel="impact">
        <section class="impact-toolbar">
            <div class="impact-ranges">
                @foreach ([
                    ['key' => 'mes', 'label' => 'Último mes'],
                    ['key' => 'trimestre', 'label' => 'Trimestre'],
                    ['key' => 'anio', 'label' => 'Año'],
                    ['key' => 'historico', 'label' => 'Histórico'],
                ] as $rango)
                    <button type="button" data-impact-range="{{ $rango['key'] }}"
                        @if ($rango['key'] === 'historico') class="active" @endif>
                        {{ $rango['label'] }}
                    </button>
                @endforeach
            </div>
            <div class="impact-export">
                <button type="button" data-impact-export="csv">
                    <x-icon name="download" :size="12" /> CSV
                </button>
                <button type="button" data-impact-export="pdf">
                    <x-icon name="download" :size="12" /> PDF
                </button>
            </div>
        </section>

        <div class="analytics-layout">
            <section class="impact-hero-card ngo-impact">
                <div>
                    <p>IMPACTO DE LA ORGANIZACIÓN</p>
                    <h3><span data-impact-hours-label>{{ $horas }}</span> horas de talento que multiplican vuestra misión.</h3>
                    <span>
                        Un ahorro estimado de <b data-impact-savings>S/ {{ number_format($horas * 110, 0, ',', '.') }}</b>
                        en servicios profesionales.
                    </span>
                </div>
                <div class="impact-orbit">
                    <strong><span data-impact-hours-label>{{ $horas }}</span><small>HORAS</small></strong>
                    <i></i><i></i><i></i>
                </div>
            </section>

            <section class="dashboard-card evolution-card">
                <div class="card-heading">
                    <div>
                        <p>EVOLUCIÓN DEL VOLUNTARIADO</p>
                        <h3>Horas aportadas</h3>
                    </div>
                    <span data-impact-period-label>Por año</span>
                </div>
                <div class="evolution-chart" data-impact-bars></div>
            </section>

            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>RESULTADOS</p>
                        <h3>Áreas con más impacto</h3>
                    </div>
                </div>
                <div class="horizontal-chart">
                    @forelse ($areas as $area)
                        <div data-impact-area>
                            <span>{{ $area['label'] }}</span>
                            <i><b style="width: {{ $area['value'] }}%"></b></i>
                            <em>{{ $area['value'] }}%</em>
                        </div>
                    @empty
                        <p class="panel-empty">Cuando aceptes candidatos verás aquí el reparto por causa.</p>
                    @endforelse
                </div>
            </section>

            <section class="dashboard-card review-card">
                <div class="card-heading">
                    <div>
                        <p>VALORACIÓN MUTUA</p>
                        <h3>Evalúa a tus voluntarios</h3>
                    </div>
                </div>
                <p>Puntualidad, calidad técnica y actitud.</p>
                <div class="review-selects">
                    <select data-review-volunteer aria-label="Voluntario a evaluar">
                        <option value="">Elegir voluntario…</option>
                        @foreach ($postulaciones->pluck('user')->filter()->unique('id') as $voluntario)
                            <option value="{{ $voluntario->name }}">{{ $voluntario->name }} · {{ $voluntario->career ?: 'Voluntariado' }}</option>
                        @endforeach
                    </select>
                    <select data-review-project aria-label="Proyecto finalizado">
                        <option value="">Elegir proyecto…</option>
                        @foreach ($oportunidades as $oportunidad)
                            <option value="{{ $oportunidad->titulo }}">{{ $oportunidad->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stars">
                    @for ($star = 1; $star <= 5; $star++)
                        <button type="button" data-star aria-label="{{ $star }} estrellas">★</button>
                    @endfor
                </div>
                <textarea placeholder="Escribe una recomendación para su portafolio..."></textarea>
                <button type="button" data-review-publish>Publicar evaluación</button>
            </section>
        </div>
    </div>
@endsection
