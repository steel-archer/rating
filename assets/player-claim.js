// @ts-check
import { trans } from './trans.js';
import { apiPost, transError } from './api.js';
import { buttonAction } from './button-action.js';

function initPlayerClaimNewForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('player-claim-new-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);

        const data = {
            lastName: /** @type {HTMLInputElement} */ (form.querySelector('[name="lastName"]')).value,
            firstName: /** @type {HTMLInputElement} */ (form.querySelector('[name="firstName"]')).value,
            patronymic: /** @type {HTMLInputElement} */ (form.querySelector('[name="patronymic"]')).value || null,
            townId: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="townId"]')).value) || null,
            townName: null,
            termsAccepted: isTermsAccepted(),
            telegram: getContactValue('telegram'),
            facebook: getContactValue('facebook'),
            phone: getContactValue('phone'),
        };

        if (!data.townId) {
            const townInput = /** @type {HTMLInputElement} */ (form.querySelector('[data-suggest-input]'));
            data.townName = townInput.value.trim() || null;
        }

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = '/player-claim/submitted';
                } else {
                    alert(transError(body.error));
                }
            })
            .catch(() => {
                alert(transError(null));
            });
    });
}

function initPlayerClaimActions() {
    document.addEventListener('click', (event) => {
        const existingBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-existing]');
        if (existingBtn) {
            if (existingBtn.getAttribute('aria-disabled') === 'true') {
                return;
            }
            const playerId = parseInt(/** @type {HTMLElement} */ (existingBtn).dataset.playerClaimExisting || '');
            buttonAction(
                '/player-claim/existing',
                /** @type {HTMLButtonElement} */ (existingBtn),
                {
                    data: {
                        playerId,
                        termsAccepted: isTermsAccepted(),
                        telegram: getContactValue('telegram'),
                        facebook: getContactValue('facebook'),
                        phone: getContactValue('phone'),
                    },
                    onSuccess: () => { window.location.href = '/player-claim/submitted'; },
                },
            );
            return;
        }

        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-player-claim-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.playerClaimApprove || '';
            const row = approveBtn.closest('tr');
            const townInput = row ? /** @type {HTMLInputElement|null} */ (row.querySelector('[data-suggest-input]')) : null;
            const townName = townInput ? townInput.value.trim() : null;
            buttonAction(
                `/moderator/player-claims/${id}/approve`,
                /** @type {HTMLButtonElement} */ (approveBtn),
                {
                    data: { townName: townName || null },
                    onSuccess: () => removeClaimRow(approveBtn),
                },
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

/**
 * @param {string} name
 * @returns {string|null}
 */
function getContactValue(name) {
    const input = /** @type {HTMLInputElement|null} */ (document.querySelector(`[name="${name}"]`));
    return input ? input.value.trim() || null : null;
}

/**
 * @returns {boolean}
 */
function isTermsAccepted() {
    const checkbox = /** @type {HTMLInputElement|null} */ (document.getElementById('claim-terms'));
    return checkbox ? checkbox.checked : false;
}

document.addEventListener('turbo:frame-load', (event) => {
    if (/** @type {CustomEvent} */ (event).target?.id === 'claim-search') {
        updateClaimButtons();
    }
});

document.addEventListener('turbo:load', () => {
    initPlayerClaimNewForm();
    initTermsCheckbox();
});

function initTermsCheckbox() {
    const checkbox = /** @type {HTMLInputElement|null} */ (document.getElementById('claim-terms'));
    if (!checkbox) {
        return;
    }
    checkbox.addEventListener('change', updateClaimButtons);
    updateClaimButtons();
}

function updateClaimButtons() {
    const accepted = isTermsAccepted();
    const newSubmit = /** @type {HTMLButtonElement|null} */ (document.getElementById('player-claim-new-form'))?.querySelector('button[type="submit"]');
    if (newSubmit) {
        newSubmit.disabled = !accepted;
    }
    document.querySelectorAll('[data-player-claim-existing]').forEach((btn) => {
        btn.setAttribute('aria-disabled', String(!accepted));
        /** @type {HTMLButtonElement} */ (btn).classList.toggle('btn-disabled', !accepted);
    });
}
