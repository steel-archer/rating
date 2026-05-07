import { trans } from './trans.js';

/**
 * @param {string} url
 * @param {object|undefined} data
 * @returns {Promise<{ok: boolean, body: object}>}
 */
export function apiPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: data !== undefined ? JSON.stringify(data) : undefined,
    }).then(r => r.json().then(body => ({ok: r.ok, body})));
}

/**
 * @param {HTMLElement} statusEl
 * @param {string} errorKey
 */
export function showError(statusEl, errorKey) {
    statusEl.textContent = errorKey
        ? errorKey.split(' ').map(key => trans(key)).join('. ')
        : trans('common.error');
    statusEl.className = 'save-status save-status-error';
    statusEl.hidden = false;
}
