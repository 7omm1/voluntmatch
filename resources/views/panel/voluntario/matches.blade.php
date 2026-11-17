@extends('layouts.panel')

@section('title', 'Descubrir matches — VoluntMatch')
@section('panel-section', 'DESCUBRIR MATCHES')
@section('panel-heading', 'Desliza. Encuentra tu causa.')

@section('panel-content')
    <div data-panel="matches">
        <div class="match-layout">
            <section class="match-card-large swipe-deck" data-swipe-deck>
                <div class="deck-stage" data-deck-stage>
                    @foreach ($oportunidades as $op)
                        <article class="swipe-card" data-swipe-card
                            data-title="{{ $op->titulo }}"
                            data-postular-url="{{ route('voluntario.postular', ['oportunidad' => $op->slug]) }}">
                            <div class="swipe-photo art-{{ ($loop->index % 6) + 1 }}">
                                @if ($op->imagen)
                                    <img src="{{ $op->imagen }}" alt="{{ $op->titulo }}"
                                        loading="lazy" onerror="this.remove()" draggable="false" />
                                @endif
                                <span class="swipe-cause">{{ $op->causa }}</span>
                                <b class="swipe-percent">{{ $op->afinidad ?? 80 }}%<small>MATCH</small></b>
                                <span class="stamp stamp-like">¡ME INTERESA!</span>
                                <span class="stamp stamp-nope">PASAR</span>
                                <div class="swipe-overlay"></div>
                            </div>
                            <div class="swipe-info">
                                <span class="skill-pill">{{ $op->habilidad }}</span>
                                <h3>{{ $op->titulo }}</h3>
                                <p><b>{{ $op->organizacion?->nombre ?? 'ONG verificada' }}</b> busca tu talento para un proyecto con impacto real.</p>
                                <div class="match-meta">
                                    <span>{{ $op->dedicacion }}</span>
                                    <span>{{ $op->modalidad }}</span>
                                    <span>Certificado</span>
                                </div>
                                <a class="text-project-link" href="{{ route('detalle-oportunidad', ['oportunidad' => $op->slug]) }}">
                                    Ver ficha completa <x-icon name="arrow" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="deck-empty" data-deck-empty @if ($oportunidades->isNotEmpty()) hidden @endif>
                    <span class="deck-empty-icon"><x-icon name="heart" :size="30" /></span>
                    <b>¡Ya viste todos los matches!</b>
                    <p>Vuelve más tarde para descubrir nuevas oportunidades o repasa tus postulaciones.</p>
                    <a class="jelly-button small" href="{{ route('voluntario.postulaciones') }}">
                        Ver mis postulaciones <x-icon name="arrow" :size="15" />
                    </a>
                </div>

                <div class="swipe-actions" @if ($oportunidades->isEmpty()) hidden @endif>
                    <button type="button" data-deck-nope aria-label="Pasar">
                        <x-icon name="close" />
                    </button>
                    <button type="button" data-deck-like aria-label="Postularme">
                        <x-icon name="heart" />
                    </button>
                </div>
                <p class="deck-hint">
                    <x-icon name="spark" :size="13" /> Arrastra la tarjeta solo a la izquierda
                    para pasar o a la derecha para postularte.
                </p>
            </section>

            <aside class="match-side">
                <p>POR QUÉ ENCAJA CONTIGO</p>
                <div><x-icon name="check" /> <span>Tu habilidad encaja con el proyecto</span></div>
                <div><x-icon name="check" /> <span>Compatible con tu horario</span></div>
                <div><x-icon name="check" /> <span>Causa que te importa</span></div>
                <hr />
                <small>Consejo VoluntMatch</small>
                <p>Los perfiles con Skills Passport completo reciben un 40% más de respuestas.</p>
                <a class="text-project-link" href="{{ route('voluntario.skills') }}">
                    Mejorar mi pasaporte <x-icon name="arrow" :size="12" />
                </a>
            </aside>
        </div>
    </div>
@endsection
