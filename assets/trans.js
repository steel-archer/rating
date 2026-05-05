import translations from './translations.js';

export function trans(key) {
    return translations[key] || key;
}
