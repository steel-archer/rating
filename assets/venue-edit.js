// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

function initVenueCreateForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('venue-create-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('save-status'));

        const data = {
            name: /** @type {HTMLInputElement} */ (form.querySelector('[name="name"]')).value,
            townId: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="townId"]')).value) || null,
        };

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = '/my/venues';
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => {
                showError(status, null);
            });
    });
}

function initVenueEditForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('venue-edit-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('save-status'));
        const group = /** @type {HTMLElement} */ (form.querySelector('.officials-group[data-role="representatives"]'));

        const data = {
            representatives: Array.from(group.querySelectorAll('input[type="hidden"]'))
                .map(input => parseInt(/** @type {HTMLInputElement} */ (input).value)),
        };

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => {
                showError(status, null);
            });
    });
}

function initVenueModeration() {
    document.addEventListener('click', (event) => {
        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-venue-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.venueApprove || '';
            moderateVenue(id, 'approve', /** @type {HTMLButtonElement} */ (approveBtn));
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-venue-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.venueReject || '';
            moderateVenue(id, 'reject', /** @type {HTMLButtonElement} */ (rejectBtn));
        }
    });
}

/**
 * @param {string} id
 * @param {string} action
 * @param {HTMLButtonElement} btn
 */
function moderateVenue(id, action, btn) {
    btn.disabled = true;

    apiPost(`/moderator/venues/${id}/${action}`)
        .then(({ok, body}) => {
            if (ok) {
                const card = btn.closest('[data-venue-id]');
                card?.remove();
                if (document.querySelectorAll('[data-venue-id]').length === 0) {
                    const container = document.querySelector('.moderation-card')?.parentElement || document.querySelector('h1')?.parentElement;
                    if (container && !container.querySelector('.empty-state')) {
                        const emptyState = document.createElement('p');
                        emptyState.className = 'empty-state';
                        emptyState.textContent = trans('moderator.no_venue_claims');
                        container.appendChild(emptyState);
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

initVenueModeration();

document.addEventListener('turbo:load', () => {
    initVenueCreateForm();
    initVenueEditForm();
});
