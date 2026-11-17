@extends('layouts.panel')

@section('title', 'Mensajes — VoluntMatch')
@section('panel-section', 'MENSAJES')
@section('panel-heading', 'Habla con las ONG.')

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
                        <a href="{{ route('detalle-oportunidad', ['oportunidad' => 'mentoria-de-matematicas']) }}">Ver proyecto</a>
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
                    <form data-chat-form>
                        <button type="button">+</button>
                        <input type="text" placeholder="Escribe un mensaje..." data-chat-input />
                        <button type="submit"><x-icon name="arrow" /></button>
                    </form>
                </section>

                <aside class="chat-context">
                    <span class="shared-project-icon"><x-icon name="heart" /></span>
                    <p>PROYECTO COMPARTIDO</p>
                    <h3>{{ $activa['project'] }}</h3>
                    @foreach ($activa['context'] as $dato)
                        <div>
                            <span>{{ $dato['label'] }}</span>
                            <b>{{ $dato['value'] }}</b>
                        </div>
                    @endforeach
                    <a href="{{ route('detalle-oportunidad', ['oportunidad' => 'mentoria-de-matematicas']) }}">
                        Abrir ficha <x-icon name="arrow" />
                    </a>
                </aside>
            @endif
        </div>
    </div>
@endsection
