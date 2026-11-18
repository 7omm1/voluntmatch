/*
 * VoluntMatch — Tema claro/oscuro
 */
/* ============================================================
   TEMA (modo claro / oscuro)
============================================================ */
// Delegado en documento para que siga funcionando cuando la
// navegacion del panel reemplaza el encabezado sin recargar.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');
    if (!button) return;

    const dark = document.documentElement.classList.toggle('dark');
    try {
        localStorage.setItem('voluntmatch-theme', dark ? 'dark' : 'light');
    } catch (e) {}
    showToast(dark ? 'Modo oscuro activado' : 'Modo claro activado');
});
