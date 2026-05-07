// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

function initPlayerClaimNewForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('player-claim-new-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('claim-new-status'));

        const data = {
            lastName: /** @type {HTMLInputElement} */ (form.querySelector('[name="lastName"]')).value,
            firstName: /** @type {HTMLInputElement} */ (form.querySelector('[name="firstName"]')).value || null,
            patronymic: /** @type {HTMLInputElement} */ (form.querySelector('[name="patronymic"]')).value || null,
            townId: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="townId"]')).value) || null,
        };

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = '/player-claim/submitted';
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => {
                showError(status, null);
            });
    });
}

function initPlayerClaimActions() {
    document.addEventListener('click', (event) => {
        const existingBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-existing]');
        if (existingBtn) {
            const playerId = parseInt(/** @type {HTMLElement} */ (existingBtn).dataset.playerClaimExisting || '');
            /** @type {HTMLButtonElement} */ (existingBtn).disabled = true;

            apiPost('/player-claim/existing', {playerId})
                .then(({ok, body}) => {
                    if (ok) {
                        window.location.href = '/player-claim/submitted';
                    } else {
                        /** @type {HTMLButtonElement} */ (existingBtn).disabled = false;
                        alert(body.error ? trans(body.error) : trans('common.error'));
                    }
                })
                .catch(() => {
                    /** @type {HTMLButtonElement} */ (existingBtn).disabled = false;
                    alert(trans('common.error'));
                });
            return;
        }

        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-approve]');
        if (approveBtn) {
            moderatePlayerClaim(/** @type {HTMLElement} */ (approveBtn).dataset.playerClaimApprove || '', 'approve', /** @type {HTMLButtonElement} */ (approveBtn));
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-reject]');
        if (rejectBtn) {
            moderatePlayerClaim(/** @type {HTMLElement} */ (rejectBtn).dataset.playerClaimReject || '', 'reject', /** @type {HTMLButtonElement} */ (rejectBtn));
        }
    });
}

/**
 * @param {string} id
 * @param {string} action
 * @param {HTMLButtonElement} btn
 */
function moderatePlayerClaim(id, action, btn) {
    btn.disabled = true;

    apiPost(`/moderator/player-claims/${id}/${action}`)
        .then(({ok, body}) => {
            if (ok) {
                const row = btn.closest('tr');
                row?.remove();
                if (document.querySelectorAll('table tbody tr').length === 0) {
                    const table = document.querySelector('table');
                    if (table) {
                        const emptyState = document.createElement('p');
                        emptyState.textContent = trans('moderator.no_pending_claims');
                        table.replaceWith(emptyState);
                    }
                }
            } else {
                btn.disabled = false;
                alert(body.error ? trans(body.error) : trans('common.error'));
            }
        })
        .catch(() => {
            btn.disabled = false;
            alert(trans('common.error'));
        });
}

initPlayerClaimActions();

document.addEventListener('turbo:load', () => {
    initPlayerClaimNewForm();
});
