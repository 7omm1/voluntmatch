@extends('layouts.panel')

@section('title', 'Mensajes — Panel ONG')
@section('panel-section', 'MENSAJES')
@section('panel-heading', 'Coordina con tu talento.')

@section('panel-content')
    @php $activa = $conversaciones[0] ?? null; @endphp
    <div data-panel="messages">
        <div class="messaging-layout">
            <aside class="conversation-list">
                <div class="card-heading">
                    <div>
                        <p>MENSAJES</p>
                        <h3>Conversaciones</h3>
                    </div>
                </div>
                @foreach ($conversaciones as $conversacion)
                    <button type="button" class="{{ $loop->first ? 'active' : '' }}">
                        <span>{{ $conversacion['initials'] }}</span>
                        <div>
                            <b>{{ $conversacion['name'] }}</b>
                            <small>{{ collect($conversacion['messages'])->last()['text'] ?? 'Sin mensajes' }}</small>
                        </div>
                        @if (($conversacion['unread'] ?? 0) > 0)
                            <i>{{ $conversacion['unread'] }}</i>
                        @endif
                    </button>
                @endforeach
            </aside>

            @if ($activa)
                <section class="chat-panel">
                    <header>
                        <span>{{ $activa['initials'] }}</span>
                        <div>
                            <b>{{ $activa['name'] }}</b>
                            <small><i></i> {{ $activa['status'] }}</small>
                        </div>
                        <a href="{{ route('detalle-oportunidad', ['oportunidad' => 'rediseno-web-accesible']) }}">Ver proyecto</a>
                    </header>
                    <div class="chat-messages" data-chat>
                        <p>HOY</p>
                        @foreach ($activa['messages'] as $mensaje)
                            <div class="{{ $mensaje['from'] === 'me' ? 'mine' : '' }}">
                                <span>{{ $mensaje['text'] }}</span>
                                <small>{{ $mensaje['time'] }}</small>
                            </div>
                        @endforeach
                    </div>
                    <div class="chat-quick-templates" role="toolbar" aria-label="Plantillas de mensajes rápidos">
                        @foreach ([
                            'Hola, nos encantó tu perfil. ¿Tienes disponibilidad esta semana para una llamada de 15 min?',
                            'Te confirmamos la entrevista para el jueves a las 17:00. ¿Te funciona?',
                            '¡Felicidades! Fuiste seleccionado/a para el proyecto. Te envío los siguientes pasos.',
                            'Gracias por tu postulación. Seguiremos con otros perfiles por ahora, ¡éxitos!',
                        ] as $plantilla)
                            <button type="button" data-template="{{ $plantilla }}">{{ mb_substr($plantilla, 0, 34) }}…</button>
                        @endforeach
                    </div>
                    <form data-chat-form>
                        <button type="button" data-chat-attach aria-label="Adjuntar archivo">+</button>
                        <input type="text" placeholder="Escribe un mensaje..." data-chat-input />
                        <button type="submit"><x-icon name="arrow" /></button>
                    </form>
                </section>

                <aside class="chat-context">
                    <span class="shared-project-icon"><x-icon name="heart" /></span>
                    <p>PROYECTO COMPARTIDO</p>
                    <h3>{{ $activa['project'] }}</h3>
                    <div>
                        <span>Próxima reunión</span>
                        <b>Jueves · 17:00</b>
                        <div class="context-schedule">
                            <button type="button" data-schedule="meet">
                                <x-icon name="video" :size="11" /> Enlace Meet
                            </button>
                            <button type="button" data-schedule="calendar">
                                <x-icon name="calendar" :size="11" /> Invitar
                            </button>
                        </div>
                    </div>
                    @foreach ($activa['context'] as $dato)
                        <div>
                            <span>{{ $dato['label'] }}</span>
                            <b>{{ $dato['value'] }}</b>
                        </div>
                    @endforeach
                    <div class="shared-files">
                        <span>Archivos y enlaces</span>
                        @foreach ([
                            ['icon' => 'download', 'label' => 'Brief del proyecto.pdf', 'meta' => 'PDF · 240 KB'],
                            ['icon' => 'download', 'label' => 'Guion de entrevista.docx', 'meta' => 'Word · 18 KB'],
                            ['icon' => 'arrow', 'label' => 'Tablero de Figma', 'meta' => 'Enlace'],
                        ] as $archivo)
                            <button type="button" data-shared-file="{{ $archivo['label'] }}">
                                <x-icon name="{{ $archivo['icon'] }}" :size="11" />
                                <span>
                                    <b>{{ $archivo['label'] }}</b>
                                    <small>{{ $archivo['meta'] }}</small>
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <a class="chat-context-link" href="{{ route('detalle-oportunidad', ['oportunidad' => 'rediseno-web-accesible']) }}">
                        Abrir ficha <x-icon name="arrow" :size="11" />
                    </a>
                </aside>
            @endif
        </div>
    </div>
@endsection
