// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

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
            tournamentAction(/** @type {HTMLElement} */ (submitBtn).dataset.tournamentSubmit || '', 'submit', /** @type {HTMLButtonElement} */ (submitBtn));
            return;
        }

        const publishBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-publish]');
        if (publishBtn) {
            tournamentAction(/** @type {HTMLElement} */ (publishBtn).dataset.tournamentPublish || '', 'publish', /** @type {HTMLButtonElement} */ (publishBtn));
            return;
        }

        const deleteBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-delete]');
        if (deleteBtn) {
            const confirmMessage = /** @type {HTMLElement} */ (deleteBtn).dataset.confirm || trans('tournament.my.delete_confirm');
            if (!confirm(confirmMessage)) {
                return;
            }
            tournamentAction(/** @type {HTMLElement} */ (deleteBtn).dataset.tournamentDelete || '', 'delete', /** @type {HTMLButtonElement} */ (deleteBtn), () => {
                window.location.href = '/my/tournaments';
            });
            return;
        }

        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-approve]');
        if (approveBtn) {
            moderateTournament(/** @type {HTMLElement} */ (approveBtn).dataset.tournamentApprove || '', 'approve', null, /** @type {HTMLButtonElement} */ (approveBtn));
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-tournament-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.tournamentReject || '';
            const commentInput = /** @type {HTMLInputElement|null} */ (document.querySelector(`[data-tournament-reject-comment="${id}"]`));
            const comment = commentInput ? commentInput.value : null;
            moderateTournament(id, 'reject', comment, /** @type {HTMLButtonElement} */ (rejectBtn));
        }
    });
}

/**
 * @param {string} id
 * @param {string} action
 * @param {HTMLButtonElement} btn
 * @param {function(): void} [onSuccess]
 */
function tournamentAction(id, action, btn, onSuccess) {
    btn.disabled = true;

    apiPost(`/my/tournaments/${id}/${action}`)
        .then(({ok, body}) => {
            if (ok) {
                if (onSuccess) {
                    onSuccess();
                } else {
                    window.location.reload();
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

/**
 * @param {string} id
 * @param {string} action
 * @param {string|null} comment
 * @param {HTMLButtonElement} btn
 */
function moderateTournament(id, action, comment, btn) {
    btn.disabled = true;

    const data = action === 'reject' ? {comment} : undefined;

    apiPost(`/moderator/tournaments/${id}/${action}`, data)
        .then(({ok, body}) => {
            if (ok) {
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

initTournamentActions();

document.addEventListener('turbo:load', () => {
    initTournamentCreateForm();
    initTournamentEditForm();
});
