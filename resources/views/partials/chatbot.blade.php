@php
    $rolAsistente = auth()->user()?->role;
    $sugerenciasAsistente = match ($rolAsistente) {
        'voluntario' => ['Ver mis postulaciones', 'Buscar oportunidades', '¿Cómo completo mi perfil?'],
        'organizacion' => ['Estado de mi ONG', 'Mis oportunidades', '¿Cómo verifico mi ONG?'],
        'admin' => ['Resumen de la plataforma', 'Cómo funciona el asistente', 'Ver oportunidades'],
        default => ['¿Cómo me registro?', 'Buscar oportunidades', '¿Cómo registro mi ONG?'],
    };
@endphp

<div class="vm-assistant" data-chatbot
    data-endpoint="{{ route('chatbot.mensaje') }}"
    data-history="{{ route('chatbot.historial') }}"
    data-confirm="{{ route('chatbot.confirmar') }}"
    data-reset="{{ route('chatbot.reiniciar') }}"
    data-csrf="{{ csrf_token() }}"
    data-suggestions='@json($sugerenciasAsistente)'
    data-role="{{ $rolAsistente ?? 'public' }}">

    <button class="vm-assistant-toggle" type="button" data-chatbot-toggle
        aria-label="Abrir asistente de VoluntMatch" aria-expanded="false" aria-controls="vm-assistant-panel">
        <span class="vm-assistant-toggle-open"><x-icon name="chat" :size="22" /></span>
        <span class="vm-assistant-toggle-close"><x-icon name="close" :size="20" /></span>
        <span class="vm-assistant-dot" aria-hidden="true"></span>
    </button>

    <section class="vm-assistant-panel" id="vm-assistant-panel" data-chatbot-panel hidden
        role="dialog" aria-modal="false" aria-labelledby="vm-assistant-title">
        <header class="vm-assistant-head">
            <div class="vm-assistant-identity">
                <span class="vm-assistant-avatar" aria-hidden="true">V</span>
                <div>
                    <b id="vm-assistant-title">Vera · Asistente VoluntMatch</b>
                    <small><i aria-hidden="true"></i> En línea · respuestas con IA</small>
                </div>
            </div>
            <div class="vm-assistant-head-actions">
                <button type="button" data-chatbot-reset aria-label="Empezar una conversación nueva" title="Nueva conversación">
                    <x-icon name="spark" :size="17" />
                </button>
                <button type="button" data-chatbot-close aria-label="Cerrar asistente">
                    <x-icon name="close" :size="18" />
                </button>
            </div>
        </header>

        <div class="vm-assistant-messages" data-chatbot-messages aria-live="polite" aria-relevant="additions">
            <div class="vm-assistant-message assistant" data-chatbot-welcome>
                <span class="vm-assistant-bubble">
                    Hola, soy Vera. Puedo ayudarte con el registro, buscar oportunidades, revisar tus
                    postulaciones o abrir un caso de soporte. ¿Qué necesitas?
                </span>
                <small>Respuesta inmediata · soporte humano en menos de 24 h hábiles</small>
            </div>
        </div>

        <div class="vm-assistant-pending" data-chatbot-pending hidden>
            <p data-chatbot-pending-text></p>
            <div>
                <button type="button" data-chatbot-accept>Confirmar</button>
                <button type="button" data-chatbot-cancel>Cancelar</button>
            </div>
        </div>

        <div class="vm-assistant-suggestions" data-chatbot-suggestions aria-label="Sugerencias"></div>

        <form class="vm-assistant-form" data-chatbot-form autocomplete="off">
            <label class="sr-only" for="vm-assistant-input">Escribe tu mensaje</label>
            <textarea id="vm-assistant-input" data-chatbot-input rows="1" maxlength="{{ (int) config('chatbot.prompt.max_input_chars', 1200) }}"
                placeholder="Escribe tu mensaje…" enterkeyhint="send"></textarea>
            <button type="submit" data-chatbot-send aria-label="Enviar mensaje">
                <x-icon name="send" :size="18" />
            </button>
        </form>

        <p class="vm-assistant-note">
            No compartas contraseñas ni datos de tarjetas. El asistente puede equivocarse;
            <a href="{{ route('contacto') }}">escríbenos</a> si necesitas una persona.
        </p>
    </section>
</div>
