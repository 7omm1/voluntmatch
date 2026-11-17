/*
 * VoluntMatch — Animación de aparición al hacer scroll
 */
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
