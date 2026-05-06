import { trans } from './trans.js';

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

document.addEventListener('turbo:load', initVenueEditForm);
