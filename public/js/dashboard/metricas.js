/*
 * VoluntMatch — Métricas de la ONG: rango de fechas, exportación y evaluación
 */
/* ============================================================
   DASHBOARD ONG — métricas: rango de fechas, exportación y evaluación
============================================================ */
const impactPanel = document.querySelector('[data-panel="impact"]');

if (impactPanel) {
    const IMPACT_AREAS = ['Diseño', 'Tecnología', 'Comunicación', 'Datos'];
    const IMPACT_RANGES = {
        mes: {
            label: 'Último mes',
            hours: 31,
            savings: 'S/ 3.400',
            areas: [70, 84, 79, 72],
            period: 'Por semana',
            bars: [['Sem 1', 6], ['Sem 2', 7], ['Sem 3', 8], ['Sem 4', 10]],
        },
        trimestre: {
            label: 'Trimestre',
            hours: 96,
            savings: 'S/ 10.400',
            areas: [74, 80, 84, 66],
            period: 'Por mes',
            bars: [['Sep', 28], ['Oct', 32], ['Nov', 36]],
        },
        anio: {
            label: 'Año',
            hours: 312,
            savings: 'S/ 33.800',
            areas: [78, 72, 88, 61],
            period: 'Por mes',
            bars: [['Jun', 38], ['Jul', 44], ['Ago', 51], ['Sep', 57], ['Oct', 60], ['Nov', 62]],
        },
        historico: {
            label: 'Histórico',
            hours: 684,
            savings: 'S/ 74.000',
            areas: [82, 68, 91, 54],
            period: 'Por año',
            bars: [['2022', 96], ['2023', 148], ['2024', 187], ['2025', 253]],
        },
    };

    const rangeButtons = [...impactPanel.querySelectorAll('[data-impact-range]')];
    const hoursLabels = impactPanel.querySelectorAll('[data-impact-hours-label]');
    const savingsLabel = impactPanel.querySelector('[data-impact-savings]');
    const areaRows = [...impactPanel.querySelectorAll('[data-impact-area]')];
    const barsWrap = impactPanel.querySelector('[data-impact-bars]');
    const periodLabel = impactPanel.querySelector('[data-impact-period-label]');
    let impactRange = 'historico';

    const applyImpactRange = (range) => {
        const data = IMPACT_RANGES[range];
        if (!data) return;
        hoursLabels.forEach((el) => (el.textContent = data.hours));
        if (savingsLabel) savingsLabel.textContent = data.savings;
        areaRows.forEach((row, index) => {
            const value = data.areas[index];
            const bar = row.querySelector('i b');
            const label = row.querySelector('em');
            if (bar) bar.style.width = `${value}%`;
            if (label) label.textContent = `${value}%`;
        });
        if (periodLabel) periodLabel.textContent = data.period;
        if (barsWrap) {
            const max = Math.max(...data.bars.map(([, value]) => value));
            barsWrap.innerHTML = data.bars
                .map(([label, value]) => {
                    const height = Math.max(12, Math.round((value / max) * 110));
                    return `<div class="evolution-bar"><b>${value} h</b><i style="height: ${height}px"></i><span>${label}</span></div>`;
                })
                .join('');
        }
    };

    rangeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            impactRange = button.dataset.impactRange;
            rangeButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
            applyImpactRange(impactRange);
        });
    });
    applyImpactRange(impactRange);

    /* Exportar reporte de impacto: CSV descargable o diálogo de impresión (PDF) */
    const buildImpactCsv = (range) => {
        const data = IMPACT_RANGES[range];
        const rows = [
            ['Reporte de impacto', 'VoluntMatch'],
            ['Rango', data.label],
            ['Horas de voluntariado', data.hours],
            ['Ahorro estimado', data.savings],
            [],
            ['Area', '% de proyectos entregados'],
            ...IMPACT_AREAS.map((area, index) => [area, data.areas[index]]),
            [],
            ['Periodo', 'Horas aportadas'],
            ...data.bars,
        ];
        return rows.map((row) => (row.length ? row.join(',') : '')).join('\n');
    };

    impactPanel.querySelectorAll('[data-impact-export]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = IMPACT_RANGES[impactRange];
            if (button.dataset.impactExport === 'csv') {
                const blob = new Blob([`\uFEFF${buildImpactCsv(impactRange)}`], {
                    type: 'text/csv;charset=utf-8',
                });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `reporte-impacto-${impactRange}.csv`;
                link.click();
                URL.revokeObjectURL(url);
                showToast(`Reporte CSV (${data.label}) descargado`);
                return;
            }
            showToast('En el diálogo, elige "Guardar como PDF"');
            window.print();
        });
    });

    /* Evaluación de voluntarios: exige voluntario elegido */
    const reviewVolunteer = impactPanel.querySelector('[data-review-volunteer]');
    const reviewProject = impactPanel.querySelector('[data-review-project]');
    impactPanel.querySelector('[data-review-publish]')?.addEventListener('click', () => {
        if (!reviewVolunteer?.value) {
            showToast('Elige un voluntario para evaluar');
            return;
        }
        const project = reviewProject?.value ? ` en ${reviewProject.value}` : '';
        showToast(`Evaluación de ${reviewVolunteer.value}${project} publicada en el perfil`);
    });
}
