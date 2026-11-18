@extends('layouts.base')

@section('title', $op['title'] . ' — VoluntMatch')

@php
    $razones = array_values($op['porque'] ?? []);
    $match = $op['match'] ?? 80;
    $desdeMatches = request('desde') === 'matches' && auth()->user()?->role === 'voluntario';
    $volverUrl = $desdeMatches ? route('voluntario.matches') : route('oportunidades');
    $volverTexto = $desdeMatches ? 'Volver a matches' : 'Volver a oportunidades';
@endphp

@section('content')
    <div class="page-panel ficha-page">
        <div class="panel-ambient" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="panel-topbar ficha-topbar">
            <a class="brand" href="{{ route('index') }}">
                <span class="brand-mark">
                    <span></span>
                    <span></span>
                </span>
                <span>VOLUNTMATCH</span>
            </a>
            <a class="panel-close" href="{{ $volverUrl }}">
                <x-icon name="close" /> {{ $volverTexto }}
            </a>
        </div>

        <article class="ficha-shell">
            <header class="ficha-hero">
                <div class="ficha-cover has-photo">
                    @if (! empty($op['image']))
                        <img src="{{ $op['image'] }}" alt="Imagen de {{ $op['title'] }}"
                            loading="eager" onerror="this.remove()" />
                    @endif
                    <span class="ficha-cover-cause">{{ $op['cause'] }}</span>
                    <div class="ficha-cover-score">
                        <b>{{ $match }}%</b>
                        <small>MATCH</small>
                    </div>
                </div>

                <div class="ficha-intro">
                    <p class="eyebrow dark-text"><span></span> FICHA DEL RETO</p>
                    <span class="skill-pill">{{ $op['skill'] }}</span>
                    <h1>{{ $op['title'] }}</h1>
                    <p class="ficha-org">
                        {{ $op['org'] }}
                        <span><x-icon name="check" :size="12" /> ONG verificada</span>
                    </p>
                    <p class="ficha-summary">{{ $op['summary'] ?? '' }}</p>

                    <div class="ficha-meta">
                        <span><x-icon name="spark" :size="13" /> {{ $op['modalidad'] ?? 'Flexible' }}</span>
                        <span><x-icon name="clock" :size="13" /> {{ $op['time'] ?? 'A tu ritmo' }}</span>
                        <span><x-icon name="heart" :size="13" /> {{ $op['cause'] }}</span>
                        <span><x-icon name="check" :size="13" /> Certificado digital</span>
                    </div>

                    @if (session('success'))
                        <div class="form-success" role="status">
                            <x-icon name="check" :size="16" /> {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="form-error form-errors" role="alert">
                            <b>No pudimos registrar tu postulación:</b>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="ficha-actions">
                        @auth
                            @if (auth()->user()->role === 'voluntario')
                                <form method="POST" action="{{ route('postular') }}">
                                    @csrf
                                    <input type="hidden" name="oportunidad_id" value="{{ $op['id'] ?? 0 }}" />
                                    <button type="submit" class="jelly-button">
                                        Postularme a este reto <x-icon name="arrow" :size="15" />
                                    </button>
                                </form>
                            @else
                                <a class="jelly-button" href="{{ route('home') }}">
                                    Gestionar desde mi panel <x-icon name="arrow" :size="15" />
                                </a>
                            @endif
                        @else
                            <a class="jelly-button" href="{{ route('registro-voluntario') }}">
                                Postularme a este reto <x-icon name="arrow" :size="15" />
                            </a>
                        @endauth
                        <a class="ghost-button ficha-back" href="{{ $volverUrl }}">
                            {{ $volverTexto }}
                        </a>
                    </div>

                    <p class="ficha-note">
                        <x-icon name="check" :size="12" />
                        Postular es gratis. La ONG revisa tu perfil y te contacta desde el panel.
                    </p>
                </div>
            </header>

            <div class="ficha-body">
                <div class="ficha-main">
                    <section class="ficha-block">
                        <h2>Por qué encaja contigo</h2>
                        <div class="ficha-reasons">
                            @forelse ($razones as $razon)
                                <div class="ficha-reason">
                                    <span><x-icon name="check" :size="13" /></span>
                                    <p>{{ $razon }}</p>
                                </div>
                            @empty
                                <div class="ficha-reason">
                                    <span><x-icon name="check" :size="13" /></span>
                                    <p>Tu perfil y disponibilidad son compatibles con este reto.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="ficha-block">
                        <h2>Lo que aprenderás</h2>
                        <p>{{ $op['aprenderas'] ?? 'Experiencia real, acompañamiento del equipo y certificado digital para tu portafolio.' }}</p>
                    </section>

                    <section class="ficha-block">
                        <h2>Lo que conseguirás</h2>
                        <ul class="ficha-checklist">
                            <li><x-icon name="check" :size="14" /> Experiencia real para tu portafolio</li>
                            <li><x-icon name="check" :size="14" /> Certificado digital y valoración de la ONG</li>
                            <li><x-icon name="check" :size="14" /> Acompañamiento durante todo el proyecto</li>
                            <li><x-icon name="check" :size="14" /> Una red de contactos que comparte tu causa</li>
                        </ul>
                    </section>

                    <section class="ficha-block">
                        <h2>Cómo funciona</h2>
                        <ol class="ficha-steps">
                            <li>
                                <b>1</b>
                                <div>
                                    <strong>Postúlate</strong>
                                    <p>Envía tu perfil con un clic. La ONG recibe tu talento y tu disponibilidad.</p>
                                </div>
                            </li>
                            <li>
                                <b>2</b>
                                <div>
                                    <strong>Conversa</strong>
                                    <p>Coordinen una breve entrevista por mensajes para conocer el reto y el equipo.</p>
                                </div>
                            </li>
                            <li>
                                <b>3</b>
                                <div>
                                    <strong>Aporta y certifica</strong>
                                    <p>Empieza el proyecto, registra tu impacto y recibe tu certificado digital.</p>
                                </div>
                            </li>
                        </ol>
                    </section>
                </div>

                <aside class="ficha-aside">
                    <div class="ficha-side-card">
                        <p class="ficha-side-label">RESUMEN DEL MATCH</p>
                        <div class="ficha-side-score">
                            <b>{{ $match }}%</b>
                            <span>Afinidad con tu perfil</span>
                        </div>
                        <div class="ficha-side-list">
                            <span><b>Habilidad</b><em>{{ $op['skill'] }}</em></span>
                            <span><b>Dedicación</b><em>{{ $op['time'] ?? 'A tu ritmo' }}</em></span>
                            <span><b>Modalidad</b><em>{{ $op['modalidad'] ?? 'Flexible' }}</em></span>
                            <span><b>Causa</b><em>{{ $op['cause'] }}</em></span>
                        </div>
                        @auth
                            @if (auth()->user()->role === 'voluntario')
                                <form method="POST" action="{{ route('postular') }}">
                                    @csrf
                                    <input type="hidden" name="oportunidad_id" value="{{ $op['id'] ?? 0 }}" />
                                    <button type="submit" class="jelly-button wide">
                                        Postularme ahora <x-icon name="heart" :size="14" />
                                    </button>
                                </form>
                            @else
                                <a class="jelly-button wide" href="{{ route('home') }}">
                                    Gestionar desde mi panel <x-icon name="arrow" :size="14" />
                                </a>
                            @endif
                        @else
                            <a class="jelly-button wide" href="{{ route('registro-voluntario') }}">
                                Postularme ahora <x-icon name="heart" :size="14" />
                            </a>
                        @endauth
                        <p class="ficha-side-note">
                            <x-icon name="check" :size="12" /> Una sola postulación por reto.
                        </p>
                    </div>

                    <div class="ficha-org-card">
                        <span class="ficha-org-avatar">{{ mb_substr($op['org'] ?? 'V', 0, 1) }}</span>
                        <div>
                            <b>{{ $op['org'] }}</b>
                            <small>Organización verificada por VoluntMatch</small>
                        </div>
                    </div>
                </aside>
            </div>
        </article>
    </div>
@endsection
