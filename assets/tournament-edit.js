import { trans } from './trans.js';

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

document.addEventListener('turbo:load', initTournamentEditForm);
