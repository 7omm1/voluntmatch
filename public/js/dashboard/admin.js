/*
 * VoluntMatch — Administrador: verificación de ONG y moderación
 */
/* ============================================================
   DASHBOARD — verificación / moderación
============================================================ */
document.querySelectorAll('[data-verify]').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('[data-verification-row]');
        const status = row?.querySelector('.status');
        if (status && row) {
            status.className = `status ${button.dataset.verify.toLowerCase()}`;
            status.textContent = button.dataset.verify;
        }
        showToast(
            button.dataset.verify === 'Verificada'
                ? 'Organización verificada'
                : 'Organización rechazada',
        );
    });
});

document.querySelectorAll('[data-toggle-status]').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('[data-user-row]');
        const status = row?.querySelector('.status');
        if (status) {
            const blocked = status.textContent.trim() === 'Activo';
            status.className = `status ${blocked ? 'bloqueado' : 'activo'}`;
            status.textContent = blocked ? 'Bloqueado' : 'Activo';
            button.textContent = blocked ? 'Reactivar' : 'Bloquear';
        }
        showToast('Registro actualizado');
    });
});
