// @ts-check
import { trans } from './trans.js';

/**
 * @param {string} url
 * @param {object} [data]
 * @returns {Promise<{ok: boolean, body: Record<string, any>}>}
 */
export function apiPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: data !== undefined ? JSON.stringify(data) : undefined,
    }).then(response =>
        response.json()
            .then(body => ({ok: response.ok, body}))
            .catch(() => ({ok: false, body: /** @type {Record<string, any>} */ ({error: response.status === 404 ? 'common.not_found' : 'common.error'})}))
    );
}

/**
 * @param {HTMLElement} statusEl
 * @param {string|null} errorKey
 */
export function showError(statusEl, errorKey) {
    statusEl.textContent = errorKey
        ? errorKey.split(' ').map(key => trans(key)).join('. ')
        : trans('common.error');
    statusEl.className = 'save-status save-status-error';
    statusEl.hidden = false;
}
