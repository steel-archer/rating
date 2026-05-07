// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';
import { buttonAction } from './button-action.js';

function initTournamentCreateForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('tournament-create-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('save-status'));
        const data = { name: /** @type {HTMLInputElement} */ (form.querySelector('[name="name"]')).value };

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = `/my/tournaments/${body.id}/edit`;
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => {
                showError(status, null);
            });
    });
}

function initTournamentEditForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('tournament-edit-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('save-status'));

        const data = {
            name: /** @type {HTMLInputElement} */ (form.querySelector('[name="name"]')).value,
            startedAt: /** @type {HTMLInputElement} */ (form.querySelector('[name="startedAt"]')).value || null,
            endedAt: /** @type {HTMLInputElement} */ (form.querySelector('[name="endedAt"]')).value || null,
            toursCount: /** @type {HTMLInputElement} */ (form.querySelector('[name="toursCount"]')).value
                ? parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="toursCount"]')).value)
                : null,
            questionsPerTour: /** @type {HTMLInputElement} */ (form.querySelector('[name="questionsPerTour"]')).value
                ? parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="questionsPerTour"]')).value)
                : null,
            difficulty: /** @type {HTMLInputElement} */ (form.querySelector('[name="difficulty"]')).value
                ? parseFloat(/** @type {HTMLInputElement} */ (form.querySelector('[name="difficulty"]')).value)
                : null,
            organizers: getOfficialIds('organizers'),
            editors: getOfficialIds('editors'),
            gameJury: getOfficialIds('gameJury'),
            appealJury: getOfficialIds('appealJury'),
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

/**
 * @param {string} role
 * @returns {number[]}
 */
function getOfficialIds(role) {
    const group = document.querySelector(`.officials-group[data-role="${role}"]`);
    if (!group) {
        return [];
    }
    return Array.from(group.querySelectorAll('input[type="hidden"]'))
        .map(input => parseInt(/** @type {HTMLInputElement} */ (input).value));
}

function initTournamentActions() {
    document.addEventListener('click', (event) => {
        const submitBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-submit]');
        if (submitBtn) {
            buttonAction(
                `/my/tournaments/${/** @type {HTMLElement} */ (submitBtn).dataset.tournamentSubmit}/submit`,
                /** @type {HTMLButtonElement} */ (submitBtn),
            );
            return;
        }

        const publishBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-publish]');
        if (publishBtn) {
            buttonAction(
                `/my/tournaments/${/** @type {HTMLElement} */ (publishBtn).dataset.tournamentPublish}/publish`,
                /** @type {HTMLButtonElement} */ (publishBtn),
            );
            return;
        }

        const deleteBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-delete]');
        if (deleteBtn) {
            const confirmMessage = /** @type {HTMLElement} */ (deleteBtn).dataset.confirm || trans('tournament.my.delete_confirm');
            if (!confirm(confirmMessage)) {
                return;
            }
            buttonAction(
                `/my/tournaments/${/** @type {HTMLElement} */ (deleteBtn).dataset.tournamentDelete}/delete`,
                /** @type {HTMLButtonElement} */ (deleteBtn),
                { onSuccess: () => { window.location.href = '/my/tournaments'; } },
            );
            return;
        }

        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.tournamentApprove || '';
            buttonAction(
                `/moderator/tournaments/${id}/approve`,
                /** @type {HTMLButtonElement} */ (approveBtn),
                { onSuccess: () => removeModerationCard(approveBtn) },
            );
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.tournamentReject || '';
            const commentInput = /** @type {HTMLInputElement|null} */ (document.querySelector(`[data-tournament-reject-comment="${id}"]`));
            const comment = commentInput ? commentInput.value : null;
            buttonAction(
                `/moderator/tournaments/${id}/reject`,
                /** @type {HTMLButtonElement} */ (rejectBtn),
                { data: {comment}, onSuccess: () => removeModerationCard(rejectBtn) },
            );
        }
    });
}

/**
 * @param {Element} btn
 */
function removeModerationCard(btn) {
    const card = btn.closest('.moderation-card');
    card?.remove();
    if (document.querySelectorAll('.moderation-card').length === 0) {
        const container = document.querySelector('h1')?.parentElement;
        if (container && !container.querySelector('.empty-state')) {
            const emptyState = document.createElement('p');
            emptyState.className = 'empty-state';
            emptyState.textContent = trans('moderator.no_tournament_claims_pending');
            container.appendChild(emptyState);
        }
    }
}

initTournamentActions();

document.addEventListener('turbo:load', () => {
    initTournamentCreateForm();
    initTournamentEditForm();
});
