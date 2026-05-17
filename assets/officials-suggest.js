// @ts-check
import { initSuggestBehavior } from './suggest-base.js';

/** @type {WeakSet<HTMLElement>} */
const initializedWrappers = new WeakSet();

/**
 * @param {HTMLElement} wrapper
 */
function initOfficialsSuggest(wrapper) {
    const input = /** @type {HTMLInputElement|null} */ (wrapper.querySelector('[data-suggest-input]'));
    const dropdown = /** @type {HTMLElement|null} */ (wrapper.querySelector('[data-suggest-dropdown]'));
    const apiUrl = wrapper.dataset.suggestUrl;
    const group = /** @type {HTMLElement|null} */ (wrapper.closest('.form-group')?.querySelector('.officials-group'));
    if (!input || !dropdown || !apiUrl || !group || initializedWrappers.has(wrapper)) {
        return;
    }
    const fieldName = /** @type {string} */ (group.dataset.role);
    initializedWrappers.add(wrapper);
    wrapper.dataset.suggestInit = '1';

    initSuggestBehavior(input, dropdown, apiUrl, (item) => {
        const existing = group.querySelectorAll(`input[value="${item.id}"]`);
        if (existing.length > 0) {
            input.value = '';
            return;
        }

        const isSingle = group.dataset.single !== undefined;
        if (isSingle && group.children.length > 0) {
            input.value = '';
            return;
        }

        const entry = document.createElement('div');
        entry.className = 'official-entry';

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = `${fieldName}[]`;
        hidden.value = item.id;

        const link = document.createElement('a');
        link.href = `/player/${item.id}`;
        link.textContent = item.name;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-remove';
        btn.textContent = '\u00d7';

        entry.append(hidden, link, btn);
        group.appendChild(entry);

        input.value = '';

        if (isSingle) {
            input.disabled = true;
        }
    });

    if (group.dataset.single !== undefined && group.children.length > 0) {
        input.disabled = true;
    }
}

function initAllOfficialsSuggests() {
    document.querySelectorAll('.officials-group + .suggest-wrapper[data-suggest-url]')
        .forEach(element => initOfficialsSuggest(/** @type {HTMLElement} */ (element)));
}

document.addEventListener('click', (event) => {
    if (!/** @type {HTMLElement} */ (event.target).closest('.suggest-wrapper')) {
        document.querySelectorAll('.officials-group + .suggest-wrapper [data-suggest-dropdown]').forEach(dropdown => {
            /** @type {HTMLElement} */ (dropdown).innerHTML = '';
            /** @type {HTMLElement} */ (dropdown).hidden = true;
        });
    }

    const removeBtn = /** @type {HTMLElement} */ (event.target).closest('.official-entry .btn-remove');
    if (removeBtn) {
        const entry = removeBtn.closest('.official-entry');
        const group = entry?.closest('.officials-group');
        entry?.remove();

        if (group && /** @type {HTMLElement} */ (group).dataset.single !== undefined && group.children.length === 0) {
            const wrapper = group.closest('.form-group')?.querySelector('.suggest-wrapper [data-suggest-input]');
            if (wrapper) {
                /** @type {HTMLInputElement} */ (wrapper).disabled = false;
            }
        }
    }
});

document.addEventListener('turbo:load', initAllOfficialsSuggests);
document.addEventListener('turbo:frame-load', initAllOfficialsSuggests);
