/*
 * VoluntMatch — Navegación entre secciones del panel
 */
/* ============================================================
   DASHBOARD — navegación entre secciones
============================================================ */
const dashPanels = [...document.querySelectorAll('[data-panel]')];
const dashButtons = [...document.querySelectorAll('[data-section]')];
const dashHeading = document.querySelector('[data-dash-heading]');
const dashTopLabel = document.querySelector('.dashboard-top p');

function showSection(key) {
    const panel = dashPanels.find((el) => el.dataset.panel === key);
    if (!panel) return;
    dashPanels.forEach((el) => (el.hidden = el !== panel));
    dashButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.section === key));
    if (dashHeading && panel.dataset.heading) {
        dashHeading.textContent = panel.dataset.heading;
    }
    if (dashTopLabel) {
        const zone = dashTopLabel.textContent.includes('CENTRO DE CONTROL')
            ? 'CENTRO DE CONTROL'
            : 'ESPACIO VOLUNTMATCH';
        dashTopLabel.textContent = `${zone} · ${panel.dataset.heading.toUpperCase()}`;
    }
    document.querySelector('.dashboard-main')?.scrollTo({ top: 0 });
}

dashButtons.forEach((btn) => {
    btn.addEventListener('click', () => showSection(btn.dataset.section));
});
document.querySelectorAll('[data-goto]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const key = btn.dataset.goto;
        const navBtn = dashButtons.find((el) => el.dataset.section === key);
        if (navBtn) showSection(key);
        else showToast('Muy pronto disponible');
    });
});
