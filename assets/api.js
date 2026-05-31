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
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: data !== undefined ? JSON.stringify(data) : undefined,
    }).then(response =>
        response.json()
            .then(body => ({ok: response.ok, body}))
            .catch(() => ({ok: false, body: /** @type {Record<string, any>} */ ({error: response.status === 404 ? 'common.not_found' : 'common.error'})})),
    );
}

/**
 * @param {HTMLElement} statusEl
 * @param {string|null} errorKey
 */
export function showError(statusEl, errorKey) {
    statusEl.textContent = errorKey
        ? transError(errorKey)
        : trans('common.error');
    statusEl.className = 'save-status save-status-error';
    statusEl.hidden = false;
}

/**
 * @param {string} errorKey
 * @returns {string}
 */
function transError(errorKey) {
    const colonIndex = errorKey.indexOf(':');
    if (colonIndex === -1) {
        return trans(errorKey);
    }
    const key = errorKey.substring(0, colonIndex);
    const param = errorKey.substring(colonIndex + 1);
    return trans(key).replace('%name%', param);
}
