// @ts-check
import { apiPost } from './api.js';
import { trans } from './trans.js';

function init() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('appeal-create-form'));
    if (!form) {
        return;
    }

    const questionSelect = /** @type {HTMLSelectElement|null} */ (document.getElementById('appeal-question'));
    const errorEl = /** @type {HTMLElement} */ (document.getElementById('appeal-status'));

    const rejectedQuestionsEl = document.getElementById('rejected-dispute-questions');
    const rejectedQuestions = rejectedQuestionsEl ? JSON.parse(rejectedQuestionsEl.textContent || '[]') : [];

    questionSelect?.addEventListener('change', () => {
        const selectedQuestion = parseInt(questionSelect.value || '0', 10);
        const acceptRadio = /** @type {HTMLInputElement|null} */ (form.querySelector('input[name="type"][value="accept"]'));

        if (acceptRadio) {
            const canAccept = rejectedQuestions.includes(selectedQuestion);
            acceptRadio.disabled = !canAccept;
            if (!canAccept && acceptRadio.checked) {
                const removeRadio = /** @type {HTMLInputElement|null} */ (form.querySelector('input[name="type"][value="remove"]'));
                if (removeRadio) {
                    removeRadio.checked = true;
                }
            }
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = form.dataset.url || '';
        const data = {
            questionNumber: Number(questionSelect?.value),
            type: /** @type {HTMLInputElement|null} */ (form.querySelector('input[name="type"]:checked'))?.value,
            text: /** @type {HTMLTextAreaElement} */ (form.querySelector('[name="text"]')).value.trim(),
        };

        errorEl.hidden = true;

        apiPost(url, data).then(({ ok, body }) => {
            if (ok) {
                window.location.href = url.replace('/create', '');
            } else {
                errorEl.textContent = trans(body.error || 'common.error');
                errorEl.hidden = false;
            }
        });
    });
}

document.addEventListener('turbo:load', init);
