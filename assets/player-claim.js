// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';
import { buttonAction } from './button-action.js';

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
            buttonAction(
                '/player-claim/existing',
                /** @type {HTMLButtonElement} */ (existingBtn),
                {
                    data: {playerId},
                    onSuccess: () => { window.location.href = '/player-claim/submitted'; },
                },
            );
            return;
        }

        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.playerClaimApprove || '';
            buttonAction(
                `/moderator/player-claims/${id}/approve`,
                /** @type {HTMLButtonElement} */ (approveBtn),
                { onSuccess: () => removeClaimRow(approveBtn) },
            );
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.playerClaimReject || '';
            buttonAction(
                `/moderator/player-claims/${id}/reject`,
                /** @type {HTMLButtonElement} */ (rejectBtn),
                { onSuccess: () => removeClaimRow(rejectBtn) },
            );
        }
    });
}

/**
 * @param {Element} btn
 */
function removeClaimRow(btn) {
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
}

initPlayerClaimActions();

document.addEventListener('turbo:load', () => {
    initPlayerClaimNewForm();
});
