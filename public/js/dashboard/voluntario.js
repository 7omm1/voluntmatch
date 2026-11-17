/*
 * VoluntMatch — Panel del voluntario: baraja de matches horizontal,
 * sliders de habilidades y estrellas de valoración.
 */
/* ============================================================
   DASHBOARD — baraja de matches (solo izquierda / derecha)
============================================================ */
const swipeDeck = document.querySelector('[data-swipe-deck]');
if (swipeDeck) {
    const stage = swipeDeck.querySelector('[data-deck-stage]');
    const emptyState = swipeDeck.querySelector('[data-deck-empty]');
    const actions = swipeDeck.querySelector('.swipe-actions');
    const initialDeckHTML = stage.innerHTML;
    const SWIPE_OUT = 90;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const bindCard = (card) => {
        card.addEventListener('pointerdown', (event) => {
            if (!card.classList.contains('is-top')) return;
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            event.preventDefault();
            card.setPointerCapture(event.pointerId);
            card.classList.add('is-dragging');

            const startX = event.clientX;
            const like = card.querySelector('.stamp-like');
            const nope = card.querySelector('.stamp-nope');

            const onMove = (moveEvent) => {
                // Solo cuenta el eje horizontal: la tarjeta no sube ni baja.
                const dx = moveEvent.clientX - startX;
                card.style.transform = `translate(${dx}px, 0) rotate(${dx / 22}deg)`;
                if (like) like.style.opacity = Math.max(0, Math.min(1, dx / 80));
                if (nope) nope.style.opacity = Math.max(0, Math.min(1, -dx / 80));
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

        if (emptyState) emptyState.hidden = cards.length > 0;
        if (stage) stage.hidden = cards.length === 0;
        if (actions) actions.hidden = cards.length === 0;
        swipeDeck.classList.toggle('deck-done', cards.length === 0);
    };

    /* Envía la postulación real al servidor cuando la tarjeta cae a la derecha. */
    const postular = async (card) => {
        const url = card.dataset.postularUrl;
        if (!url) {
            showToast('No pudimos enviar tu postulación');
            return;
        }

        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify({}),
            });

            if (!respuesta.ok) throw new Error('request failed');

            const datos = await respuesta.json();
            showToast(datos.mensaje ?? 'Postulación enviada');
        } catch (error) {
            showToast('No pudimos guardar tu postulación. Inténtalo de nuevo.');
        }
    };

    const swipeCard = (card, direction, distance = SWIPE_OUT + 120) => {
        if (!card || !card.classList.contains('is-top')) return;
        card.classList.remove('is-top');
        card.style.transition = 'transform 0.45s cubic-bezier(0.3, 0, 0.7, 1), opacity 0.45s';
        // Salida estrictamente horizontal: nada de movimiento vertical.
        card.style.transform = `translate(${direction * distance * 1.6}px, 0) rotate(${direction * 22}deg)`;
        card.style.opacity = '0';
        card.style.pointerEvents = 'none';
        card.classList.add('is-out');

        if (direction > 0) {
            postular(card);
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
   DASHBOARD — sliders de habilidades
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
