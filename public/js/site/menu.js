/*
 * VoluntMatch — Menú móvil
 */
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
