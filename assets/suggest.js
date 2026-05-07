import { initSuggestBehavior } from './suggest-base.js';

function initSuggest(wrapper) {
    const input = wrapper.querySelector('[data-suggest-input]');
    const hidden = wrapper.querySelector('[data-suggest-hidden]');
    const dropdown = wrapper.querySelector('[data-suggest-dropdown]');
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
    document.querySelectorAll('[data-suggest-url]').forEach(initSuggest);
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.suggest-wrapper')) {
        document.querySelectorAll('[data-suggest-dropdown]').forEach(d => {
            d.innerHTML = '';
            d.hidden = true;
        });
    }
});

document.addEventListener('turbo:load', initAllSuggests);
document.addEventListener('turbo:frame-load', initAllSuggests);
