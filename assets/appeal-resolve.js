// @ts-check
import { apiPost } from './api.js';
import { trans } from './trans.js';

function init() {
    document.addEventListener('click', (event) => {
        const btn = /** @type {HTMLElement} */ (event.target).closest('[data-appeal-resolve]');
        if (!btn) {
            return;
        }

        const url = btn.dataset.appealResolve || '';
        const action = btn.dataset.action || '';
        const row = btn.closest('tr');
        const verdictInput = /** @type {HTMLTextAreaElement|null} */ (row?.querySelector('.appeal-verdict-input'));
        const verdict = verdictInput?.value.trim() || null;

        btn.disabled = true;

        apiPost(url, { action, verdict }).then(({ ok, body }) => {
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
