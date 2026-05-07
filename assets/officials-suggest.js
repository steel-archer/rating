import { initSuggestBehavior } from './suggest-base.js';

const initializedWrappers = new WeakSet();

function initOfficialsSuggest(wrapper) {
    const input = wrapper.querySelector('[data-suggest-input]');
    const dropdown = wrapper.querySelector('[data-suggest-dropdown]');
    const apiUrl = wrapper.dataset.suggestUrl;
    const group = wrapper.closest('.form-group').querySelector('.officials-group');
    const fieldName = group.dataset.role;

    if (!input || !dropdown || !apiUrl || !group || initializedWrappers.has(wrapper)) {
        return;
    }
    initializedWrappers.add(wrapper);

    initSuggestBehavior(input, dropdown, apiUrl, (item) => {
        const existing = group.querySelectorAll(`input[value="${item.id}"]`);
        if (existing.length > 0) {
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
    });
}

function initAllOfficialsSuggests() {
    document.querySelectorAll('.officials-group + .suggest-wrapper[data-suggest-url]').forEach(initOfficialsSuggest);
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.suggest-wrapper')) {
        document.querySelectorAll('.officials-group + .suggest-wrapper [data-suggest-dropdown]').forEach(d => {
            d.innerHTML = '';
            d.hidden = true;
        });
    }

    const removeBtn = e.target.closest('.official-entry .btn-remove');
    if (removeBtn) {
        removeBtn.closest('.official-entry').remove();
    }
});

document.addEventListener('turbo:load', initAllOfficialsSuggests);
document.addEventListener('turbo:frame-load', initAllOfficialsSuggests);
