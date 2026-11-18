/**
 * VoluntMatch — Asistente virtual.
 *
 * Widget sin dependencias. La seguridad real vive en el servidor: aquí solo
 * se pinta texto plano (nunca HTML del modelo), se validan URLs internas y
 * se envían los tokens de confirmación de un solo uso.
 */
(() => {
    const root = document.querySelector('[data-chatbot]');
    if (!root) return;

    const panel = root.querySelector('[data-chatbot-panel]');
    const toggle = root.querySelector('[data-chatbot-toggle]');
    const closeButton = root.querySelector('[data-chatbot-close]');
    const resetButton = root.querySelector('[data-chatbot-reset]');
    const messages = root.querySelector('[data-chatbot-messages]');
    const form = root.querySelector('[data-chatbot-form]');
    const input = root.querySelector('[data-chatbot-input]');
    const sendButton = root.querySelector('[data-chatbot-send]');
    const suggestionsBox = root.querySelector('[data-chatbot-suggestions]');
    const pendingBox = root.querySelector('[data-chatbot-pending]');
    const pendingText = root.querySelector('[data-chatbot-pending-text]');
    const acceptButton = root.querySelector('[data-chatbot-accept]');
    const cancelButton = root.querySelector('[data-chatbot-cancel]');
    const welcome = root.querySelector('[data-chatbot-welcome]');

    if (!panel || !toggle || !messages || !form || !input) return;

    const endpoints = {
        mensaje: root.dataset.endpoint,
        historial: root.dataset.history,
        confirmar: root.dataset.confirm,
        reiniciar: root.dataset.reset,
    };
    const csrf = root.dataset.csrf || '';
    const storageKey = 'vm-assistant-conversation';

    let defaultSuggestions = [];
    try {
        defaultSuggestions = JSON.parse(root.dataset.suggestions || '[]');
    } catch (error) {
        defaultSuggestions = [];
    }

    const state = {
        conversationId: readConversationId(),
        loading: false,
        historyLoaded: false,
        pending: null,
        lastFocus: null,
    };

    function readConversationId() {
        try {
            return sessionStorage.getItem(storageKey) || null;
        } catch (error) {
            return null;
        }
    }

    function saveConversationId(id) {
        state.conversationId = id || null;
        try {
            if (id) sessionStorage.setItem(storageKey, id);
            else sessionStorage.removeItem(storageKey);
        } catch (error) {
            /* Sin almacenamiento: la sesión del servidor sigue resolviendo el hilo. */
        }
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.headers || {}),
            },
            ...options,
        });

        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            data = null;
        }

        if (!response.ok) {
            const message = data?.message
                || (response.status === 429
                    ? 'Has enviado muchos mensajes seguidos. Espera un minuto y vuelve a intentarlo.'
                    : response.status === 419
                        ? 'Tu sesión caducó. Recarga la página para continuar.'
                        : response.status === 503
                            ? 'El asistente no está disponible ahora mismo. Escríbenos desde la página de contacto.'
                            : 'No pude completar la solicitud. Inténtalo de nuevo en unos segundos.');
            throw new Error(message);
        }

        return data;
    }

    function openPanel() {
        state.lastFocus = document.activeElement;
        panel.hidden = false;
        root.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Cerrar asistente de VoluntMatch');
        input.focus({ preventScroll: true });

        if (!state.historyLoaded) {
            state.historyLoaded = true;
            loadHistory();
        }
    }

    function closePanel() {
        panel.hidden = true;
        root.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Abrir asistente de VoluntMatch');

        if (state.lastFocus && typeof state.lastFocus.focus === 'function') {
            state.lastFocus.focus({ preventScroll: true });
        } else {
            toggle.focus({ preventScroll: true });
        }
    }

    async function loadHistory() {
        try {
            const query = state.conversationId
                ? '?conversation_id=' + encodeURIComponent(state.conversationId)
                : '';
            const data = await request(endpoints.historial + query, { method: 'GET' });

            if (data?.conversation_id) saveConversationId(data.conversation_id);

            if (Array.isArray(data?.messages) && data.messages.length > 0) {
                removeWelcome();
                data.messages.forEach((message) => {
                    appendMessage(message.role, message.text, message.sources || [], []);
                });
                scrollToBottom();
            }

            renderSuggestions(data?.suggestions || defaultSuggestions);
        } catch (error) {
            renderSuggestions(defaultSuggestions);
        }
    }

    function removeWelcome() {
        if (welcome && welcome.parentNode) welcome.parentNode.removeChild(welcome);
    }

    function appendMessage(role, text, sources = [], links = []) {
        const isUser = role === 'user';
        const wrapper = document.createElement('div');
        wrapper.className = 'vm-assistant-message ' + (isUser ? 'user' : 'assistant');

        const bubble = document.createElement('span');
        bubble.className = 'vm-assistant-bubble';
        bubble.textContent = String(text || '');
        wrapper.appendChild(bubble);

        if (!isUser && Array.isArray(sources) && sources.length > 0) {
            const sourceList = document.createElement('div');
            sourceList.className = 'vm-assistant-sources';
            const seen = new Set();

            sources.slice(0, 4).forEach((source) => {
                const title = typeof source?.title === 'string' ? source.title : '';
                if (!title || seen.has(title)) return;
                seen.add(title);
                const chip = document.createElement('span');
                chip.textContent = title;
                sourceList.appendChild(chip);
            });

            if (sourceList.childElementCount > 0) wrapper.appendChild(sourceList);
        }

        if (!isUser && Array.isArray(links) && links.length > 0) {
            const linkList = document.createElement('div');
            linkList.className = 'vm-assistant-links';
            const seen = new Set();

            links.slice(0, 4).forEach((link) => {
                const url = safeUrl(link?.url);
                const label = typeof link?.label === 'string' ? link.label : '';
                if (!url || !label || seen.has(url)) return;
                seen.add(url);

                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.textContent = label;
                linkList.appendChild(anchor);
            });

            if (linkList.childElementCount > 0) wrapper.appendChild(linkList);
        }

        const time = document.createElement('small');
        time.textContent = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
        wrapper.appendChild(time);

        messages.appendChild(wrapper);
        scrollToBottom();
    }

    function safeUrl(raw) {
        if (typeof raw !== 'string' || raw.trim() === '') return null;

        try {
            const url = new URL(raw, window.location.origin);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') return null;
            if (url.origin !== window.location.origin) return null;
            return url.pathname + url.search + url.hash;
        } catch (error) {
            return null;
        }
    }

    function renderSuggestions(items) {
        suggestionsBox.replaceChildren();

        (Array.isArray(items) ? items : []).slice(0, 4).forEach((label) => {
            if (typeof label !== 'string' || label.trim() === '') return;
            if (state.pending && (label === 'Confirmar' || label === 'Cancelar')) return;

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.addEventListener('click', () => {
                if (state.loading) return;
                sendMessage(label);
            });
            suggestionsBox.appendChild(button);
        });
    }

    function showTyping() {
        removeTyping();
        const wrapper = document.createElement('div');
        wrapper.className = 'vm-assistant-message assistant';
        wrapper.dataset.chatbotTyping = '1';

        const bubble = document.createElement('span');
        bubble.className = 'vm-assistant-bubble vm-assistant-typing';
        bubble.setAttribute('aria-label', 'Vera está escribiendo');
        for (let i = 0; i < 3; i++) bubble.appendChild(document.createElement('i'));

        wrapper.appendChild(bubble);
        messages.appendChild(wrapper);
        messages.setAttribute('aria-busy', 'true');
        scrollToBottom();
    }

    function removeTyping() {
        const typing = messages.querySelector('[data-chatbot-typing="1"]');
        if (typing) typing.remove();
        messages.removeAttribute('aria-busy');
    }

    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function setLoading(value) {
        state.loading = value;
        input.disabled = value;
        sendButton.disabled = value;
    }

    function applyResponse(data, includeUser = false) {
        if (!data) throw new Error('Respuesta vacía del asistente.');

        if (data.conversation_id) saveConversationId(data.conversation_id);

        if (includeUser && data.user_message) appendMessage('user', data.user_message);

        appendMessage('assistant', data.message || '', data.sources || [], data.links || []);
        renderSuggestions(data.suggestions || defaultSuggestions);
        setPending(data.pending || null);

        if (data.pending) {
            pendingBox.hidden = false;
            pendingText.textContent = data.pending.summary || '¿Confirmas la acción?';
        }
    }

    function setPending(pending) {
        state.pending = pending || null;
        pendingBox.hidden = !state.pending;
        if (!state.pending) {
            pendingText.textContent = '';
        }
    }

    async function sendMessage(text) {
        const clean = String(text || '').replace(/\s+/g, ' ').trim();
        if (clean === '' || state.loading) return;

        removeWelcome();
        setPending(null);
        appendMessage('user', clean);
        input.value = '';
        resizeInput();
        showTyping();
        setLoading(true);

        try {
            const data = await request(endpoints.mensaje, {
                method: 'POST',
                body: JSON.stringify({
                    message: clean,
                    conversation_id: state.conversationId,
                }),
            });

            removeTyping();
            applyResponse(data);
        } catch (error) {
            removeTyping();
            appendMessage('assistant', error.message || 'No pude responder. Inténtalo de nuevo.');
            renderSuggestions(defaultSuggestions);
        } finally {
            setLoading(false);
            input.focus({ preventScroll: true });
        }
    }

    async function confirmAction(decision) {
        if (!state.pending || state.loading) return;

        const token = state.pending.token;
        if (!token) {
            setPending(null);
            return;
        }

        appendMessage('user', decision === 'accept' ? 'Confirmo la acción.' : 'Cancelo la acción.');
        showTyping();
        setLoading(true);

        try {
            const data = await request(endpoints.confirmar, {
                method: 'POST',
                body: JSON.stringify({ token, decision }),
            });

            removeTyping();
            applyResponse(data);
        } catch (error) {
            removeTyping();
            appendMessage('assistant', error.message || 'No pude completar la confirmación.');
        } finally {
            setLoading(false);
            input.focus({ preventScroll: true });
        }
    }

    async function resetConversation() {
        if (state.loading) return;

        setLoading(true);
        try {
            await request(endpoints.reiniciar, { method: 'POST', body: '{}' });
        } catch (error) {
            /* El servidor puede haber cerrado ya el hilo; limpiamos igual. */
        } finally {
            saveConversationId(null);
            state.historyLoaded = true;
            setPending(null);
            messages.replaceChildren();
            const intro = document.createElement('div');
            intro.className = 'vm-assistant-message assistant';
            intro.dataset.chatbotWelcome = '1';
            const bubble = document.createElement('span');
            bubble.className = 'vm-assistant-bubble';
            bubble.textContent = 'Empezamos de nuevo. ¿En qué te puedo ayudar?';
            intro.appendChild(bubble);
            messages.appendChild(intro);
            renderSuggestions(defaultSuggestions);
            setLoading(false);
            input.focus({ preventScroll: true });
        }
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(104, Math.max(22, input.scrollHeight)) + 'px';
    }

    toggle.addEventListener('click', () => {
        if (panel.hidden) openPanel();
        else closePanel();
    });

    closeButton?.addEventListener('click', closePanel);
    resetButton?.addEventListener('click', resetConversation);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
    });

    input.addEventListener('input', resizeInput);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage(input.value);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) closePanel();
    });

    acceptButton?.addEventListener('click', () => confirmAction('accept'));
    cancelButton?.addEventListener('click', () => confirmAction('cancel'));

    renderSuggestions(defaultSuggestions);
    resizeInput();
})();
