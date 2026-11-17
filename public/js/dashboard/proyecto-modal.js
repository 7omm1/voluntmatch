/*
 * VoluntMatch — Ficha de proyecto: modal del panel de la ONG
 */
/* ============================================================
   DASHBOARD — ficha de proyecto (modal de la ONG)
============================================================ */
const projectModal = document.querySelector('[data-project-modal]');
if (projectModal) {
    const projectField = (name) => projectModal.querySelector(`[data-project-${name}]`);
    const projectLink = projectModal.querySelector('[data-project-link]');
    const projectBase = projectModal.dataset.projectBase || '';

    const openProject = (trigger) => {
        const data = trigger.dataset;
        [
            ['title', data.title],
            ['org', data.org],
            ['cause', data.cause],
            ['skill', data.skill],
            ['time', data.time],
        ].forEach(([name, value]) => {
            const el = projectField(name);
            if (el && value) el.textContent = value;
        });
        if (projectLink && data.slug) projectLink.href = `${projectBase}${data.slug}`;
        projectModal.hidden = false;
        projectModal.querySelector('.project-detail-modal')?.scrollTo({ top: 0 });
    };

    const closeProject = () => {
        projectModal.hidden = true;
    };

    document.querySelectorAll('[data-project-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => openProject(trigger));
    });
    document.querySelectorAll('[data-project-close]').forEach((btn) => {
        btn.addEventListener('click', closeProject);
    });
    projectModal.addEventListener('click', (event) => {
        if (event.target === projectModal) closeProject();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !projectModal.hidden) closeProject();
    });
}
