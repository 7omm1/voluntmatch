/*
 * VoluntMatch — Match Inbox: etapas, rechazo y notas internas
 */
/* ============================================================
   DASHBOARD ONG — Match Inbox: etapas, rechazo y notas internas
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
    const INBOX_STORAGE_KEY = 'voluntmatch-inbox';

    const inboxKanban = document.querySelector('[data-inbox-kanban]');
    const inboxNewChip = document.querySelector('[data-inbox-new]');
    const inboxItems = [...inboxList.querySelectorAll('[data-candidate-item]')];
    const kanbanCards = [...document.querySelectorAll('[data-candidate-card]')];

    /* Etapas y notas persistidas en el navegador (demo sin backend) */
    let inboxState = { stages: {}, notes: {} };
    try {
        inboxState = { ...inboxState, ...JSON.parse(localStorage.getItem(INBOX_STORAGE_KEY) || '{}') };
        inboxState.stages = inboxState.stages || {};
        inboxState.notes = inboxState.notes || {};
    } catch (error) {
        inboxState = { stages: {}, notes: {} };
    }

    const saveInboxState = () => localStorage.setItem(INBOX_STORAGE_KEY, JSON.stringify(inboxState));
    const stageOf = (item) =>
        inboxState.stages[item.dataset.candidate] ||
        item.querySelector('[data-candidate-row]').dataset.stage;
    const stageIndex = (stage) => INBOX_STAGES.indexOf(stage);
    const cardOf = (item) =>
        kanbanCards.find((card) => card.dataset.candidate === item.dataset.candidate);

    const updateInboxCounts = () => {
        INBOX_STAGES.forEach((stage) => {
            const column = document.querySelector(`[data-kanban-column="${stage}"]`);
            const badge = column?.querySelector('[data-kanban-count]');
            if (badge) badge.textContent = column.querySelectorAll('[data-candidate-card]').length;
        });
        const pending = inboxItems.filter((item) => stageOf(item) === 'por-revisar').length;
        if (inboxNewChip) inboxNewChip.textContent = `${pending} por revisar`;
    };

    /* Aplica una etapa a la fila de lista y a su tarjeta kanban */
    const syncCandidateUI = (item, stage) => {
        const row = item.querySelector('[data-candidate-row]');
        const badge = row.querySelector('[data-stage-badge]');
        row.dataset.stage = stage;
        badge.dataset.stage = stage;
        badge.textContent = INBOX_LABELS[stage] ?? stage;

        const settled = stage === 'aceptado' || stage === 'rechazado';
        row.querySelector('[data-candidate-accept]').hidden = settled;
        row.querySelector('[data-candidate-reject]').hidden = settled;

        const card = cardOf(item);
        if (card) {
            card.dataset.stage = stage;
            card.toggleAttribute('data-has-note', Boolean(inboxState.notes[item.dataset.candidate]));
            const column = document.querySelector(`[data-kanban-column="${stage}"] [data-kanban-cards]`);
            if (column) column.appendChild(card);
            const prev = card.querySelector('[data-candidate-move="prev"]');
            const next = card.querySelector('[data-candidate-move="next"]');
            if (prev) prev.hidden = stageIndex(stage) === 0;
            if (next) next.hidden = stageIndex(stage) === INBOX_STAGES.length - 1;
        }
    };

    /* Ordena la bandeja: lo pendiente arriba, descartados al fondo */
    const sortInboxList = () => {
        [...inboxItems]
            .sort((a, b) => stageIndex(stageOf(a)) - stageIndex(stageOf(b)))
            .forEach((item) => inboxList.appendChild(item));
    };

    const moveCandidate = (item, stage, toastMessage) => {
        if (!INBOX_STAGES.includes(stage)) return;
        inboxState.stages[item.dataset.candidate] = stage;
        saveInboxState();
        syncCandidateUI(item, stage);
        sortInboxList();
        updateInboxCounts();
        showToast(toastMessage ?? `${item.dataset.candidate} movido a ${INBOX_LABELS[stage]}`);
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

    /* Abre la nota interna de un candidato y cambia a la vista lista */
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

        /* Aceptar: pasa a la etapa "Aceptado" */
        row.querySelector('[data-candidate-accept]').addEventListener('click', () => {
            moveCandidate(item, 'aceptado', `${name} aceptado en ${project}`);
        });

        /* Rechazar: descarta y notifica con mensaje automático amable */
        row.querySelector('[data-candidate-reject]').addEventListener('click', () => {
            moveCandidate(
                item,
                'rechazado',
                `${name} descartado · Mensaje enviado: "Gracias por tu interés en ${project}. Seguimos con otros perfiles, ¡éxitos!"`,
            );
        });

        /* Nota interna: se guarda en el navegador */
        notesInput.value = inboxState.notes[name] || '';
        notesToggle.addEventListener('click', () => {
            const open = notesPanel.hidden;
            notesPanel.hidden = !open;
            notesToggle.classList.toggle('active', open);
            notesToggle.setAttribute('aria-expanded', String(open));
            if (open) notesInput.focus();
        });
        notesPanel.querySelector('[data-candidate-note-save]').addEventListener('click', () => {
            const note = notesInput.value.trim();
            if (note) inboxState.notes[name] = note;
            else delete inboxState.notes[name];
            saveInboxState();
            syncCandidateUI(item, stageOf(item));
            showToast(note ? `Nota interna guardada sobre ${name}` : `Nota de ${name} eliminada`);
        });
    });

    /* Mover candidatos entre etapas desde el kanban */
    kanbanCards.forEach((card) => {
        const item = inboxItems.find((el) => el.dataset.candidate === card.dataset.candidate);
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
