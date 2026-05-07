// @ts-check
import { initSuggestBehavior } from './suggest-base.js';

/**
 * @param {HTMLElement} wrapper
 */
function initSuggest(wrapper) {
    const input = /** @type {HTMLInputElement|null} */ (wrapper.querySelector('[data-suggest-input]'));
    const hidden = /** @type {HTMLInputElement|null} */ (wrapper.querySelector('[data-suggest-hidden]'));
    const dropdown = /** @type {HTMLElement|null} */ (wrapper.querySelector('[data-suggest-dropdown]'));
    const apiUrl = wrapper.dataset.suggestUrl;
    if (!input || !hidden || !dropdown || !apiUrl || wrapper.dataset.suggestInit) {
        return;
    }
    wrapper.dataset.suggestInit = '1';

    input.addEventListener('input', () => {
        hidden.value = '';
    });

    initSuggestBehavior(input, dropdown, apiUrl, (item) => {
        input.value = item.name;
        hidden.value = item.id;
    });
}

function initAllSuggests() {
    document.querySelectorAll('[data-suggest-url]').forEach(element => initSuggest(/** @type {HTMLElement} */ (element)));
}

document.addEventListener('click', (event) => {
    if (!/** @type {HTMLElement} */ (event.target).closest('.suggest-wrapper')) {
        document.querySelectorAll('[data-suggest-dropdown]').forEach(dropdown => {
            /** @type {HTMLElement} */ (dropdown).innerHTML = '';
            /** @type {HTMLElement} */ (dropdown).hidden = true;
        });
    }
});

document.addEventListener('turbo:load', initAllSuggests);
document.addEventListener('turbo:frame-load', initAllSuggests);
