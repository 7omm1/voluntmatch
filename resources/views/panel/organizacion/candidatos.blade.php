@extends('layouts.panel')

@section('title', 'Candidatos — Panel ONG')
@section('panel-section', 'CANDIDATOS')
@section('panel-heading', 'Match Inbox: tus postulantes.')

@section('panel-content')
    @php
        $etapas = [
            ['key' => 'por-revisar', 'label' => 'Por revisar'],
            ['key' => 'entrevista', 'label' => 'En entrevista'],
            ['key' => 'aceptado', 'label' => 'Aceptado'],
            ['key' => 'rechazado', 'label' => 'Rechazado'],
        ];
        $porRevisar = collect($candidatos)->where('etapa', 'por-revisar')->count();
    @endphp

    <div data-panel="candidates">
        <section class="dashboard-card table-card">
            <div class="card-heading">
                <div>
                    <p>MATCH INBOX</p>
                    <h3>Candidatos por revisar</h3>
                </div>
                <div class="inbox-actions">
                    <span data-inbox-new>{{ $porRevisar }} por revisar</span>
                    <div class="inbox-view-toggle">
                        <button type="button" data-inbox-view="list" class="active">Lista</button>
                        <button type="button" data-inbox-view="kanban">Kanban</button>
                    </div>
                </div>
            </div>

            <div data-inbox-list>
                @forelse ($candidatos as $candidato)
                    <div class="candidate-item" data-candidate-item
                        data-candidate="{{ $candidato['name'] }}"
                        data-project="{{ $candidato['proyecto'] }}"
                        data-postulacion="{{ $candidato['id'] }}"
                        data-update-url="{{ route('ong.candidatos.actualizar', ['postulacion' => $candidato['id']]) }}">
                        <div class="candidate-row" data-candidate-row data-stage="{{ $candidato['etapa'] }}">
                            <span class="talent-avatar">{{ $candidato['initials'] }}</span>
                            <span>
                                <b>{{ $candidato['name'] }}</b>
                                <small>{{ $candidato['detail'] }} · {{ $candidato['skills'][0] ?? 'Voluntariado' }}</small>
                                <small class="candidate-project">Postula a: {{ $candidato['proyecto'] }}</small>
                                @if ($candidato['nota'])
                                    <small class="candidate-message">«{{ $candidato['nota'] }}»</small>
                                @endif
                            </span>
                            <em>{{ $candidato['match'] }}% match</em>
                            <span class="status candidate-stage" data-stage-badge
                                data-stage="{{ $candidato['etapa'] }}">{{ $candidato['legible'] }}</span>
                            <button type="button" data-candidate-accept>
                                <x-icon name="check" :size="11" /> Aceptar
                            </button>
                            <button type="button" data-candidate-reject>
                                <x-icon name="close" :size="11" /> Rechazar
                            </button>
                            <button type="button" data-candidate-notes-toggle aria-expanded="false">
                                <x-icon name="menu" :size="11" /> Nota
                            </button>
                            <a class="row-action" href="{{ route('ong.mensajes') }}">Mensaje</a>
                        </div>
                        <div class="candidate-notes" data-candidate-notes hidden>
                            <textarea data-candidate-note-input rows="2"
                                placeholder="Nota interna sobre {{ $candidato['name'] }} (solo la ve tu equipo)...">{{ $candidato['nota_interna'] }}</textarea>
                            <div class="candidate-note-actions">
                                <button type="button" data-candidate-note-save>Guardar nota</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="panel-empty">Todavía no hay postulaciones. Cuando alguien postule a tus oportunidades aparecerá aquí.</p>
                @endforelse
            </div>

            <div class="candidate-kanban" data-inbox-kanban hidden>
                @foreach ($etapas as $etapa)
                    <div class="kanban-column" data-kanban-column="{{ $etapa['key'] }}">
                        <header>
                            <span>{{ $etapa['label'] }}</span>
                            <b data-kanban-count>0</b>
                        </header>
                        <div class="kanban-cards" data-kanban-cards>
                            @foreach ($candidatos as $candidato)
                                @if ($candidato['etapa'] === $etapa['key'])
                                    <div class="kanban-card" data-candidate-card
                                        data-candidate="{{ $candidato['name'] }}"
                                        data-postulacion="{{ $candidato['id'] }}"
                                        data-stage="{{ $candidato['etapa'] }}">
                                        <b>{{ $candidato['name'] }}</b>
                                        <small>{{ $candidato['match'] }}% match · {{ $candidato['skills'][0] ?? 'Voluntariado' }}</small>
                                        <span>Postula a: {{ $candidato['proyecto'] }}</span>
                                        <div class="kanban-card-actions">
                                            <button type="button" data-candidate-move="prev" aria-label="Mover a la etapa anterior">
                                                <x-icon name="arrow" :size="12" />
                                            </button>
                                            <button type="button" data-candidate-notes-jump
                                                aria-label="Ver nota interna de {{ $candidato['name'] }}">
                                                <x-icon name="menu" :size="12" />
                                            </button>
                                            <button type="button" data-candidate-move="next" aria-label="Mover a la etapa siguiente">
                                                <x-icon name="arrow" :size="12" />
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
