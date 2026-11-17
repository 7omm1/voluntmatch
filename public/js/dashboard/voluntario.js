/*
 * VoluntMatch — Panel del voluntario: baraja de matches,
 postulaciones, sliders de habilidades y estrellas de valoración
 */
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
