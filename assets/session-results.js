// @ts-check
import { trans } from './trans.js';

function init() {
    const uploadBtn = /** @type {HTMLButtonElement|null} */ (document.getElementById('upload-btn'));
    const submitBtn = /** @type {HTMLButtonElement|null} */ (document.getElementById('submit-btn'));

    if (uploadBtn) {
        uploadBtn.addEventListener('click', handleUpload);
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', handleSubmit);
    }
}

function handleUpload() {
    const fileInput = /** @type {HTMLInputElement|null} */ (document.getElementById('results-file'));
    const uploadBtn = /** @type {HTMLButtonElement} */ (document.getElementById('upload-btn'));
    const errorsEl = /** @type {HTMLElement} */ (document.getElementById('upload-errors'));
    const successEl = /** @type {HTMLElement} */ (document.getElementById('upload-success'));

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        errorsEl.textContent = trans('results.error.no_file');
        errorsEl.hidden = false;
        successEl.hidden = true;
        return;
    }

    const url = uploadBtn.dataset.uploadUrl || '';
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    uploadBtn.disabled = true;
    errorsEl.hidden = true;
    successEl.hidden = true;

    fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData,
    })
        .then(response => response.json().then(body => ({ ok: response.ok, body })))
        .then(({ ok, body }) => {
            if (ok) {
                successEl.hidden = false;
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const errors = body.errors || [body.error || 'common.error'];
                errorsEl.innerHTML = errors.map(e => translateError(e)).join('<br>');
                errorsEl.hidden = false;
            }
        })
        .catch(() => {
            errorsEl.textContent = trans('common.error');
            errorsEl.hidden = false;
        })
        .finally(() => {
            uploadBtn.disabled = false;
        });
}

function handleSubmit() {
    const submitBtn = /** @type {HTMLButtonElement} */ (document.getElementById('submit-btn'));
    const errorsEl = /** @type {HTMLElement} */ (document.getElementById('submit-errors'));

    const url = submitBtn.dataset.submitUrl || '';

    submitBtn.disabled = true;
    errorsEl.hidden = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    })
        .then(response => response.json().then(body => ({ ok: response.ok, body })))
        .then(({ ok, body }) => {
            if (ok && body.redirect) {
                window.location.href = body.redirect;
            } else if (!ok) {
                errorsEl.textContent = body.error ? trans(body.error) : trans('common.error');
                errorsEl.hidden = false;
            }
        })
        .catch(() => {
            errorsEl.textContent = trans('common.error');
            errorsEl.hidden = false;
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
}

/**
 * @param {string} errorKey
 * @returns {string}
 */
function translateError(errorKey) {
    const parts = errorKey.split(':');
    const key = parts[0];
    const params = parts.slice(1);

    let message = trans(key);
    params.forEach((param, index) => {
        message = message.replace(`%${index + 1}%`, param);
    });

    return message;
}

document.addEventListener('turbo:load', init);
