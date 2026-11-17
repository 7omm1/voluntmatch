/**
 * VoluntMatch — Interactividad del sitio
 * (portado de la app React de Plataforma_solidaria)
 */

/* ============================================================
   TEMA (modo claro / oscuro)
============================================================ */
document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('voluntmatch-theme', dark ? 'dark' : 'light');
        } catch (e) {}
        showToast(dark ? 'Modo oscuro activado' : 'Modo claro activado');
    });
});

/* ============================================================
   MENÚ MÓVIL
============================================================ */
const mobileToggle = document.querySelector('[data-mobile-toggle]');
const mobileNav = document.querySelector('[data-mobile-nav]');

if (mobileToggle && mobileNav) {
    mobileToggle.addEventListener('click', () => {
        const open = mobileNav.classList.toggle('open');
        mobileToggle.classList.toggle('open', open);
        mobileToggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });
}

/* ============================================================
   ANIMACIÓN DE APARICIÓN (reveal)
============================================================ */
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.14 },
);
document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));

/* ============================================================
   BRILLO DEL CURSOR
============================================================ */
const cursorGlow = document.querySelector('[data-cursor-glow]');
if (cursorGlow) {
    window.addEventListener('mousemove', (event) => {
        cursorGlow.style.transform = `translate3d(${event.clientX - 170}px, ${event.clientY - 170}px, 0)`;
    });
}

/* ============================================================
   HERO SLIDER
============================================================ */
const heroSlides = [...document.querySelectorAll('[data-hero-slide]')];
const heroDots = [...document.querySelectorAll('[data-hero-dot]')];
const heroVideo = document.querySelector('[data-hero-video]');
const heroPlay = document.querySelector('[data-hero-play]');
const heroCounter = document.querySelector('[data-hero-counter]');

if (heroSlides.length > 0) {
    let slideIndex = 0;
    let playing = true;
    let timer = null;

    const showSlide = (index) => {
        slideIndex = (index + heroSlides.length) % heroSlides.length;
        heroSlides.forEach((el, i) => el.classList.toggle('active', i === slideIndex));
        heroDots.forEach((el, i) => el.classList.toggle('active', i === slideIndex));
        if (heroCounter) {
            heroCounter.textContent = `0${slideIndex + 1} / 0${heroSlides.length}`;
        }
        if (heroVideo) {
            const current = heroSlides[slideIndex];
            heroVideo.poster = current.dataset.poster || heroVideo.poster;
            if (heroVideo.currentSrc !== current.dataset.video) {
                heroVideo.src = current.dataset.video;
                heroVideo.load();
                if (playing) heroVideo.play().catch(() => {});
            }
        }
    };

    const stopTimer = () => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    const startTimer = () => {
        stopTimer();
        timer = window.setInterval(() => showSlide(slideIndex + 1), 8500);
    };

    heroDots.forEach((dot, index) => {
        dot.addEventListener('click', () => showSlide(index));
    });

    if (heroPlay) {
        heroPlay.addEventListener('click', () => {
            playing = !playing;
            const iconPause = heroPlay.querySelector('.icon-pause');
            const iconPlay = heroPlay.querySelector('.icon-play');
            if (iconPause) iconPause.hidden = !playing;
            if (iconPlay) iconPlay.hidden = playing;
            if (playing) {
                heroVideo?.play().catch(() => {});
                startTimer();
            } else {
                heroVideo?.pause();
                stopTimer();
            }
        });
        const iconPlay = heroPlay.querySelector('.icon-play');
        if (iconPlay) iconPlay.hidden = true;
        startTimer();
    }
}

/* ============================================================
   TOASTS
============================================================ */
const toast = document.querySelector('[data-toast]');
const toastText = document.querySelector('[data-toast-text]');
let toastTimer = null;

function showToast(message) {
    if (!toast || !toastText) return;
    toastText.textContent = message;
    toast.hidden = false;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 2600);
}

document.querySelectorAll('[data-toast-msg]').forEach((el) => {
    el.addEventListener('click', () => showToast(el.dataset.toastMsg));
});

/* ============================================================
   DASHBOARD — navegación entre secciones
============================================================ */
const dashPanels = [...document.querySelectorAll('[data-panel]')];
const dashButtons = [...document.querySelectorAll('[data-section]')];
const dashHeading = document.querySelector('[data-dash-heading]');
const dashTopLabel = document.querySelector('.dashboard-top p');

function showSection(key) {
    const panel = dashPanels.find((el) => el.dataset.panel === key);
    if (!panel) return;
    dashPanels.forEach((el) => (el.hidden = el !== panel));
    dashButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.section === key));
    if (dashHeading && panel.dataset.heading) {
        dashHeading.textContent = panel.dataset.heading;
    }
    if (dashTopLabel) {
        const zone = dashTopLabel.textContent.includes('CENTRO DE CONTROL')
            ? 'CENTRO DE CONTROL'
            : 'ESPACIO VOLUNTMATCH';
        dashTopLabel.textContent = `${zone} · ${panel.dataset.heading.toUpperCase()}`;
    }
    document.querySelector('.dashboard-main')?.scrollTo({ top: 0 });
}

dashButtons.forEach((btn) => {
    btn.addEventListener('click', () => showSection(btn.dataset.section));
});
document.querySelectorAll('[data-goto]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const key = btn.dataset.goto;
        const navBtn = dashButtons.find((el) => el.dataset.section === key);
        if (navBtn) showSection(key);
        else showToast('Muy pronto disponible');
    });
});

/* ============================================================
   DASHBOARD — ficha de proyecto (modal de la ONG)
============================================================ */
const projectModal = document.querySelector('[data-project-modal]');
if (projectModal) {
    const projectField = (name) => projectModal.querySelector(`[data-project-${name}]`);
    const projectLink = projectModal.querySelector('[data-project-link]');
    const projectBase = projectModal.dataset.projectBase || '';

    const openProject = (trigger) => {
        const data = trigger.dataset;
        [
            ['title', data.title],
            ['org', data.org],
            ['cause', data.cause],
            ['skill', data.skill],
            ['time', data.time],
        ].forEach(([name, value]) => {
            const el = projectField(name);
            if (el && value) el.textContent = value;
        });
        if (projectLink && data.id) projectLink.href = `${projectBase}${data.id}`;
        projectModal.hidden = false;
        projectModal.querySelector('.project-detail-modal')?.scrollTo({ top: 0 });
    };

    const closeProject = () => {
        projectModal.hidden = true;
    };

    document.querySelectorAll('[data-project-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => openProject(trigger));
    });
    document.querySelectorAll('[data-project-close]').forEach((btn) => {
        btn.addEventListener('click', closeProject);
    });
    projectModal.addEventListener('click', (event) => {
        if (event.target === projectModal) closeProject();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !projectModal.hidden) closeProject();
    });
}

/* ============================================================
   DASHBOARD — baraja de matches estilo Tinder
============================================================ */
const swipeDeck = document.querySelector('[data-swipe-deck]');
if (swipeDeck) {
    const stage = swipeDeck.querySelector('[data-deck-stage]');
    const emptyState = swipeDeck.querySelector('[data-deck-empty]');
    const initialDeckHTML = stage.innerHTML;
    const SWIPE_OUT = 110;

    const bindCard = (card) => {
        card.addEventListener('pointerdown', (event) => {
            if (!card.classList.contains('is-top')) return;
            event.preventDefault();
            card.setPointerCapture(event.pointerId);
            card.classList.add('is-dragging');

            const startX = event.clientX;
            const startY = event.clientY;
            const like = card.querySelector('.stamp-like');
            const nope = card.querySelector('.stamp-nope');

            const onMove = (moveEvent) => {
                const dx = moveEvent.clientX - startX;
                const dy = moveEvent.clientY - startY;
                card.style.transform = `translate(${dx}px, ${dy}px) rotate(${dx / 22}deg)`;
                if (like) like.style.opacity = Math.max(0, Math.min(1, dx / 90));
                if (nope) nope.style.opacity = Math.max(0, Math.min(1, -dx / 90));
            };

            const onUp = (upEvent) => {
                card.removeEventListener('pointermove', onMove);
                card.removeEventListener('pointerup', onUp);
                card.removeEventListener('pointercancel', onUp);
                card.classList.remove('is-dragging');
                const dx = upEvent.clientX - startX;

                if (Math.abs(dx) > SWIPE_OUT) {
                    swipeCard(card, dx > 0 ? 1 : -1, Math.abs(dx));
                } else {
                    card.style.transform = '';
                    if (like) like.style.opacity = 0;
                    if (nope) nope.style.opacity = 0;
                }
            };

            card.addEventListener('pointermove', onMove);
            card.addEventListener('pointerup', onUp);
            card.addEventListener('pointercancel', onUp);
        });
    };

    const cardsInDeck = () => [...stage.querySelectorAll('[data-swipe-card]')];

    const restack = () => {
        const cards = cardsInDeck();
        cards.forEach((card, i) => {
            const depth = Math.min(i, 2);
            card.classList.toggle('is-top', i === 0);
            card.classList.remove('is-out');
            card.style.zIndex = cards.length - i;
            card.style.setProperty('--depth', depth);
            if (!card.classList.contains('is-dragging')) {
                card.style.transform = '';
            }
            const like = card.querySelector('.stamp-like');
            const nope = card.querySelector('.stamp-nope');
            if (like) like.style.opacity = 0;
            if (nope) nope.style.opacity = 0;
        });

        if (emptyState) {
            emptyState.hidden = cards.length > 0;
        }
        if (stage) {
            stage.hidden = cards.length === 0;
        }
        swipeDeck.classList.toggle('deck-done', cards.length === 0);
    };

    const swipeCard = (card, direction, distance = SWIPE_OUT + 120) => {
        if (!card || !card.classList.contains('is-top')) return;
        card.classList.remove('is-top');
        card.style.transition = 'transform 0.45s cubic-bezier(0.3, 0, 0.7, 1), opacity 0.45s';
        card.style.transform = `translate(${direction * distance * 1.6}px, 110px) rotate(${direction * 22}deg)`;
        card.style.opacity = '0';
        card.style.pointerEvents = 'none';
        card.classList.add('is-out');

        if (direction > 0) {
            const title = card.dataset.title || card.querySelector('h3')?.textContent?.trim();
            applyTo(title);
        } else {
            showToast('Match descartado');
        }

        window.setTimeout(restack, 240);
        window.setTimeout(() => card.remove(), 480);
    };

    cardsInDeck().forEach(bindCard);
    restack();

    swipeDeck.querySelector('[data-deck-like]')?.addEventListener('click', () => {
        swipeCard(cardsInDeck()[0], 1);
    });
    swipeDeck.querySelector('[data-deck-nope]')?.addEventListener('click', () => {
        swipeCard(cardsInDeck()[0], -1);
    });

    swipeDeck.querySelector('[data-deck-reset]')?.addEventListener('click', () => {
        stage.innerHTML = initialDeckHTML;
        cardsInDeck().forEach(bindCard);
        restack();
        showToast('Baraja reiniciada');
    });
}

/* ============================================================
   DASHBOARD — postulaciones
============================================================ */
const applicationsNew = document.querySelector('[data-applications-new]');
const applicationsCount = document.querySelector('[data-applications-count]');
const applied = new Set();

function applyTo(title) {
    if (!title || applied.has(title)) {
        if (title) showToast('Ya estás postulado a este reto');
        return;
    }
    applied.add(title);
    if (applicationsNew) {
        const row = document.createElement('div');
        row.className = 'application-row';
        row.innerHTML = `
            <span class="list-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.4 5.4 0 0 0-7.6 0L12 5.8l-1.2-1.2a5.4 5.4 0 0 0-7.6 7.6l1.2 1.2L12 21l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z"/></svg></span>
            <span><b>${title}</b><small>Nueva organización</small></span>
            <span class="status en-revisión">En revisión</span>
            <em>Enviada hoy</em>
        `;
        applicationsNew.appendChild(row);
    }
    if (applicationsCount) {
        applicationsCount.textContent = `${3 + applied.size} activas`;
    }
    showToast('Postulación enviada correctamente');
}

document.querySelectorAll('[data-apply]').forEach((el) => {
    el.addEventListener('click', () => applyTo(el.dataset.apply));
});

/* ============================================================
   DASHBOARD — sliders de habilidades / algoritmo
============================================================ */
document.querySelectorAll('[data-range]').forEach((input) => {
    const label = input.closest('label');
    const value = label?.querySelector('[data-range-value]');
    input.addEventListener('input', () => {
        if (value) value.textContent = `${input.value}%`;
    });
});

/* ============================================================
   DASHBOARD — estrellas de valoración
============================================================ */
document.querySelectorAll('.stars').forEach((group) => {
    const stars = [...group.querySelectorAll('[data-star]')];
    stars.forEach((star, index) => {
        star.addEventListener('click', () => {
            stars.forEach((s, i) => s.classList.toggle('active', i <= index));
        });
    });
});

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
   DASHBOARD — buscador, filtros y favoritos de talento
============================================================ */
const talentSearch = document.querySelector('[data-talent-search]');
const talentFilterButtons = [...document.querySelectorAll('[data-talent-filter]')];
const talentCards = [...document.querySelectorAll('[data-talent-card]')];
const talentFavCount = document.querySelector('[data-fav-count]');
const talentEmpty = document.querySelector('[data-talent-empty]');
const talentFilters = { career: null, skill: null, hours: null, match: null, fav: false };

const talentHoursBucket = (hours) => (hours <= 5 ? 'low' : hours <= 10 ? 'mid' : 'high');

function applyTalentFilters() {
    const query = talentSearch ? talentSearch.value.toLowerCase().trim() : '';
    let visible = 0;
    talentCards.forEach((card) => {
        const ds = card.dataset;
        const matchQuery = !query || (ds.search || '').includes(query);
        const matchCareer = !talentFilters.career || ds.career === talentFilters.career;
        const matchSkill =
            !talentFilters.skill || (ds.skills || '').includes(talentFilters.skill.toLowerCase());
        const matchHours =
            !talentFilters.hours || talentHoursBucket(Number(ds.hours)) === talentFilters.hours;
        const matchMin = !talentFilters.match || Number(ds.match) >= Number(talentFilters.match);
        const matchFav = !talentFilters.fav || card.classList.contains('is-saved');
        const visibleCard =
            matchQuery && matchCareer && matchSkill && matchHours && matchMin && matchFav;
        card.style.display = visibleCard ? '' : 'none';
        if (visibleCard) visible += 1;
    });
    if (talentEmpty) talentEmpty.hidden = visible > 0;
}

if (talentSearch) {
    talentSearch.addEventListener('input', applyTalentFilters);
}

talentFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const type = button.dataset.talentFilter;
        if (type === 'fav') {
            talentFilters.fav = !talentFilters.fav;
            button.classList.toggle('active', talentFilters.fav);
        } else {
            const isActive = button.classList.contains('active');
            talentFilterButtons.forEach((btn) => {
                if (btn.dataset.talentFilter === type) btn.classList.remove('active');
            });
            talentFilters[type] = isActive ? null : button.dataset.filterValue;
            if (!isActive) button.classList.add('active');
        }
        applyTalentFilters();
    });
});

/* Guardar en favoritos (shortlist) */
document.querySelectorAll('[data-talent-save]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('[data-talent-card]');
        if (!card) return;
        const saved = card.classList.toggle('is-saved');
        button.setAttribute('aria-pressed', String(saved));
        if (talentFavCount) {
            talentFavCount.textContent = document.querySelectorAll(
                '[data-talent-card].is-saved',
            ).length;
        }
        showToast(saved ? 'Perfil guardado en favoritos' : 'Perfil quitado de favoritos');
        applyTalentFilters();
    });
});

/* ============================================================
   DASHBOARD — verificación / moderación
============================================================ */
document.querySelectorAll('[data-verify]').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('[data-verification-row]');
        const status = row?.querySelector('.status');
        if (status && row) {
            status.className = `status ${button.dataset.verify.toLowerCase()}`;
            status.textContent = button.dataset.verify;
        }
        showToast(
            button.dataset.verify === 'Verificada'
                ? 'Organización verificada'
                : 'Organización rechazada',
        );
    });
});

document.querySelectorAll('[data-toggle-status]').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('[data-user-row]');
        const status = row?.querySelector('.status');
        if (status) {
            const blocked = status.textContent.trim() === 'Activo';
            status.className = `status ${blocked ? 'bloqueado' : 'activo'}`;
            status.textContent = blocked ? 'Bloqueado' : 'Activo';
            button.textContent = blocked ? 'Reactivar' : 'Bloquear';
        }
        showToast('Registro actualizado');
    });
});

/* ============================================================
   DASHBOARD — oportunidades (ONG): filtros, buscador, duplicar
============================================================ */
const projectsNew = document.querySelector('[data-projects-new]');
const projectRowTemplate = document.querySelector('[data-project-row]');

const createDraftRow = (templateRow, title) => {
    if (!projectsNew || !templateRow) return;
    const total = document.querySelectorAll('[data-project-row]').length + projectsNew.children.length + 1;
    const row = templateRow.cloneNode(true);
    const heading = row.querySelector('b');
    if (heading) heading.textContent = title ?? `Nuevo reto ${total}`;
    const meta = row.querySelector('small');
    if (meta) meta.textContent = '0 candidatos · Creado hoy';
    const status = row.querySelector('.status');
    if (status) {
        status.className = 'status borrador';
        status.textContent = 'Borrador';
    }
    const progress = row.querySelector('[data-progress]');
    if (progress) progress.hidden = true;
    const metrics = row.querySelector('[data-metrics]');
    if (metrics) metrics.hidden = true;
    row.dataset.status = 'borrador';
    row.dataset.search = (title ?? `Nuevo reto ${total}`).toLowerCase();
    projectsNew.appendChild(row);
    applyProjectFilters();
};

document.querySelectorAll('[data-add-project]').forEach((button) => {
    button.addEventListener('click', () => {
        createDraftRow(projectRowTemplate);
        showToast('Borrador de proyecto creado');
    });
});

/* Duplicar oportunidad: clona el proyecto como borrador nuevo */
document.querySelectorAll('[data-duplicate-project]').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('[data-project-row]');
        if (!row) return;
        const heading = row.querySelector('b');
        createDraftRow(row, heading ? `Copia de ${heading.textContent}` : undefined);
        showToast('Oportunidad duplicada como borrador');
    });
});

/* Filtros por estado + buscador */
const projectFilterButtons = [...document.querySelectorAll('[data-project-filter]')];
const projectSearchInput = document.querySelector('[data-project-search]');
let activeProjectFilter = 'all';

function applyProjectFilters() {
    const query = projectSearchInput ? projectSearchInput.value.trim().toLowerCase() : '';
    document
        .querySelectorAll('[data-panel="projects"] [data-project-row]')
        .forEach((row) => {
            const matchesStatus =
                activeProjectFilter === 'all' || row.dataset.status === activeProjectFilter;
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            row.hidden = !(matchesStatus && matchesSearch);
        });
}

projectFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        activeProjectFilter = button.dataset.projectFilter;
        projectFilterButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
        applyProjectFilters();
    });
});
projectSearchInput?.addEventListener('input', applyProjectFilters);

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

/* ============================================================
   DASHBOARD ONG — métricas: rango de fechas, exportación y evaluación
============================================================ */
const impactPanel = document.querySelector('[data-panel="impact"]');

if (impactPanel) {
    const IMPACT_AREAS = ['Diseño', 'Tecnología', 'Comunicación', 'Datos'];
    const IMPACT_RANGES = {
        mes: {
            label: 'Último mes',
            hours: 31,
            savings: 'S/ 3.400',
            areas: [70, 84, 79, 72],
            period: 'Por semana',
            bars: [['Sem 1', 6], ['Sem 2', 7], ['Sem 3', 8], ['Sem 4', 10]],
        },
        trimestre: {
            label: 'Trimestre',
            hours: 96,
            savings: 'S/ 10.400',
            areas: [74, 80, 84, 66],
            period: 'Por mes',
            bars: [['Sep', 28], ['Oct', 32], ['Nov', 36]],
        },
        anio: {
            label: 'Año',
            hours: 312,
            savings: 'S/ 33.800',
            areas: [78, 72, 88, 61],
            period: 'Por mes',
            bars: [['Jun', 38], ['Jul', 44], ['Ago', 51], ['Sep', 57], ['Oct', 60], ['Nov', 62]],
        },
        historico: {
            label: 'Histórico',
            hours: 684,
            savings: 'S/ 74.000',
            areas: [82, 68, 91, 54],
            period: 'Por año',
            bars: [['2022', 96], ['2023', 148], ['2024', 187], ['2025', 253]],
        },
    };

    const rangeButtons = [...impactPanel.querySelectorAll('[data-impact-range]')];
    const hoursLabels = impactPanel.querySelectorAll('[data-impact-hours-label]');
    const savingsLabel = impactPanel.querySelector('[data-impact-savings]');
    const areaRows = [...impactPanel.querySelectorAll('[data-impact-area]')];
    const barsWrap = impactPanel.querySelector('[data-impact-bars]');
    const periodLabel = impactPanel.querySelector('[data-impact-period-label]');
    let impactRange = 'historico';

    const applyImpactRange = (range) => {
        const data = IMPACT_RANGES[range];
        if (!data) return;
        hoursLabels.forEach((el) => (el.textContent = data.hours));
        if (savingsLabel) savingsLabel.textContent = data.savings;
        areaRows.forEach((row, index) => {
            const value = data.areas[index];
            const bar = row.querySelector('i b');
            const label = row.querySelector('em');
            if (bar) bar.style.width = `${value}%`;
            if (label) label.textContent = `${value}%`;
        });
        if (periodLabel) periodLabel.textContent = data.period;
        if (barsWrap) {
            const max = Math.max(...data.bars.map(([, value]) => value));
            barsWrap.innerHTML = data.bars
                .map(([label, value]) => {
                    const height = Math.max(12, Math.round((value / max) * 110));
                    return `<div class="evolution-bar"><b>${value} h</b><i style="height: ${height}px"></i><span>${label}</span></div>`;
                })
                .join('');
        }
    };

    rangeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            impactRange = button.dataset.impactRange;
            rangeButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
            applyImpactRange(impactRange);
        });
    });
    applyImpactRange(impactRange);

    /* Exportar reporte de impacto: CSV descargable o diálogo de impresión (PDF) */
    const buildImpactCsv = (range) => {
        const data = IMPACT_RANGES[range];
        const rows = [
            ['Reporte de impacto', 'VoluntMatch'],
            ['Rango', data.label],
            ['Horas de voluntariado', data.hours],
            ['Ahorro estimado', data.savings],
            [],
            ['Area', '% de proyectos entregados'],
            ...IMPACT_AREAS.map((area, index) => [area, data.areas[index]]),
            [],
            ['Periodo', 'Horas aportadas'],
            ...data.bars,
        ];
        return rows.map((row) => (row.length ? row.join(',') : '')).join('\n');
    };

    impactPanel.querySelectorAll('[data-impact-export]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = IMPACT_RANGES[impactRange];
            if (button.dataset.impactExport === 'csv') {
                const blob = new Blob([`\uFEFF${buildImpactCsv(impactRange)}`], {
                    type: 'text/csv;charset=utf-8',
                });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `reporte-impacto-${impactRange}.csv`;
                link.click();
                URL.revokeObjectURL(url);
                showToast(`Reporte CSV (${data.label}) descargado`);
                return;
            }
            showToast('En el diálogo, elige "Guardar como PDF"');
            window.print();
        });
    });

    /* Evaluación de voluntarios: exige voluntario elegido */
    const reviewVolunteer = impactPanel.querySelector('[data-review-volunteer]');
    const reviewProject = impactPanel.querySelector('[data-review-project]');
    impactPanel.querySelector('[data-review-publish]')?.addEventListener('click', () => {
        if (!reviewVolunteer?.value) {
            showToast('Elige un voluntario para evaluar');
            return;
        }
        const project = reviewProject?.value ? ` en ${reviewProject.value}` : '';
        showToast(`Evaluación de ${reviewVolunteer.value}${project} publicada en el perfil`);
    });
}
