import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

function initPlayerClaimNewForm() {
    const form = document.getElementById('player-claim-new-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const url = form.dataset.url;
        const status = document.getElementById('claim-new-status');

        const data = {
            lastName: form.querySelector('[name="lastName"]').value,
            firstName: form.querySelector('[name="firstName"]').value || null,
            patronymic: form.querySelector('[name="patronymic"]').value || null,
            townId: parseInt(form.querySelector('[name="townId"]').value) || null,
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
    document.addEventListener('click', (e) => {
        const existingBtn = e.target.closest('[data-player-claim-existing]');
        if (existingBtn) {
            const playerId = parseInt(existingBtn.dataset.playerClaimExisting);
            existingBtn.disabled = true;

            apiPost('/player-claim/existing', {playerId})
                .then(({ok, body}) => {
                    if (ok) {
                        window.location.href = '/player-claim/submitted';
                    } else {
                        existingBtn.disabled = false;
                        alert(body.error ? trans(body.error) : trans('common.error'));
                    }
                })
                .catch(() => {
                    existingBtn.disabled = false;
                    alert(trans('common.error'));
                });
            return;
        }

        const approveBtn = e.target.closest('[data-player-claim-approve]');
        if (approveBtn) {
            moderatePlayerClaim(approveBtn.dataset.playerClaimApprove, 'approve', approveBtn);
            return;
        }

        const rejectBtn = e.target.closest('[data-player-claim-reject]');
        if (rejectBtn) {
            moderatePlayerClaim(rejectBtn.dataset.playerClaimReject, 'reject', rejectBtn);
        }
    });
}

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
