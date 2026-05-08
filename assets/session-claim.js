// @ts-check
import { trans } from './trans.js';
import { apiPost } from './api.js';
import { buttonAction } from './button-action.js';

function initSessionClaimForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('session-claim-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const redirect = form.dataset.redirect || null;
        const status = /** @type {HTMLElement} */ (document.getElementById('session-claim-status'));
        const hostInput = /** @type {HTMLInputElement|null} */ (form.querySelector('.officials-group[data-role="claim-host"] input[type="hidden"]'));

        const data = {
            venueId: parseInt(/** @type {HTMLSelectElement} */ (form.querySelector('[name="venueId"]')).value) || null,
            playedAt: /** @type {HTMLInputElement} */ (form.querySelector('[name="playedAt"]')).value || null,
            estimatedTeams: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="estimatedTeams"]')).value) || null,
            hostId: hostInput ? parseInt(hostInput.value) || null : null,
        };

        status.hidden = true;

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    if (redirect) {
                        window.location.href = redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    status.textContent = body.error ? trans(body.error) : trans('common.error');
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.hidden = false;
            });
    });
}

function initSessionClaimEditForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('session-claim-edit-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('save-status'));
        const hostInput = /** @type {HTMLInputElement|null} */ (form.querySelector('.officials-group[data-role="host"] input[type="hidden"]'));

        const data = {
            playedAt: /** @type {HTMLInputElement} */ (form.querySelector('[name="playedAt"]')).value || null,
            estimatedTeams: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="estimatedTeams"]')).value) || null,
            hostId: hostInput ? parseInt(hostInput.value) || null : null,
        };

        status.hidden = true;

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    status.textContent = body.error ? trans(body.error) : trans('common.error');
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.hidden = false;
            });
    });
}

function initSessionClaimActions() {
    document.addEventListener('click', (event) => {
        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.sessionApprove || '';
            buttonAction(
                `/my/tournament-claims/${id}/approve`,
                /** @type {HTMLButtonElement} */ (approveBtn),
                { onSuccess: () => { removeSessionClaimCard(approveBtn); } },
            );
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.sessionReject || '';
            const commentInput = /** @type {HTMLInputElement|null} */ (document.querySelector(`[data-session-reject-comment="${id}"]`));
            const comment = commentInput ? commentInput.value : null;
            buttonAction(
                `/my/tournament-claims/${id}/reject`,
                /** @type {HTMLButtonElement} */ (rejectBtn),
                { data: {comment}, onSuccess: () => removeSessionClaimCard(rejectBtn) },
            );
            return;
        }

        const resubmitBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-resubmit]');
        if (resubmitBtn) {
            const id = /** @type {HTMLElement} */ (resubmitBtn).dataset.sessionResubmit || '';
            buttonAction(
                `/my/session-claims/${id}/resubmit`,
                /** @type {HTMLButtonElement} */ (resubmitBtn),
            );
            return;
        }

        const deleteBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-delete]');
        if (deleteBtn) {
            const id = /** @type {HTMLElement} */ (deleteBtn).dataset.sessionDelete || '';
            const redirect = /** @type {HTMLElement} */ (deleteBtn).dataset.redirect || null;
            buttonAction(
                `/my/session-claims/${id}/delete`,
                /** @type {HTMLButtonElement} */ (deleteBtn),
                { onSuccess: () => redirect ? (window.location.href = redirect) : removeSessionClaimCard(deleteBtn) },
            );
        }
    });
}

/**
 * @param {Element} btn
 */
function removeSessionClaimCard(btn) {
    const row = btn.closest('[data-session-claim-id]');
    if (!row) {
        return;
    }
    const container = row.closest('.card');
    row.remove();
    if (container && container.querySelectorAll('[data-session-claim-id]').length === 0) {
        container.remove();
    }
}

initSessionClaimActions();

document.addEventListener('turbo:load', () => {
    initSessionClaimForm();
    initSessionClaimEditForm();
});
