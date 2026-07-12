// @ts-check
import translations from './translations.js';

/**
 * @param {string} key
 * @param {Object<string, string|number>} [params]
 * @returns {string}
 */
export function trans(key, params) {
    let text = translations[key] || key;
    if (params) {
        for (const [k, v] of Object.entries(params)) {
            text = text.replaceAll(k, String(v));
        }
    }
    return text;
}
