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
                        @php
                            $razones = $op->porque ?? [];
                            $detalleUrl = route('detalle-oportunidad', [
                                'oportunidad' => $op->slug,
                                'desde' => 'matches',
                            ]);
                        @endphp
                        <article class="swipe-card" data-swipe-card
                            data-title="{{ $op->titulo }}"
                            data-org="{{ $op->organizacion?->nombre ?? 'ONG verificada' }}"
                            data-cause="{{ $op->causa }}"
                            data-skill="{{ $op->habilidad }}"
                            data-time="{{ $op->dedicacion }}"
                            data-modality="{{ $op->modalidad }}"
                            data-percent="{{ $op->afinidad ?? 80 }}"
                            data-summary="{{ $op->resumen }}"
                            data-learn="{{ $op->aprenderas }}"
                            data-image="{{ $op->imagen }}"
                            data-reasons="{{ json_encode(array_values($razones)) }}"
                            data-detail-url="{{ $detalleUrl }}"
                            data-postular-url="{{ route('voluntario.postular', ['oportunidad' => $op->slug]) }}">
                            <div class="swipe-photo art-{{ ($loop->index % 6) + 1 }}">
                                @if ($op->imagen)
                                    <img src="{{ $op->imagen }}" alt="{{ $op->titulo }}"
                                        loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        @if ($loop->index === 0) fetchpriority="high" @endif
                                        onerror="this.remove()" draggable="false" />
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
                                <div class="swipe-card-actions">
                                    <button type="button" class="swipe-card-open" data-match-open>
                                        Ver por qué encaja <x-icon name="arrow" :size="13" />
                                    </button>
                                    <a class="text-project-link" href="{{ $detalleUrl }}">
                                        Ficha completa <x-icon name="arrow" :size="12" />
                                    </a>
                                </div>
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
                    <x-icon name="spark" :size="13" /> Arrastra la tarjeta a la izquierda para pasar
                    o a la derecha para postularte.
                </p>
                <div class="deck-support">
                    <button type="button" class="deck-support-button" data-support-open>
                        <x-icon name="heart" :size="13" /> ¿Necesitas ayuda? Contactar soporte
                        <x-icon name="arrow" :size="12" />
                    </button>
                </div>
            </section>

            <aside class="match-side match-side-live" data-match-side>
                <p>POR QUÉ ENCAJA CONTIGO</p>
                <div class="match-side-card-head">
                    <span class="match-side-percent" data-match-percent>
                        {{ $oportunidades->first()?->afinidad ?? 80 }}%
                    </span>
                    <div>
                        <b data-match-title>{{ $oportunidades->first()?->titulo ?? 'Tu próximo reto' }}</b>
                        <small data-match-org>{{ $oportunidades->first()?->organizacion?->nombre ?? 'ONG verificada' }}</small>
                    </div>
                </div>
                <p class="match-side-summary" data-match-summary>
                    {{ $oportunidades->first()?->resumen ?? 'Desliza para descubrir oportunidades que encajan con tu perfil.' }}
                </p>

                <div class="match-reasons" data-match-reasons>
                    @foreach (($oportunidades->first()?->porque ?? []) as $razon)
                        <div class="match-reason"><span></span><p>{{ $razon }}</p></div>
                    @endforeach
                </div>

                <div class="match-compat">
                    <span><b>Habilidad</b><em data-match-skill>{{ $oportunidades->first()?->habilidad ?? 'Tu talento' }}</em></span>
                    <span><b>Dedicación</b><em data-match-time>{{ $oportunidades->first()?->dedicacion ?? 'A tu ritmo' }}</em></span>
                    <span><b>Causa</b><em data-match-cause>{{ $oportunidades->first()?->causa ?? 'La que te mueve' }}</em></span>
                </div>

                <div class="match-tip">
                    <small><x-icon name="spark" :size="12" /> Consejo VoluntMatch</small>
                    <p>Los perfiles con Skills Passport completo reciben un 40% más de respuestas.</p>
                    <a class="text-project-link" href="{{ route('voluntario.skills') }}">
                        Mejorar mi pasaporte <x-icon name="arrow" :size="12" />
                    </a>
                </div>
                <p class="match-side-empty" data-match-side-empty @if ($oportunidades->isNotEmpty()) hidden @endif>
                    No quedan tarjetas por revisar. Vuelve más tarde para conocer nuevos retos.
                </p>
            </aside>
        </div>
    </div>
@endsection

@section('panel-modals')
    <div class="project-modal-backdrop" data-match-modal hidden>
        <article class="match-detail-modal" role="dialog" aria-modal="true"
            aria-labelledby="match-modal-title">
            <button type="button" class="modal-close" data-match-close aria-label="Cerrar">
                <x-icon name="close" />
            </button>

            <div class="match-modal-cover">
                <img data-match-modal-image alt="" onerror="this.style.display='none'" />
                <div class="match-modal-cover-badges">
                    <span data-match-modal-cause>Causa</span>
                    <span data-match-modal-percent>80% match</span>
                </div>
                <p class="match-modal-cover-org">
                    <span data-match-modal-org>ONG verificada</span>
                    <small>Organización verificada</small>
                </p>
            </div>

            <div class="match-detail-body">
                <div class="match-detail-top">
                    <span class="skill-pill" data-match-modal-skill>Habilidad</span>
                    <span class="match-detail-verified"><x-icon name="check" :size="12" /> ONG verificada</span>
                </div>
                <h2 id="match-modal-title" data-match-modal-title>Oportunidad</h2>
                <p class="match-detail-org" data-match-modal-org-line>Aula Abierta</p>
                <p class="match-detail-summary" data-match-modal-summary></p>
                <div class="match-meta" data-match-modal-meta></div>

                <h3>Por qué encaja contigo</h3>
                <div class="match-reasons" data-match-modal-reasons></div>

                <h3>Lo que aprenderás</h3>
                <p class="match-detail-learn" data-match-modal-learn></p>

                <div class="match-detail-actions">
                    <button type="button" class="jelly-button" data-match-modal-apply>
                        Postularme ahora <x-icon name="heart" :size="15" />
                    </button>
                    <a class="ghost-button" data-match-modal-link href="#">
                        Ver ficha completa <x-icon name="arrow" :size="13" />
                    </a>
                </div>
            </div>
        </article>
    </div>
@endsection
