/*
 * VoluntMatch — Chats: hilos, plantillas de respuesta, agendar
 reunión y archivos compartidos
 */
/* ============================================================
   DASHBOARD — chat
============================================================ */
document.querySelectorAll('[data-chat-form]').forEach((form) => {
    const input = form.querySelector('[data-chat-input]');
    const list = document.querySelector('[data-chat]');
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const text = input?.value.trim();
        if (!text || !list) return;
        const message = document.createElement('div');
        message.className = 'mine';
        const now = new Date();
        const time = `${now.getHours()}:${String(now.getMinutes()).padStart(2, '0')}`;
        message.innerHTML = `<span></span><small>${time}</small>`;
        message.querySelector('span').textContent = text;
        list.appendChild(message);
        input.value = '';
        list.scrollTop = list.scrollHeight;
    });
});

/* ============================================================
   DASHBOARD — mensajes: plantillas, agendar reunión y archivos
============================================================ */
const messagesPanel = document.querySelector('[data-panel="messages"]');

if (messagesPanel) {
    const chatInput = messagesPanel.querySelector('[data-chat-input]');

    /* Plantillas de respuesta rápida: cargan el texto en el input para editarlo */
    messagesPanel.querySelectorAll('[data-template]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!chatInput) return;
            chatInput.value = button.dataset.template;
            chatInput.focus();
            showToast('Plantilla cargada: edítala antes de enviarla');
        });
    });

    /* Agendar reunión: enlace de Meet o invitación de Google Calendar */
    const scheduleButtons = messagesPanel.querySelectorAll('[data-schedule]');
    if (scheduleButtons.length) {
        const volunteerName = messagesPanel.querySelector('.chat-panel header b')?.textContent ?? 'el voluntario';
        const projectName = messagesPanel.querySelector('.chat-context h3')?.textContent ?? 'Proyecto VoluntMatch';

        /* Siguiente jueves 17:00 (30 min de duración) */
        const nextMeeting = () => {
            const start = new Date();
            const daysAhead = (4 - start.getDay() + 7) % 7 || 7;
            start.setDate(start.getDate() + daysAhead);
            start.setHours(17, 0, 0, 0);
            const end = new Date(start.getTime() + 30 * 60 * 1000);
            return { start, end };
        };
        const calendarStamp = (date) =>
            `${date.getFullYear()}${String(date.getMonth() + 1).padStart(2, '0')}${String(date.getDate()).padStart(2, '0')}T${String(date.getHours()).padStart(2, '0')}${String(date.getMinutes()).padStart(2, '0')}00`;

        scheduleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.schedule === 'meet') {
                    window.open('https://meet.google.com/new?pli=1', '_blank', 'noopener');
                    showToast(`Enlace de Meet creado para ${volunteerName}`);
                    return;
                }
                const { start, end } = nextMeeting();
                const params = new URLSearchParams({
                    action: 'TEMPLATE',
                    text: `Entrevista · ${projectName}`,
                    details: `Reunión con ${volunteerName} coordinada desde VoluntMatch.`,
                    dates: `${calendarStamp(start)}/${calendarStamp(end)}`,
                });
                window.open(`https://calendar.google.com/calendar/render?${params}`, '_blank', 'noopener');
                showToast(`Invitación del ${start.toLocaleDateString('es', { weekday: 'long' })} a las 17:00 lista`);
            });
        });
    }

    /* Archivos compartidos: descarga/abre con aviso (demo) */
    messagesPanel.querySelectorAll('[data-shared-file]').forEach((button) => {
        button.addEventListener('click', () => {
            showToast(`Abriendo "${button.dataset.sharedFile}"…`);
        });
    });

    /* Botón + del chat: adjuntar archivo */
    messagesPanel.querySelector('[data-chat-attach]')?.addEventListener('click', () => {
        showToast('Adjuntar archivos: disponible muy pronto');
    });
}
