/*
 * VoluntMatch — Toasts de aviso (showToast es global)
 */
/* ============================================================
   TOASTS
============================================================ */
const toast = document.querySelector('[data-toast]');
const toastText = document.querySelector('[data-toast-text]');
let toastTimer = null;

function showToast(message) {
    if (!toast || !toastText) return;
    toastText.textContent = message;
    toast.hidden = false;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 2600);
}

document.querySelectorAll('[data-toast-msg]').forEach((el) => {
    el.addEventListener('click', () => showToast(el.dataset.toastMsg));
});
