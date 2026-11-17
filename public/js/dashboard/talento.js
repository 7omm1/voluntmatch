/*
 * VoluntMatch — Buscador de talento: filtros y favoritos
 */
/* ============================================================
   DASHBOARD — buscador, filtros y favoritos de talento
============================================================ */
const talentSearch = document.querySelector('[data-talent-search]');
const talentFilterButtons = [...document.querySelectorAll('[data-talent-filter]')];
const talentCards = [...document.querySelectorAll('[data-talent-card]')];
const talentFavCount = document.querySelector('[data-fav-count]');
const talentEmpty = document.querySelector('[data-talent-empty]');
const talentFilters = { career: null, skill: null, hours: null, match: null, fav: false };

const talentHoursBucket = (hours) => (hours <= 5 ? 'low' : hours <= 10 ? 'mid' : 'high');

function applyTalentFilters() {
    const query = talentSearch ? talentSearch.value.toLowerCase().trim() : '';
    let visible = 0;
    talentCards.forEach((card) => {
        const ds = card.dataset;
        const matchQuery = !query || (ds.search || '').includes(query);
        const matchCareer = !talentFilters.career || ds.career === talentFilters.career;
        const matchSkill =
            !talentFilters.skill || (ds.skills || '').includes(talentFilters.skill.toLowerCase());
        const matchHours =
            !talentFilters.hours || talentHoursBucket(Number(ds.hours)) === talentFilters.hours;
        const matchMin = !talentFilters.match || Number(ds.match) >= Number(talentFilters.match);
        const matchFav = !talentFilters.fav || card.classList.contains('is-saved');
        const visibleCard =
            matchQuery && matchCareer && matchSkill && matchHours && matchMin && matchFav;
        card.style.display = visibleCard ? '' : 'none';
        if (visibleCard) visible += 1;
    });
    if (talentEmpty) talentEmpty.hidden = visible > 0;
}

if (talentSearch) {
    talentSearch.addEventListener('input', applyTalentFilters);
}

talentFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const type = button.dataset.talentFilter;
        if (type === 'fav') {
            talentFilters.fav = !talentFilters.fav;
            button.classList.toggle('active', talentFilters.fav);
        } else {
            const isActive = button.classList.contains('active');
            talentFilterButtons.forEach((btn) => {
                if (btn.dataset.talentFilter === type) btn.classList.remove('active');
            });
            talentFilters[type] = isActive ? null : button.dataset.filterValue;
            if (!isActive) button.classList.add('active');
        }
        applyTalentFilters();
    });
});

/* Guardar en favoritos (shortlist) */
document.querySelectorAll('[data-talent-save]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('[data-talent-card]');
        if (!card) return;
        const saved = card.classList.toggle('is-saved');
        button.setAttribute('aria-pressed', String(saved));
        if (talentFavCount) {
            talentFavCount.textContent = document.querySelectorAll(
                '[data-talent-card].is-saved',
            ).length;
        }
        showToast(saved ? 'Perfil guardado en favoritos' : 'Perfil quitado de favoritos');
        applyTalentFilters();
    });
});
