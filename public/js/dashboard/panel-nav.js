/*
 * VoluntMatch — Navegación fluida del espacio del estudiante.
 *
 * Las secciones del panel son páginas reales con URL propia. Este
 * script intercepta los enlaces del menú lateral, trae la siguiente
 * sección y la intercambia sin recargar toda la página; la URL se
 * actualiza con pushState para que al refrescar se quede donde estaba.
 */
(() => {
    const shell = document.querySelector('.dashboard-shell');
    if (!shell) return;

    /* En móvil el menú del panel es un carrusel horizontal. Al cargar
       una sección, desplazamos el carrusel solo lo necesario para que la
       pestaña activa quede completamente visible. */
    const revealActiveNavLink = (smooth = false) => {
        const nav = shell.querySelector('.dashboard-sidebar nav');
        const active = nav?.querySelector('a.active');
        if (!nav || !active) return;
        if (nav.scrollWidth <= nav.clientWidth + 2) return;

        const margin = 18;
        const navRect = nav.getBoundingClientRect();
        const activeRect = active.getBoundingClientRect();
        const start = activeRect.left - navRect.left + nav.scrollLeft;
        const max = nav.scrollWidth - nav.clientWidth;
        let target = Math.max(0, Math.min(start - margin, max));

        if (Math.abs(target - nav.scrollLeft) < 1) return;

        nav.scrollTo({ left: target, behavior: smooth ? 'smooth' : 'auto' });
    };
    revealActiveNavLink();

    const funcionaSinRecarga = shell.classList.contains('dashboard-student');

    /* Paneles ONG y admin: salida suave y recarga normal, para que el
       cambio de modulo no se sienta como un salto seco. */
    if (!funcionaSinRecarga) {
        const main = shell.querySelector('.dashboard-main');
        let leaving = false;

        document.addEventListener('click', (event) => {
            const link = event.target.closest('.dashboard-sidebar nav a');
            if (!link || leaving) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target && link.target !== '_self') return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            if (url.href === window.location.href) return;

            event.preventDefault();
            leaving = true;
            main?.classList.add('is-leaving');
            shell.classList.add('panel-loading');
            window.setTimeout(() => {
                window.location.href = url.href;
            }, 150);
        });

        return;
    }

    const nav = shell.querySelector('.dashboard-sidebar nav');
    let main = shell.querySelector('.dashboard-main');
    if (!nav || !main || !window.fetch || !window.history?.pushState) return;

    const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));
    let navigating = false;

    const esDelPanel = (url) => /\/(mi-espacio|home)(\/|$)/.test(url.pathname);

    const swap = async (url, push) => {
        if (navigating || !esDelPanel(url)) {
            window.location.href = url.href;
            return;
        }

        navigating = true;
        shell.classList.add('panel-loading');
        main.classList.add('is-leaving');
        document.querySelectorAll('.project-modal-backdrop').forEach((modal) => {
            modal.hidden = true;
        });
        document.body.classList.remove('modal-open');

        try {
            const response = await fetch(url.href, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('No pudimos cargar la sección');

            const html = await response.text();
            const finalUrl = new URL(response.url || url.href, window.location.href);
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextMain = doc.querySelector('.dashboard-main');
            const nextNav = doc.querySelector('.dashboard-sidebar nav');

            if (!nextMain) throw new Error('La sesión cambió de página');

            await wait(140);

            main.replaceWith(nextMain);
            main = nextMain;
            if (nextNav) nav.innerHTML = nextNav.innerHTML;
            revealActiveNavLink(true);

            const modalSlot = document.querySelector('[data-panel-modals]');
            const nextModalSlot = doc.querySelector('[data-panel-modals]');
            if (modalSlot && nextModalSlot) modalSlot.innerHTML = nextModalSlot.innerHTML;

            document.title = doc.title;
            main.scrollTop = 0;
            main.classList.add('is-entering');
            window.setTimeout(() => main.classList.remove('is-entering'), 500);

            if (push) window.history.pushState({ vmPanel: true }, '', finalUrl.href);

            document.dispatchEvent(new CustomEvent('vm:panel-loaded'));
        } catch (error) {
            window.location.href = url.href;
        } finally {
            navigating = false;
            shell.classList.remove('panel-loading');
            main.classList.remove('is-leaving');
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link) return;
        if (link.hasAttribute('data-profile-open') || link.hasAttribute('download')) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (link.target && link.target !== '_self') return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (!esDelPanel(url)) return;
        if (url.hash && url.pathname === window.location.pathname) return;

        event.preventDefault();
        if (url.href === window.location.href) return;
        swap(url, true);
    });

    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        if (esDelPanel(url)) {
            swap(url, false);
        } else {
            window.location.reload();
        }
    });
})();
