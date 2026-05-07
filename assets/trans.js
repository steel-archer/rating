// @ts-check
import translations from './translations.js';

/**
 * @param {string} key
 * @returns {string}
 */
export function trans(key) {
    return translations[key] || key;
}
