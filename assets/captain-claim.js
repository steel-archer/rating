// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('captain-claim-toggle');
    const form = document.getElementById('captain-claim-form');
    const comment = /** @type {HTMLTextAreaElement} */ (document.getElementById('captain-claim-comment'));
    const submitBtn = /** @type {HTMLButtonElement} */ (document.getElementById('captain-claim-submit'));
    const status = /** @type {HTMLElement} */ (document.getElementById('captain-claim-status'));

    if (!toggle || !form || !comment || !submitBtn || !status) {
        return;
    }

    toggle.addEventListener('click', () => {
        form.hidden = !form.hidden;
        if (!form.hidden) {
            comment.focus();
        }
    });

    submitBtn.addEventListener('click', () => {
        const text = comment.value.trim();
        if (!text) {
            showError(status, 'captain_claim.error.comment_required');
            return;
        }

        const teamId = parseInt(submitBtn.dataset.teamId || '0');
        if (!teamId) {
            return;
        }

        submitBtn.disabled = true;
        status.hidden = true;

        apiPost('/my/captain-claim', { teamId, comment: text })
            .then(({ ok, body }) => {
                if (ok) {
                    status.textContent = trans('captain_claim.success');
                    status.className = 'save-status save-status-success';
                    status.hidden = false;
                    submitBtn.disabled = true;
                    comment.disabled = true;
                    toggle.hidden = true;
                } else {
                    submitBtn.disabled = false;
                    showError(status, body.error);
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                showError(status, null);
            });
    });
});
