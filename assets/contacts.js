// @ts-check
import { apiPost } from './api.js';
import { trans } from './trans.js';

function initContactsForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('contacts-form'));
    if (!form || form.dataset.initialized) {
        return;
    }
    form.dataset.initialized = 'true';

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const flashEl = /** @type {HTMLElement} */ (document.getElementById('contacts-flash'));
        const url = /** @type {string} */ (form.dataset.url);

        const data = {
            telegram: /** @type {HTMLInputElement} */ (form.querySelector('[name="telegram"]')).value.trim() || null,
            facebook: /** @type {HTMLInputElement} */ (form.querySelector('[name="facebook"]')).value.trim() || null,
            phone: /** @type {HTMLInputElement} */ (form.querySelector('[name="phone"]')).value.trim() || null,
        };

        apiPost(url, data).then(({ ok, body }) => {
            if (ok) {
                flashEl.textContent = trans('contact.saved');
                flashEl.className = 'flash-success';
                flashEl.hidden = false;
            } else {
                let message = trans('common.error');
                if (body.violations && body.violations.length > 0) {
                    message = body.violations.map(v => trans(v.title) || v.title).join('. ');
                } else if (body.detail) {
                    message = body.detail;
                } else if (body.error) {
                    message = trans(body.error);
                }
                flashEl.textContent = message;
                flashEl.className = 'flash-error';
                flashEl.hidden = false;
            }
        });
    });
}

initContactsForm();
document.addEventListener('turbo:load', initContactsForm);
