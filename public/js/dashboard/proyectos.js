/*
 * VoluntMatch — Oportunidades de la ONG: filtros, buscador y duplicar
 */
/* ============================================================
   DASHBOARD — oportunidades (ONG): filtros, buscador, duplicar
============================================================ */
const projectsNew = document.querySelector('[data-projects-new]');
const projectRowTemplate = document.querySelector('[data-project-row]');

const createDraftRow = (templateRow, title) => {
    if (!projectsNew || !templateRow) return;
    const total = document.querySelectorAll('[data-project-row]').length + projectsNew.children.length + 1;
    const row = templateRow.cloneNode(true);
    const heading = row.querySelector('b');
    if (heading) heading.textContent = title ?? `Nuevo reto ${total}`;
    const meta = row.querySelector('small');
    if (meta) meta.textContent = '0 candidatos · Creado hoy';
    const status = row.querySelector('.status');
    if (status) {
        status.className = 'status borrador';
        status.textContent = 'Borrador';
    }
    const progress = row.querySelector('[data-progress]');
    if (progress) progress.hidden = true;
    const metrics = row.querySelector('[data-metrics]');
    if (metrics) metrics.hidden = true;
    row.dataset.status = 'borrador';
    row.dataset.search = (title ?? `Nuevo reto ${total}`).toLowerCase();
    projectsNew.appendChild(row);
    applyProjectFilters();
};

document.querySelectorAll('[data-add-project]').forEach((button) => {
    button.addEventListener('click', () => {
        createDraftRow(projectRowTemplate);
        showToast('Borrador de proyecto creado');
    });
});

/* Duplicar oportunidad: clona el proyecto como borrador nuevo */
document.querySelectorAll('[data-duplicate-project]').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('[data-project-row]');
        if (!row) return;
        const heading = row.querySelector('b');
        createDraftRow(row, heading ? `Copia de ${heading.textContent}` : undefined);
        showToast('Oportunidad duplicada como borrador');
    });
});

/* Filtros por estado + buscador */
const projectFilterButtons = [...document.querySelectorAll('[data-project-filter]')];
const projectSearchInput = document.querySelector('[data-project-search]');
let activeProjectFilter = 'all';

function applyProjectFilters() {
    const query = projectSearchInput ? projectSearchInput.value.trim().toLowerCase() : '';
    document
        .querySelectorAll('[data-panel="projects"] [data-project-row]')
        .forEach((row) => {
            const matchesStatus =
                activeProjectFilter === 'all' || row.dataset.status === activeProjectFilter;
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            row.hidden = !(matchesStatus && matchesSearch);
        });
}

projectFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        activeProjectFilter = button.dataset.projectFilter;
        projectFilterButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
        applyProjectFilters();
    });
});
projectSearchInput?.addEventListener('input', applyProjectFilters);
