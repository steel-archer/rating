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
            .catch(() => {
                let error;
                if (response.status === 404) {
                    error = 'common.not_found';
                } else if (response.status === 422) {
                    error = 'common.validation_error';
                } else {
                    error = 'common.error';
                }
                return {ok: false, body: /** @type {Record<string, any>} */ ({error})};
            }),
    );
}

/**
 * @param {HTMLElement} statusEl
 * @param {string|null} errorKey
 */
export function showError(statusEl, errorKey) {
    statusEl.textContent = transError(errorKey);
    statusEl.className = 'save-status save-status-error';
    statusEl.hidden = false;
}

/**
 * @param {string|null|undefined} errorKey
 * @returns {string}
 */
export function transError(errorKey) {
    if (!errorKey) {
        return trans('common.error');
    }
    const colonIndex = errorKey.indexOf(':');
    const key = colonIndex === -1 ? errorKey : errorKey.substring(0, colonIndex);
    const translated = trans(key);
    if (translated === key) {
        return trans('common.error');
    }
    if (colonIndex !== -1) {
        return translated.replace('%name%', errorKey.substring(colonIndex + 1));
    }
    return translated;
}
