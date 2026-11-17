/*
 * VoluntMatch — Ventana de registro de ONG
 *
 * Cualquier enlace o botón con [data-ngo-modal-open] abre el formulario
 * sobre la página actual. Si JavaScript no está disponible, el enlace
 * conserva su href a /registro-organizacion.
 */
const ngoModal = document.querySelector('[data-ngo-modal]');

if (ngoModal) {
    const card = ngoModal.querySelector('.ngo-modal');
    const shell = document.querySelector('.app-shell');
    let lastFocused = null;

    const focusables = () =>
        [
            ...card.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), summary, [tabindex]:not([tabindex="-1"])'
            ),
        ].filter((el) => el.offsetParent !== null);

    const open = (trigger) => {
        if (!ngoModal.hidden) return;

        lastFocused = trigger || document.activeElement;
        ngoModal.hidden = false;
        document.body.classList.add('modal-open');
        shell?.setAttribute('aria-hidden', 'true');

        // Si se abrió desde el menú móvil, lo cerramos para no dejarlo
        // abierto detrás de la ventana.
        const mobileNav = document.querySelector('[data-mobile-nav]');
        const mobileToggle = document.querySelector('[data-mobile-toggle]');
        mobileNav?.classList.remove('open');
        mobileToggle?.classList.remove('open');
        mobileToggle?.setAttribute('aria-label', 'Abrir menú');

        requestAnimationFrame(() => {
            // En móvil enfocamos el botón de cerrar para no desplegar el
            // teclado automáticamente al abrir la ventana.
            const isSmall = window.matchMedia('(max-width: 640px)').matches;
            const first = isSmall
                ? card.querySelector('[data-ngo-modal-close]')
                : card.querySelector('.form-step:not([hidden]) input:not([type="hidden"])') ||
                  card.querySelector('[data-ngo-modal-close]');
            first?.focus({ preventScroll: true });
        });
    };

    const close = () => {
        if (ngoModal.hidden) return;

        ngoModal.hidden = true;
        document.body.classList.remove('modal-open');
        shell?.removeAttribute('aria-hidden');
        lastFocused?.focus?.({ preventScroll: true });
    };

    document.querySelectorAll('[data-ngo-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            open(trigger);
        });
    });

    ngoModal.querySelectorAll('[data-ngo-modal-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    ngoModal.addEventListener('click', (event) => {
        if (event.target === ngoModal) close();
    });

    document.addEventListener('keydown', (event) => {
        if (ngoModal.hidden) return;

        if (event.key === 'Escape') {
            close();
            return;
        }

        if (event.key !== 'Tab') return;

        const items = focusables();
        if (!items.length) return;

        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    if (ngoModal.dataset.autoopen === '1' || window.location.hash === '#registro-ong') {
        open();
    }
}

/* Mostrar u ocultar contraseñas de los formularios de registro. */
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.parentElement?.querySelector('input');
        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Ocultar' : 'Ver';
        button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
});
