/*
 * VoluntMatch — Formularios por pasos
 *
 * Cualquier <form data-form-steps> con secciones [data-step] se divide en
 * pasos. Los botones [data-step-next] y [data-step-prev] navegan y los
 * puntos [data-step-jump] permiten volver a un paso anterior.
 */
document.querySelectorAll('[data-form-steps]').forEach((form) => {
    const steps = [...form.querySelectorAll('[data-step]')];
    if (steps.length < 2) return;

    const dots = [...form.querySelectorAll('[data-step-jump]')];
    const total = steps.length;
    let current = Math.min(Math.max(Number(form.dataset.start) || 1, 1), total);

    // La validación la hacemos nosotros para poder abrir el paso que
    // contiene el campo inválido antes de mostrar el aviso del navegador.
    form.noValidate = true;

    const fieldsOf = (step) =>
        [...step.querySelectorAll('input, select, textarea')].filter(
            (field) => field.type !== 'hidden' && !field.disabled
        );

    const show = (index, focus = false) => {
        current = Math.min(Math.max(index, 1), total);

        steps.forEach((step, i) => {
            const active = i + 1 === current;
            step.hidden = !active;
            step.classList.toggle('active', active);
        });

        dots.forEach((dot, i) => {
            const active = i + 1 === current;
            dot.classList.toggle('active', active);
            dot.classList.toggle('done', i + 1 < current);
            dot.setAttribute('aria-current', active ? 'step' : 'false');
        });

        form.dataset.step = String(current);

        if (focus) {
            const first = steps[current - 1].querySelector(
                'input:not([type="hidden"]), select, textarea'
            );
            first?.focus({ preventScroll: true });
        }
    };

    const validate = (index) => {
        const step = steps[index];
        if (!step) return true;

        const invalid = fieldsOf(step).find((field) => !field.checkValidity());
        if (!invalid) return true;

        show(index + 1);
        invalid.reportValidity();
        invalid.focus({ preventScroll: true });
        return false;
    };

    form.addEventListener('click', (event) => {
        const next = event.target.closest('[data-step-next]');
        const prev = event.target.closest('[data-step-prev]');
        const jump = event.target.closest('[data-step-jump]');

        if (next) {
            if (validate(current - 1)) show(current + 1, true);
            return;
        }

        if (prev) {
            show(current - 1, true);
            return;
        }

        if (jump) {
            const target = Number(jump.dataset.stepJump);
            if (target === current) return;
            if (target > current && !validate(current - 1)) return;
            show(target, true);
        }
    });

    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || current >= total) return;
        if (event.target.tagName !== 'INPUT' || event.target.type === 'checkbox') return;

        event.preventDefault();
        if (validate(current - 1)) show(current + 1, true);
    });

    form.addEventListener('submit', (event) => {
        for (let i = 0; i < total; i += 1) {
            if (!validate(i)) {
                event.preventDefault();
                return;
            }
        }
    });

    show(current);
});
