// @ts-check
import { apiPost } from './api.js';
import { trans } from './trans.js';

function init() {
    initCreateForm();
    initSubmitForm();
    initDeleteButtons();
    initSelectAll();
}

function initCreateForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('dispute-create-form'));
    if (!form) {
        return;
    }

    const teamSelect = /** @type {HTMLSelectElement} */ (form.querySelector('[name="sessionTeamId"]'));
    const questionSelect = /** @type {HTMLSelectElement} */ (form.querySelector('[name="questionNumber"]'));

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = form.dataset.url || '';
        const errorEl = /** @type {HTMLElement} */ (document.getElementById('dispute-create-error'));

        const data = {
            sessionTeamId: Number(teamSelect.value),
            questionNumber: Number(questionSelect.value),
            text: /** @type {HTMLInputElement} */ (form.querySelector('[name="text"]')).value.trim(),
        };

        errorEl.hidden = true;

        apiPost(url, data).then(({ ok, body }) => {
            if (ok) {
                window.location.reload();
            } else {
                errorEl.textContent = trans(body.error || 'common.error');
                errorEl.hidden = false;
            }
        });
    });
}

function initSubmitForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('dispute-submit-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = form.dataset.url || '';
        const errorEl = /** @type {HTMLElement} */ (document.getElementById('dispute-submit-error'));

        const checkboxes = /** @type {NodeListOf<HTMLInputElement>} */ (form.querySelectorAll('.dispute-checkbox:checked'));
        const ids = Array.from(checkboxes).map(cb => Number(cb.value));

        if (ids.length === 0) {
            errorEl.textContent = trans('dispute.error.nothing_to_submit');
            errorEl.hidden = false;
            return;
        }

        errorEl.hidden = true;

        apiPost(url, { ids }).then(({ ok, body }) => {
            if (ok) {
                window.location.reload();
            } else {
                errorEl.textContent = trans(body.error || 'common.error');
                errorEl.hidden = false;
            }
        });
    });
}

function initDeleteButtons() {
    document.addEventListener('click', (event) => {
        const btn = /** @type {HTMLElement} */ (event.target).closest('[data-dispute-delete]');
        if (!btn) {
            return;
        }

        const url = /** @type {HTMLElement} */ (btn).dataset.disputeDelete || '';

        apiPost(url).then(({ ok }) => {
            if (ok) {
                window.location.reload();
            }
        });
    });
}

function initSelectAll() {
    const selectAll = /** @type {HTMLInputElement|null} */ (document.getElementById('dispute-select-all'));
    if (!selectAll) {
        return;
    }

    selectAll.addEventListener('change', () => {
        const checkboxes = /** @type {NodeListOf<HTMLInputElement>} */ (document.querySelectorAll('.dispute-checkbox'));
        checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
    });
}

document.addEventListener('turbo:load', init);
