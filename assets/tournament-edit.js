import { trans } from './trans.js';

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

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
        })
            .then(r => r.json().then(body => ({ok: r.ok, body})))
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = `/my/tournaments/${body.id}/edit`;
                } else {
                    status.textContent = body.error ? trans(body.error) : trans('common.error');
                    status.className = 'save-status save-status-error';
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.className = 'save-status save-status-error';
                status.hidden = false;
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

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
        })
            .then(r => r.json().then(body => ({ok: r.ok, body})))
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    status.textContent = body.error
                        ? body.error.split(' ').map(key => trans(key)).join('. ')
                        : trans('common.error');
                    status.className = 'save-status save-status-error';
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.className = 'save-status save-status-error';
                status.hidden = false;
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

    fetch(`/my/tournaments/${id}/${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
    })
        .then(r => r.json().then(body => ({ok: r.ok, body})))
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

    const body = action === 'reject' ? JSON.stringify({comment}) : undefined;

    fetch(`/moderator/tournaments/${id}/${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body,
    })
        .then(r => r.json().then(data => ({ok: r.ok, data})))
        .then(({ok, data}) => {
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
                alert(data.error ? trans(data.error) : trans('common.error'));
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
