// @ts-check
import { apiPost, showError } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('block-form'));
    const unblockBtn = /** @type {HTMLElement|null} */ (document.getElementById('unblock-btn'));
    const flash = /** @type {HTMLElement|null} */ (document.getElementById('block-flash'));

    if (form && flash) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const reason = /** @type {HTMLTextAreaElement} */ (document.getElementById('block-reason')).value.trim();
            const { ok, body } = await apiPost(form.dataset.url ?? '', { reason });

            if (ok) {
                window.location.reload();
            } else {
                showError(flash, body.error);
            }
        });
    }

    if (unblockBtn && flash) {
        unblockBtn.addEventListener('click', async () => {
            const { ok, body } = await apiPost(unblockBtn.dataset.url ?? '');

            if (ok) {
                window.location.reload();
            } else {
                showError(flash, body.error);
            }
        });
    }
});
