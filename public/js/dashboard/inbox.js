/*
 * VoluntMatch — Match Inbox de la ONG: etapas y notas internas
 * persistidas en la base de datos.
 */
/* ============================================================
   DASHBOARD ONG — Match Inbox
============================================================ */
const inboxList = document.querySelector('[data-inbox-list]');

if (inboxList) {
    const INBOX_STAGES = ['por-revisar', 'entrevista', 'aceptado', 'rechazado'];
    const INBOX_LABELS = {
        'por-revisar': 'Por revisar',
        entrevista: 'En entrevista',
        aceptado: 'Aceptado',
        rechazado: 'Rechazado',
    };
    const STAGE_TO_ESTADO = {
        'por-revisar': 'en revision',
        entrevista: 'en entrevista',
        aceptado: 'aceptado',
        rechazado: 'rechazado',
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const inboxKanban = document.querySelector('[data-inbox-kanban]');
    const inboxNewChip = document.querySelector('[data-inbox-new]');
    const inboxItems = [...inboxList.querySelectorAll('[data-candidate-item]')];
    const kanbanCards = [...document.querySelectorAll('[data-candidate-card]')];

    const stageOf = (item) => item.querySelector('[data-candidate-row]').dataset.stage;
    const stageIndex = (stage) => INBOX_STAGES.indexOf(stage);
    const cardOf = (item) =>
        kanbanCards.find((card) => card.dataset.postulacion === item.dataset.postulacion);

    const updateInboxCounts = () => {
        INBOX_STAGES.forEach((stage) => {
            const column = document.querySelector(`[data-kanban-column="${stage}"]`);
            const badge = column?.querySelector('[data-kanban-count]');
            if (badge) badge.textContent = column.querySelectorAll('[data-candidate-card]').length;
        });
        const pending = inboxItems.filter((item) => stageOf(item) === 'por-revisar').length;
        if (inboxNewChip) inboxNewChip.textContent = `${pending} por revisar`;
    };

    const syncCandidateUI = (item, stage, label) => {
        const row = item.querySelector('[data-candidate-row]');
        const badge = row.querySelector('[data-stage-badge]');
        row.dataset.stage = stage;
        badge.dataset.stage = stage;
        badge.textContent = label ?? INBOX_LABELS[stage] ?? stage;

        const settled = stage === 'aceptado' || stage === 'rechazado';
        const accept = row.querySelector('[data-candidate-accept]');
        const reject = row.querySelector('[data-candidate-reject]');
        if (accept) accept.hidden = settled;
        if (reject) reject.hidden = settled;

        const card = cardOf(item);
        if (card) {
            card.dataset.stage = stage;
            const column = document.querySelector(`[data-kanban-column="${stage}"] [data-kanban-cards]`);
            if (column) column.appendChild(card);
            const prev = card.querySelector('[data-candidate-move="prev"]');
            const next = card.querySelector('[data-candidate-move="next"]');
            if (prev) prev.hidden = stageIndex(stage) === 0;
            if (next) next.hidden = stageIndex(stage) === INBOX_STAGES.length - 1;
        }
    };

    const sortInboxList = () => {
        [...inboxItems]
            .sort((a, b) => stageIndex(stageOf(a)) - stageIndex(stageOf(b)))
            .forEach((item) => inboxList.appendChild(item));
    };

    /* Guarda la etapa en el servidor y refleja el resultado en la interfaz. */
    const persistCandidate = async (item, payload) => {
        const url = item.dataset.updateUrl;
        if (!url) return null;

        try {
            const respuesta = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify(payload),
            });

            if (!respuesta.ok) throw new Error('request failed');

            return await respuesta.json();
        } catch (error) {
            showToast('No pudimos guardar el cambio. Inténtalo de nuevo.');
            return null;
        }
    };

    const moveCandidate = async (item, stage, toastMessage) => {
        if (!INBOX_STAGES.includes(stage)) return;

        const datos = await persistCandidate(item, { estado: STAGE_TO_ESTADO[stage] });
        if (!datos) return;

        syncCandidateUI(item, datos.etapa, datos.legible);
        sortInboxList();
        updateInboxCounts();
        showToast(toastMessage ?? `${item.dataset.candidate} movido a ${datos.legible}`);
    };

    const setInboxView = (view) => {
        const kanban = view === 'kanban';
        inboxList.hidden = kanban;
        inboxKanban.hidden = !kanban;
        document
            .querySelectorAll('[data-inbox-view]')
            .forEach((btn) => btn.classList.toggle('active', btn.dataset.inboxView === view));
    };

    document.querySelectorAll('[data-inbox-view]').forEach((btn) => {
        btn.addEventListener('click', () => setInboxView(btn.dataset.inboxView));
    });

    const openCandidateNotes = (item) => {
        setInboxView('list');
        const panel = item.querySelector('[data-candidate-notes]');
        panel.hidden = false;
        item.querySelector('[data-candidate-notes-toggle]').classList.add('active');
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        panel.querySelector('[data-candidate-note-input]').focus();
    };

    inboxItems.forEach((item) => {
        const name = item.dataset.candidate;
        const project = item.dataset.project || 'el proyecto';
        const row = item.querySelector('[data-candidate-row]');
        const notesPanel = item.querySelector('[data-candidate-notes]');
        const notesInput = notesPanel.querySelector('[data-candidate-note-input]');
        const notesToggle = row.querySelector('[data-candidate-notes-toggle]');

        syncCandidateUI(item, stageOf(item));

        row.querySelector('[data-candidate-accept]').addEventListener('click', () => {
            moveCandidate(item, 'aceptado', `${name} aceptado en ${project}`);
        });

        row.querySelector('[data-candidate-reject]').addEventListener('click', () => {
            moveCandidate(
                item,
                'rechazado',
                `${name} descartado · Mensaje enviado: "Gracias por tu interés en ${project}."`,
            );
        });

        notesToggle.addEventListener('click', () => {
            const open = notesPanel.hidden;
            notesPanel.hidden = !open;
            notesToggle.classList.toggle('active', open);
            notesToggle.setAttribute('aria-expanded', String(open));
            if (open) notesInput.focus();
        });

        notesPanel.querySelector('[data-candidate-note-save]').addEventListener('click', async () => {
            const nota = notesInput.value.trim();
            const datos = await persistCandidate(item, { nota_interna: nota === '' ? null : nota });
            if (!datos) return;
            notesInput.value = datos.nota_interna ?? '';
            showToast(nota ? `Nota interna guardada sobre ${name}` : `Nota de ${name} eliminada`);
        });
    });

    kanbanCards.forEach((card) => {
        const item = inboxItems.find((el) => el.dataset.postulacion === card.dataset.postulacion);
        if (!item) return;

        card.querySelectorAll('[data-candidate-move]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const next = stageIndex(stageOf(item)) + (btn.dataset.candidateMove === 'next' ? 1 : -1);
                moveCandidate(item, INBOX_STAGES[next]);
            });
        });

        card.querySelector('[data-candidate-notes-jump]')?.addEventListener('click', () => {
            openCandidateNotes(item);
        });
    });

    sortInboxList();
    updateInboxCounts();
}
