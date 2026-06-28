// @ts-check
import { apiPost, transError } from './api.js';

/**
 * @param {string} url
 * @param {HTMLButtonElement} btn
 * @param {object} [options]
 * @param {object} [options.data]
 * @param {function(): void} [options.onSuccess]
 * @param {function(Record<string, any>): void} [options.onError]
 */
export function buttonAction(url, btn, options = {}) {
    btn.disabled = true;

    apiPost(url, options.data)
        .then(({ok, body}) => {
            if (ok) {
                if (options.onSuccess) {
                    options.onSuccess();
                } else {
                    window.location.reload();
                }
            } else {
                btn.disabled = false;
                if (options.onError) {
                    options.onError(body);
                } else {
                    alert(transError(body.error));
                }
            }
        })
        .catch(() => {
            btn.disabled = false;
            alert(transError(null));
        });
}
