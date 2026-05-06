import { trans } from './trans.js';

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

        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
        })
            .then(r => r.json().then(body => ({ok: r.ok, body})))
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = '/player-claim/submitted';
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

function initPlayerClaimActions() {
    document.addEventListener('click', (e) => {
        const existingBtn = e.target.closest('[data-player-claim-existing]');
        if (existingBtn) {
            const playerId = parseInt(existingBtn.dataset.playerClaimExisting);
            existingBtn.disabled = true;

            fetch('/player-claim/existing', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({playerId}),
            })
                .then(r => r.json().then(body => ({ok: r.ok, body})))
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

    fetch(`/moderator/player-claims/${id}/${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
    })
        .then(r => r.json().then(body => ({ok: r.ok, body})))
        .then(({ok, body}) => {
            if (ok) {
                const row = btn.closest('tr');
                row?.remove();
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

document.addEventListener('turbo:load', () => {
    initPlayerClaimNewForm();
    initPlayerClaimActions();
});
