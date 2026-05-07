import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

function initTournamentCreateForm() {
    const form = document.getElementById('tournament-create-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const url = form.dataset.url;
        const status = document.getElementById('save-status');
        const data = { name: form.querySelector('[name="name"]').value };

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
    const form = document.getElementById('tournament-edit-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const url = form.dataset.url;
        const status = document.getElementById('save-status');

        const data = {
            name: form.querySelector('[name="name"]').value,
            startedAt: form.querySelector('[name="startedAt"]').value || null,
            endedAt: form.querySelector('[name="endedAt"]').value || null,
            toursCount: form.querySelector('[name="toursCount"]').value ? parseInt(form.querySelector('[name="toursCount"]').value) : null,
            questionsPerTour: form.querySelector('[name="questionsPerTour"]').value ? parseInt(form.querySelector('[name="questionsPerTour"]').value) : null,
            difficulty: form.querySelector('[name="difficulty"]').value ? parseFloat(form.querySelector('[name="difficulty"]').value) : null,
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

function getOfficialIds(role) {
    const group = document.querySelector(`.officials-group[data-role="${role}"]`);
    if (!group) {
        return [];
    }
    return Array.from(group.querySelectorAll('input[type="hidden"]')).map(i => parseInt(i.value));
}

function initTournamentActions() {
    document.addEventListener('click', (e) => {
        const submitBtn = e.target.closest('[data-tournament-submit]');
        if (submitBtn) {
            tournamentAction(submitBtn.dataset.tournamentSubmit, 'submit', submitBtn);
            return;
        }

        const publishBtn = e.target.closest('[data-tournament-publish]');
        if (publishBtn) {
            tournamentAction(publishBtn.dataset.tournamentPublish, 'publish', publishBtn);
            return;
        }

        const deleteBtn = e.target.closest('[data-tournament-delete]');
        if (deleteBtn) {
            if (!confirm(deleteBtn.dataset.confirm || trans('tournament.my.delete_confirm'))) {
                return;
            }
            tournamentAction(deleteBtn.dataset.tournamentDelete, 'delete', deleteBtn, () => {
                window.location.href = '/my/tournaments';
            });
            return;
        }

        const approveBtn = e.target.closest('[data-tournament-approve]');
        if (approveBtn) {
            moderateTournament(approveBtn.dataset.tournamentApprove, 'approve', null, approveBtn);
            return;
        }

        const rejectBtn = e.target.closest('[data-tournament-reject]');
        if (rejectBtn) {
            const id = rejectBtn.dataset.tournamentReject;
            const commentInput = document.querySelector(`[data-tournament-reject-comment="${id}"]`);
            const comment = commentInput ? commentInput.value : null;
            moderateTournament(id, 'reject', comment, rejectBtn);
        }
    });
}

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

function moderateTournament(id, action, comment, btn) {
    btn.disabled = true;

    const data = action === 'reject' ? {comment} : undefined;

    apiPost(`/moderator/tournaments/${id}/${action}`, data)
        .then(({ok, data: responseData}) => {
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
                alert(responseData.error ? trans(responseData.error) : trans('common.error'));
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
