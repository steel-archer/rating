// @ts-check
import { apiPost } from './api.js';
import { trans } from './trans.js';

function init() {
    document.addEventListener('click', (event) => {
        const btn = /** @type {HTMLElement} */ (event.target).closest('[data-dispute-resolve]');
        if (!btn) {
            return;
        }

        const url = btn.dataset.disputeResolve || '';
        const action = btn.dataset.action || '';
        const row = btn.closest('tr');
        const commentInput = /** @type {HTMLInputElement|null} */ (row?.querySelector('.dispute-comment-input'));
        const comment = commentInput?.value.trim() || null;

        btn.disabled = true;

        apiPost(url, { action, comment }).then(({ ok, body }) => {
            if (ok) {
                window.location.reload();
            } else {
                const actionsCell = btn.closest('td');
                if (actionsCell) {
                    let errorEl = actionsCell.querySelector('.form-error');
                    if (!errorEl) {
                        errorEl = document.createElement('div');
                        errorEl.className = 'form-error';
                        actionsCell.appendChild(errorEl);
                    }
                    errorEl.textContent = trans(body.error || 'common.error');
                }
                btn.disabled = false;
            }
        });
    });
}

document.addEventListener('turbo:load', init);
