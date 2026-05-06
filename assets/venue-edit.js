import { trans } from './trans.js';

function initVenueCreateForm() {
    const form = document.getElementById('venue-create-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const url = form.dataset.url;
        const status = document.getElementById('save-status');

        const data = {
            name: form.querySelector('[name="name"]').value,
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
                    window.location.href = '/my/venues';
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

function initVenueEditForm() {
    const form = document.getElementById('venue-edit-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const url = form.dataset.url;
        const status = document.getElementById('save-status');
        const group = form.querySelector('.officials-group[data-role="representatives"]');

        const data = {
            representatives: Array.from(group.querySelectorAll('input[type="hidden"]')).map(i => parseInt(i.value)),
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

function initVenueModeration() {
    document.addEventListener('click', (e) => {
        const approveBtn = e.target.closest('[data-venue-approve]');
        if (approveBtn) {
            const id = approveBtn.dataset.venueApprove;
            moderateVenue(id, 'approve', approveBtn);
            return;
        }

        const rejectBtn = e.target.closest('[data-venue-reject]');
        if (rejectBtn) {
            const id = rejectBtn.dataset.venueReject;
            moderateVenue(id, 'reject', rejectBtn);
        }
    });
}

function moderateVenue(id, action, btn) {
    btn.disabled = true;

    fetch(`/moderator/venues/${id}/${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
    })
        .then(r => r.json().then(body => ({ok: r.ok, body})))
        .then(({ok, body}) => {
            if (ok) {
                const card = btn.closest('[data-venue-id]');
                card?.remove();
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
    initVenueCreateForm();
    initVenueEditForm();
    initVenueModeration();
});
