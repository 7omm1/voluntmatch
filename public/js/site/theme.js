/*
 * VoluntMatch — Tema claro/oscuro
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
