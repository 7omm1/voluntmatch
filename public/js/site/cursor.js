/*
 * VoluntMatch — Brillo del cursor
 */
/* ============================================================
   BRILLO DEL CURSOR
============================================================ */
const cursorGlow = document.querySelector('[data-cursor-glow]');
if (cursorGlow) {
    window.addEventListener('mousemove', (event) => {
        cursorGlow.style.transform = `translate3d(${event.clientX - 170}px, ${event.clientY - 170}px, 0)`;
    });
}
