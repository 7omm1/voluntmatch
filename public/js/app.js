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
   DASHBOARD — buscador de talento
============================================================ */
const talentSearch = document.querySelector('[data-talent-search]');
if (talentSearch) {
    talentSearch.addEventListener('input', () => {
        const query = talentSearch.value.toLowerCase().trim();
        document.querySelectorAll('[data-talent-card]').forEach((card) => {
            const match = (card.dataset.search || '').includes(query);
            card.style.display = match ? '' : 'none';
        });
    });
}

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
