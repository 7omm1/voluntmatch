@extends('layouts.base')

@section('title', 'Oportunidades — VoluntMatch')

@section('content')
    <div class="page-panel">
        <div class="panel-ambient" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="panel-topbar">
            <a class="brand" href="{{ route('index') }}">
                <span class="brand-mark">
                    <span></span>
                    <span></span>
                </span>
                <span>VOLUNTMATCH</span>
            </a>
            <a class="panel-close" href="{{ route('index') }}">
                <x-icon name="close" /> Cerrar
            </a>
        </div>

        <div class="panel-tabs">
            <a class="{{ $tab === 'opportunities' ? 'active' : '' }}"
                href="{{ route('oportunidades', ['tab' => 'opportunities']) }}">Oportunidades</a>
            <a class="{{ $tab === 'participate' ? 'active' : '' }}"
                href="{{ route('oportunidades', ['tab' => 'participate']) }}">Cómo participar</a>
            <a class="{{ $tab === 'organizations' ? 'active' : '' }}"
                href="{{ route('oportunidades', ['tab' => 'organizations']) }}">Para ONG</a>
        </div>

        <div class="panel-hero">
            @switch($tab)
                @case('participate')
                    <p class="eyebrow dark-text"><span></span> TU PRIMER PASO</p>
                    <h2>Hay muchas formas de participar.</h2>
                    <p>Puedes donar tu talento, aprender como mentor o ayudar a una organización desde donde estés.</p>
                @break

                @case('organizations')
                    <p class="eyebrow dark-text"><span></span> VOLUNTMATCH PARA ORGANIZACIONES</p>
                    <h2>El talento que tu causa necesita.</h2>
                    <p>Publica necesidades concretas, encuentra perfiles comprometidos y sigue el impacto desde un único lugar.</p>
                @break

                @default
                    <p class="eyebrow dark-text"><span></span> OPORTUNIDADES ABIERTAS</p>
                    <h2>Encuentra un proyecto hecho para ti.</h2>
                    <p>Filtra por causa, habilidad y tiempo disponible. Cada proyecto está verificado por nuestro equipo.</p>
            @endswitch
        </div>

        @if ($tab === 'opportunities')
            <div class="opportunity-grid">
                @foreach ($oportunidades as $i => $op)
                    <article class="opportunity-card">
                        <div class="opportunity-art art-{{ $i + 1 }} has-photo">
                            <img class="opportunity-photo" src="{{ $op['image'] }}"
                                alt="Imagen de {{ $op['title'] }}" loading="lazy" onerror="this.remove()" />
                            <span>{{ $op['cause'] }}</span>
                        </div>
                        <div class="opportunity-body">
                            <span class="skill-pill">{{ $op['skill'] }}</span>
                            <h3>{{ $op['title'] }}</h3>
                            <p>{{ $op['org'] }}</p>
                            <div>
                                <span>{{ $op['time'] }}</span>
                                <a href="{{ route('detalle-oportunidad', ['id' => $i + 1]) }}">
                                    Ver proyecto <x-icon name="arrow" />
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif ($tab === 'participate')
            <div class="participate-grid">
                <article>
                    <span>01</span>
                    <i><x-icon name="spark" /></i>
                    <h3>Comparte tu talento</h3>
                    <p>Participa en un proyecto de diseño, tecnología, comunicación, educación y mucho más.</p>
                    <a href="{{ route('registro-voluntario') }}">Empezar <x-icon name="arrow" /></a>
                </article>
                <article>
                    <span>02</span>
                    <i><x-icon name="spark" /></i>
                    <h3>Acompaña a alguien</h3>
                    <p>Conviértete en mentor y comparte aquello que la universidad y la vida te han enseñado.</p>
                    <a href="{{ route('registro-voluntario') }}">Empezar <x-icon name="arrow" /></a>
                </article>
                <article>
                    <span>03</span>
                    <i><x-icon name="spark" /></i>
                    <h3>Activa tu campus</h3>
                    <p>Crea un equipo en tu universidad y moviliza a más estudiantes alrededor de una causa.</p>
                    <a href="{{ route('registro-voluntario') }}">Empezar <x-icon name="arrow" /></a>
                </article>
            </div>
        @else
            <div class="ngo-panel-content">
                <div class="ngo-benefits">
                    <div><span><x-icon name="check" /></span> Publica proyectos en minutos</div>
                    <div><span><x-icon name="check" /></span> Recibe candidaturas relevantes</div>
                    <div><span><x-icon name="check" /></span> Gestiona equipos y horas</div>
                    <div><span><x-icon name="check" /></span> Mide y comparte tu impacto</div>
                </div>
                <div class="ngo-demo-card">
                    <p>PROYECTO EN CURSO</p>
                    <h3>Campaña por la educación rural</h3>
                    <div class="progress-row">
                        <span>Equipo completo</span>
                        <b>8 / 8</b>
                    </div>
                    <div class="progress-track"><span></span></div>
                    <div class="mini-avatars">
                        @foreach ([31, 32, 33, 34] as $img)
                            <img src="https://i.pravatar.cc/80?img={{ $img }}" alt="" />
                        @endforeach
                    </div>
                </div>
                <a class="jelly-button" href="{{ route('registro-organizacion') }}">
                    Registrar mi organización <x-icon name="arrow" />
                </a>
            </div>
        @endif
    </div>
@endsection
