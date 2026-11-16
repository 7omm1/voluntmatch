@extends('layouts.base')

@section('title', $op['title'] . ' — VoluntMatch')

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
            <a class="panel-close" href="{{ route('oportunidades') }}">
                <x-icon name="close" /> Volver
            </a>
        </div>

        <article class="project-detail-modal">
            <div class="project-detail-cover has-photo">
                <img class="opportunity-photo" src="{{ $op['image'] }}"
                    alt="Imagen de {{ $op['title'] }}" loading="lazy" onerror="this.remove()" />
                <span>{{ $op['cause'] }}</span>
                <x-icon name="heart" :size="42" />
            </div>
            <div class="project-detail-content">
                <p class="eyebrow dark-text">FICHA COMPARTIDA DEL RETO</p>
                <h2>{{ $op['title'] }}</h2>
                <p class="project-org">
                    {{ $op['org'] }}
                    <span><x-icon name="check" /> ONG verificada</span>
                </p>
                <p>
                    Buscamos una persona con ganas de aportar su talento para crear una solución útil,
                    accesible y sostenible junto a nuestro equipo.
                </p>
                <div class="project-requirements">
                    <span>{{ $op['skill'] }}</span>
                    <span>{{ $op['time'] }}</span>
                    <span>Remoto</span>
                </div>
                <h3>Lo que conseguirás</h3>
                <ul>
                    <li><x-icon name="check" /> Experiencia real para tu portafolio</li>
                    <li><x-icon name="check" /> Certificado digital y valoración</li>
                    <li><x-icon name="check" /> Acompañamiento de la organización</li>
                </ul>
                @auth
                    <a class="jelly-button wide" href="{{ route('home') }}">
                        Postularme desde mi espacio <x-icon name="arrow" />
                    </a>
                @else
                    <a class="jelly-button wide" href="{{ route('registro-voluntario') }}">
                        Postularme a este reto <x-icon name="arrow" />
                    </a>
                @endauth
            </div>
        </article>
    </div>
@endsection
