/*
 * VoluntMatch — Panel del voluntario: baraja de matches, mapa de
 * fortalezas en vivo, postulaciones y ventana de perfil.
 *
 * Todo se inicializa desde initVoluntarioPanels() para que la
 * navegacion sin recarga (panel-nav.js) pueda reconstruir la pagina.
 */
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const dec = (value) => {
        if (!value) return [];
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    };

    /* ============================================================
       MODALES — utilidades compartidas
    ============================================================ */
    const openModal = (modal) => {
        if (!modal) return;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        modal.querySelector('.modal-close, input, button, a')?.focus?.({ preventScroll: true });
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.hidden = true;
        if (!document.querySelector('.project-modal-backdrop:not([hidden])')) {
            document.body.classList.remove('modal-open');
        }
    };

    const closeAllModals = () => {
        document.querySelectorAll('.project-modal-backdrop').forEach((modal) => {
            modal.hidden = true;
        });
        document.body.classList.remove('modal-open');
    };

    /* ============================================================
       MAPA DE FORTALEZAS EN VIVO
    ============================================================ */
    const initSkillRadar = () => {
        const form = document.querySelector('[data-skills-form]');
        const radar = document.querySelector('[data-radar]');
        if (!form || !radar) return;
        if (form.dataset.radarBound === '1') return;
        form.dataset.radarBound = '1';

        const shape = radar.querySelector('[data-radar-shape]');
        const pointsBox = radar.querySelector('[data-radar-points]');
        const labels = [...radar.querySelectorAll('[data-radar-label]')];
        const ranges = [...form.querySelectorAll('[data-skill-range]')];
        const base = Number(form.dataset.profileBase || 0);
        const total = Number(form.dataset.profileTotal || 100);
        const weight = Number(form.dataset.skillsWeight || 0);

        const update = () => {
            const values = ranges.map((range) => Number(range.value));
            const coords = values.map((value, index) => {
                const angle = ((-90 + index * 72) * Math.PI) / 180;
                const radius = (value / 100) * 42;
                return [50 + radius * Math.cos(angle), 50 + radius * Math.sin(angle)];
            });

            if (shape) {
                shape.setAttribute('points', coords.map(([x, y]) => `${x.toFixed(2)},${y.toFixed(2)}`).join(' '));
            }

            if (pointsBox) {
                while (pointsBox.children.length < coords.length) {
                    pointsBox.appendChild(document.createElementNS('http://www.w3.org/2000/svg', 'circle'));
                }
                [...pointsBox.children].forEach((circle, index) => {
                    const [x, y] = coords[index];
                    circle.setAttribute('cx', x.toFixed(2));
                    circle.setAttribute('cy', y.toFixed(2));
                    circle.setAttribute('r', '1.9');
                });
            }

            ranges.forEach((range) => {
                const label = range.closest('label')?.querySelector('[data-range-value]');
                if (label) label.textContent = `${range.value}%`;
            });

            const media = values.length ? values.reduce((sum, value) => sum + value, 0) / values.length : 0;
            const porcentaje = total > 0
                ? Math.round(((base + weight * (media / 100)) / total) * 100)
                : 0;

            document.querySelectorAll('[data-profile-percent]').forEach((el) => {
                el.textContent = `${porcentaje}%`;
            });

            const ordenadas = ranges
                .map((range, index) => ({ index, value: Number(range.value) }))
                .sort((a, b) => b.value - a.value)
                .slice(0, 2)
                .map((item) => item.index);
            labels.forEach((label, index) => {
                label.classList.toggle('is-highlighted', ordenadas.includes(index));
            });
            document.querySelectorAll('[data-highlight-skill]').forEach((el, index) => {
                const label = labels[ordenadas[index] ?? 0];
                if (label) el.textContent = label.textContent.toLowerCase();
            });
        };

        ranges.forEach((range) => range.addEventListener('input', update));
        update();
    };

    /* ============================================================
       BARAJAS DE MATCHES + PANEL LATERAL DINÁMICO
    ============================================================ */
    const initSwipeDeck = () => {
        const swipeDeck = document.querySelector('[data-swipe-deck]');
        if (!swipeDeck || swipeDeck.dataset.deckBound === '1') return;
        swipeDeck.dataset.deckBound = '1';

        const stage = swipeDeck.querySelector('[data-deck-stage]');
        const emptyState = swipeDeck.querySelector('[data-deck-empty]');
        const actions = swipeDeck.querySelector('.swipe-actions');
        const initialDeckHTML = stage.innerHTML;
        const SWIPE_OUT = 90;
        const side = document.querySelector('[data-match-side]');
        let pendingCard = null;

        const setText = (selector, value) => {
            const el = side?.querySelector(selector);
            if (el && value !== undefined && value !== null) el.textContent = value;
        };

        const renderReasons = (container, reasons) => {
            if (!container) return;
            container.innerHTML = '';
            (reasons.length ? reasons : ['Tu perfil encaja con este reto.']).forEach((reason) => {
                const row = document.createElement('div');
                row.className = 'match-reason';
                const dot = document.createElement('span');
                const text = document.createElement('p');
                text.textContent = reason;
                row.append(dot, text);
                container.appendChild(row);
            });
        };

        const updateSide = (card) => {
            if (!side) return;
            const empty = side.querySelector('[data-match-side-empty]');
            const content = [
                side.querySelector('.match-side-card-head'),
                side.querySelector('[data-match-summary]'),
                side.querySelector('[data-match-reasons]'),
                side.querySelector('.match-compat'),
                side.querySelector('.match-tip'),
            ].filter(Boolean);

            if (!card) {
                content.forEach((el) => (el.hidden = true));
                if (empty) empty.hidden = false;
                return;
            }

            content.forEach((el) => (el.hidden = false));
            if (empty) empty.hidden = true;

            setText('[data-match-percent]', `${card.dataset.percent || 80}%`);
            setText('[data-match-title]', card.dataset.title || 'Tu próximo reto');
            setText('[data-match-org]', card.dataset.org || 'ONG verificada');
            setText('[data-match-summary]', card.dataset.summary || '');
            setText('[data-match-skill]', card.dataset.skill || 'Tu talento');
            setText('[data-match-time]', card.dataset.time || 'A tu ritmo');
            setText('[data-match-cause]', card.dataset.cause || 'La que te mueve');
            renderReasons(side.querySelector('[data-match-reasons]'), dec(card.dataset.reasons));

            side.classList.remove('is-refreshing');
            void side.offsetWidth;
            side.classList.add('is-refreshing');
        };

        /* Ventana de detalle del match: permite postularse desde la tarjeta. */
        const fillMatchModal = (card) => {
            const modal = document.querySelector('[data-match-modal]');
            if (!modal || !card) return;
            const field = (name) => modal.querySelector(`[data-match-modal-${name}]`);
            const set = (name, value) => {
                const el = field(name);
                if (el) el.textContent = value;
            };

            set('title', card.dataset.title || 'Oportunidad');
            set('org', card.dataset.org || 'ONG verificada');
            set('org-line', card.dataset.org || 'ONG verificada');
            set('cause', card.dataset.cause || 'Causa social');
            set('summary', card.dataset.summary || '');
            set('skill', card.dataset.skill || 'Tu talento');
            set('percent', `${card.dataset.percent || 80}% match`);
            set('learn', card.dataset.learn || 'Experiencia real y certificado digital.');
            renderReasons(modal.querySelector('[data-match-modal-reasons]'), dec(card.dataset.reasons));

            const image = modal.querySelector('[data-match-modal-image]');
            if (image) {
                if (card.dataset.image) {
                    image.src = card.dataset.image;
                    image.alt = card.dataset.title || 'Oportunidad';
                    image.style.display = '';
                } else {
                    image.removeAttribute('src');
                    image.style.display = 'none';
                }
            }

            const meta = modal.querySelector('[data-match-modal-meta]');
            if (meta) {
                meta.innerHTML = '';
                [card.dataset.time, card.dataset.modality, card.dataset.cause].forEach((value) => {
                    if (!value) return;
                    const chip = document.createElement('span');
                    chip.textContent = value;
                    meta.appendChild(chip);
                });
            }

            const link = modal.querySelector('[data-match-modal-link]');
            if (link && card.dataset.detailUrl) link.href = card.dataset.detailUrl;
        };

        const openMatch = (card) => {
            if (!card || !card.classList.contains('is-top')) return;
            pendingCard = card;
            fillMatchModal(card);
            openModal(document.querySelector('[data-match-modal]'));
        };

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

        const cardsInDeck = () => [...stage.querySelectorAll('[data-swipe-card]:not(.is-out)')];

        const restack = () => {
            const cards = cardsInDeck();
            cards.forEach((card, i) => {
                const depth = Math.min(i, 2);
                card.classList.toggle('is-top', i === 0);
                card.classList.toggle('is-queued', i > 2);
                card.classList.remove('is-out');
                card.style.zIndex = cards.length - i;
                card.style.setProperty('--depth', depth);
                if (!card.classList.contains('is-dragging')) {
                    card.style.transform = '';
                    card.style.transition = '';
                    card.style.opacity = '';
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
            updateSide(cards[0] ?? null);
        };

        const swipeCard = (card, direction, distance = SWIPE_OUT + 120) => {
            if (!card || !card.classList.contains('is-top')) return;
            card.classList.remove('is-top');
            card.style.transition = 'transform 0.45s cubic-bezier(0.3, 0, 0.7, 1), opacity 0.45s';
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

        const bindCard = (card) => {
            if (card.dataset.cardBound === '1') return;
            card.dataset.cardBound = '1';
            let dragged = false;

            card.addEventListener('pointerdown', (event) => {
                if (!card.classList.contains('is-top')) return;
                if (event.pointerType === 'mouse' && event.button !== 0) return;
                if (event.target.closest('a, button')) return;

                /* En ratón evitamos seleccionar texto; en táctil dejamos que
                   el navegador gestione el scroll vertical de la página. */
                if (event.pointerType === 'mouse') event.preventDefault();

                const startX = event.clientX;
                const startY = event.clientY;
                const like = card.querySelector('.stamp-like');
                const nope = card.querySelector('.stamp-nope');
                let currentX = 0;
                let frame = null;
                let dragging = false;
                let cancelled = false;

                dragged = false;
                card.setPointerCapture?.(event.pointerId);

                const paint = () => {
                    frame = null;
                    card.style.transform = `translate3d(${currentX}px, 0, 0) rotate(${currentX / 22}deg)`;
                    if (like) like.style.opacity = Math.max(0, Math.min(1, currentX / 80));
                    if (nope) nope.style.opacity = Math.max(0, Math.min(1, -currentX / 80));
                };

                const finish = () => {
                    card.removeEventListener('pointermove', onMove);
                    card.removeEventListener('pointerup', onUp);
                    card.removeEventListener('pointercancel', onUp);
                    card.removeEventListener('lostpointercapture', onUp);
                    if (frame) cancelAnimationFrame(frame);
                    frame = null;
                    card.classList.remove('is-dragging');
                    if (card.hasPointerCapture?.(event.pointerId)) {
                        card.releasePointerCapture(event.pointerId);
                    }
                };

                const reset = () => {
                    card.style.transform = '';
                    if (like) like.style.opacity = 0;
                    if (nope) nope.style.opacity = 0;
                };

                const onMove = (moveEvent) => {
                    if (cancelled) return;
                    const dx = moveEvent.clientX - startX;
                    const dy = moveEvent.clientY - startY;

                    if (!dragging) {
                        if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
                        /* Gesto vertical: devolvemos el control al scroll. */
                        if (Math.abs(dy) > Math.abs(dx)) {
                            cancelled = true;
                            finish();
                            reset();
                            return;
                        }
                        dragging = true;
                        dragged = true;
                        card.classList.add('is-dragging');
                    }

                    currentX = dx;
                    if (!frame) frame = requestAnimationFrame(paint);
                };

                const onUp = (upEvent) => {
                    if (cancelled) return;
                    const dx = upEvent.clientX - startX;
                    finish();

                    if (dragging && Math.abs(dx) > SWIPE_OUT) {
                        swipeCard(card, dx > 0 ? 1 : -1, Math.abs(dx));
                    } else {
                        reset();
                    }
                };

                card.addEventListener('pointermove', onMove);
                card.addEventListener('pointerup', onUp);
                card.addEventListener('pointercancel', onUp);
                card.addEventListener('lostpointercapture', onUp);
            });

            /* Toda la tarjeta abre su ventana de detalle. */
            card.addEventListener('click', (event) => {
                if (dragged) {
                    dragged = false;
                    return;
                }
                if (event.target.closest('a, [data-match-open]')) return;
                if (card.classList.contains('is-top')) openMatch(card);
            });

            card.querySelector('[data-match-open]')?.addEventListener('click', (event) => {
                event.preventDefault();
                openMatch(card);
            });
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

        /* Botón "Postularme ahora" de la ventana de detalle. */
        const matchModal = document.querySelector('[data-match-modal]');
        matchModal?.querySelector('[data-match-modal-apply]')?.addEventListener('click', () => {
            const card = pendingCard && pendingCard.isConnected ? pendingCard : cardsInDeck()[0];
            closeModal(matchModal);
            if (card) swipeCard(card, 1);
        });
    };

    /* ============================================================
       POSTULACIONES — ventana con el detalle y estado
    ============================================================ */
    const initApplicationModal = () => {
        const modal = document.querySelector('[data-application-modal]');
        if (!modal) return;

        const set = (name, value) => {
            const el = modal.querySelector(`[data-application-${name}]`);
            if (el) el.textContent = value ?? '';
        };

        const fill = (data) => {
            set('title', data.title || 'Oportunidad');
            set('org', data.org || 'ONG verificada');
            set('date', data.dateFull || data.date || 'Recién enviada');
            set('percent', `${data.percent || 80}%`);
            set('time', data.time || 'A tu ritmo');
            set('modality', data.modality || 'Flexible');
            set('skill', data.skill || 'Tu talento');
            set('cause', data.cause || 'Impacto social');

            const status = modal.querySelector('[data-application-status]');
            if (status) {
                status.textContent = data.status || 'En revisión';
                status.className = `status ${data.statusClass || 'en-revisión'}`;
            }

            const noteWrap = modal.querySelector('[data-application-note-wrap]');
            if (noteWrap) {
                noteWrap.hidden = !data.note;
                set('note', data.note || '');
            }

            const link = modal.querySelector('[data-application-link]');
            if (link && data.url) link.href = data.url;

            const timeline = modal.querySelector('[data-application-timeline]');
            if (timeline) {
                const orden = { 'en revision': 1, 'en entrevista': 2, aceptado: 3, rechazado: 1 };
                const actual = orden[data.estado] ?? 1;
                timeline.classList.toggle('is-rejected', data.estado === 'rechazado');
                [...timeline.querySelectorAll('[data-step]')].forEach((step, index) => {
                    step.classList.toggle('is-done', index < actual);
                    step.classList.toggle('is-current', index === actual);
                });
            }

            const next = {
                'en revision': 'La ONG revisará tu postulación y te contactará por mensajes.',
                'en entrevista': 'Estás en entrevista: revisa tus mensajes para coordinar el día y la hora.',
                aceptado: '¡Fuiste aceptado! Coordina con la ONG el inicio de tu proyecto.',
                rechazado: 'La ONG eligió otro perfil esta vez. Sigue explorando matches.',
            };
            set('next', next[data.estado] || next['en revision']);

            const missing = modal.querySelector('[data-application-missing]');
            if (missing) {
                missing.textContent = data.profileIncomplete === '1'
                    ? `Te falta: ${data.missing || 'completar tus datos'}.`
                    : 'Tu perfil está listo para que las ONG te contacten.';
            }

            const profileBox = modal.querySelector('.application-profile-box');
            if (profileBox) profileBox.classList.toggle('is-complete', data.profileIncomplete !== '1');
        };

        document.querySelectorAll('[data-application-open]').forEach((row) => {
            if (row.dataset.applicationBound === '1') return;
            row.dataset.applicationBound = '1';
            row.addEventListener('click', () => {
                fill(row.dataset);
                openModal(modal);
            });
        });
    };

    /* ============================================================
       PERFIL — ventana para completar el perfil
    ============================================================ */
    const initProfileModal = () => {
        const modal = document.querySelector('[data-profile-modal]');
        const form = modal?.querySelector('[data-profile-form]');
        if (!modal || !form || form.dataset.profileBound === '1') return;
        form.dataset.profileBound = '1';

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('[type="submit"]');
            let errorBox = form.querySelector('[data-profile-error]');
            if (!errorBox) {
                errorBox = document.createElement('div');
                errorBox.className = 'form-error';
                errorBox.setAttribute('data-profile-error', '');
                errorBox.hidden = true;
                form.prepend(errorBox);
            }
            errorBox.hidden = true;
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },
                    body: new FormData(form),
                });

                const datos = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const errores = datos.errors ? Object.values(datos.errors).flat() : [];
                    throw new Error(errores[0] || datos.message || 'Revisa los datos del perfil.');
                }

                if (!datos.ok) {
                    throw new Error('Tu sesión expiró. Vuelve a iniciar sesión.');
                }

                document.querySelectorAll('[data-profile-percent]').forEach((el) => {
                    el.textContent = `${datos.porcentaje ?? el.textContent.replace('%', '')}%`;
                });
                document.querySelectorAll('[data-application-open]').forEach((row) => {
                    row.dataset.profileIncomplete = Number(datos.porcentaje ?? 100) < 100 ? '1' : '0';
                    if (Array.isArray(datos.faltantes)) {
                        row.dataset.missing = datos.faltantes.slice(0, 3).join(', ');
                    }
                });
                showToast(datos.mensaje ?? 'Perfil actualizado');
                closeModal(modal);
            } catch (error) {
                errorBox.textContent = error.message || 'No pudimos guardar tu perfil.';
                errorBox.hidden = false;
            } finally {
                if (button) button.disabled = false;
            }
        });
    };

    /* ============================================================
       ESTRELLAS DE VALORACIÓN
    ============================================================ */
    const initStars = () => {
        document.querySelectorAll('.stars').forEach((group) => {
            if (group.dataset.starsBound === '1') return;
            group.dataset.starsBound = '1';
            const stars = [...group.querySelectorAll('[data-star]')];
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    stars.forEach((s, i) => s.classList.toggle('active', i <= index));
                });
            });
        });
    };

    /* ============================================================
       CHAT DEL ESTUDIANTE
    ============================================================ */
    const initChat = () => {
        document.querySelectorAll('[data-chat-form]').forEach((form) => {
            if (form.dataset.chatBound === '1') return;
            form.dataset.chatBound = '1';
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
                message.innerHTML = '<span></span><small></small>';
                message.querySelector('span').textContent = text;
                message.querySelector('small').textContent = time;
                list.appendChild(message);
                input.value = '';
                list.scrollTop = list.scrollHeight;
            });
        });
    };

    /* ============================================================
       SOPORTE — ventana compartida por todos los paneles
    ============================================================ */
    const initSupportModal = () => {
        const modal = document.querySelector('[data-support-modal]');
        const form = modal?.querySelector('[data-support-form]');
        if (!modal || !form || form.dataset.supportBound === '1') return;
        form.dataset.supportBound = '1';

        const intro = modal.querySelector('[data-support-intro]');
        const success = modal.querySelector('[data-support-success]');
        const errorBox = form.querySelector('[data-support-error]');
        const button = form.querySelector('[type="submit"]');

        const reset = () => {
            form.reset();
            form.hidden = false;
            if (intro) intro.hidden = false;
            if (success) success.hidden = true;
            if (errorBox) errorBox.hidden = true;
            window.setTimeout(() => form.querySelector('input, select, textarea')?.focus(), 80);
        };

        modal.querySelector('[data-support-reset]')?.addEventListener('click', reset);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (errorBox) errorBox.hidden = true;
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },
                    body: new FormData(form),
                });

                const datos = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const errores = datos.errors ? Object.values(datos.errors).flat() : [];
                    throw new Error(errores[0] || datos.message || 'Revisa los datos del formulario.');
                }

                const texto = modal.querySelector('[data-support-success-text]');
                if (texto && datos.mensaje) texto.textContent = datos.mensaje;
                if (intro) intro.hidden = true;
                if (success) success.hidden = false;
                form.reset();
                showToast(datos.mensaje ?? 'Caso enviado a soporte');
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = error.message || 'No pudimos enviar tu caso. Inténtalo de nuevo.';
                    errorBox.hidden = false;
                }
            } finally {
                if (button) button.disabled = false;
            }
        });
    };

    /* ============================================================
       ARRANQUE Y REINICIALIZACIÓN TRAS NAVEGAR SIN RECARGA
    ============================================================ */
    const initAll = () => {
        initSkillRadar();
        initSwipeDeck();
        initApplicationModal();
        initProfileModal();
        initSupportModal();
        initStars();
        initChat();
    };

    // Los modales delegados se registran una sola vez por carga.
    document.addEventListener('click', (event) => {
        const supportTrigger = event.target.closest('[data-support-open]');
        if (supportTrigger) {
            event.preventDefault();
            openModal(document.querySelector('[data-support-modal]'));
            return;
        }

        const supportClose = event.target.closest('[data-support-close]');
        if (supportClose) {
            closeModal(document.querySelector('[data-support-modal]'));
            return;
        }

        const profileTrigger = event.target.closest('[data-profile-open]');
        if (profileTrigger) {
            event.preventDefault();
            const applicationModal = document.querySelector('[data-application-modal]');
            const matchModal = document.querySelector('[data-match-modal]');
            if (applicationModal && !applicationModal.hidden) closeModal(applicationModal);
            if (matchModal && !matchModal.hidden) closeModal(matchModal);
            openModal(document.querySelector('[data-profile-modal]'));
            return;
        }

        const applicationClose = event.target.closest('[data-application-close]');
        if (applicationClose) {
            closeModal(document.querySelector('[data-application-modal]'));
            return;
        }

        const matchClose = event.target.closest('[data-match-close]');
        if (matchClose) {
            closeModal(document.querySelector('[data-match-modal]'));
            return;
        }

        const profileClose = event.target.closest('[data-profile-close]');
        if (profileClose) {
            closeModal(document.querySelector('[data-profile-modal]'));
        }
    });

    // Clic en el fondo oscuro cierra la ventana correspondiente.
    document.addEventListener('click', (event) => {
        if (event.target.matches('.project-modal-backdrop')) {
            closeModal(event.target);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const abierto = document.querySelector('.project-modal-backdrop:not([hidden])');
        if (abierto) closeModal(abierto);
    });

    document.addEventListener('DOMContentLoaded', initAll);
    document.addEventListener('vm:panel-loaded', initAll);

    // Si el script se carga después del DOMContentLoaded.
    if (document.readyState !== 'loading') initAll();
})();
