function initSuggest(wrapper) {
    const input = wrapper.querySelector('[data-suggest-input]');
    const hidden = wrapper.querySelector('[data-suggest-hidden]');
    const dropdown = wrapper.querySelector('[data-suggest-dropdown]');
    const apiUrl = wrapper.dataset.suggestUrl;
    if (!input || !hidden || !dropdown || !apiUrl || wrapper.dataset.suggestInit) {
        return;
    }
    wrapper.dataset.suggestInit = '1';

    let debounceTimer;

    input.addEventListener('input', () => {
        hidden.value = '';
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 2) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch(`${apiUrl}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(items => {
                    if (items.length === 0) {
                        dropdown.innerHTML = '';
                        dropdown.hidden = true;
                        return;
                    }
                    dropdown.innerHTML = items.map(item =>
                        `<div class="suggest-item" data-id="${item.id}">${item.name}</div>`
                    ).join('');
                    dropdown.hidden = false;
                });
        }, 200);
    });

    dropdown.addEventListener('click', (e) => {
        const item = e.target.closest('.suggest-item');
        if (!item) {
            return;
        }
        input.value = item.textContent;
        hidden.value = item.dataset.id;
        dropdown.innerHTML = '';
        dropdown.hidden = true;
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.suggest-wrapper')) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
        }
    });
}

function initAllSuggests() {
    document.querySelectorAll('[data-suggest-url]').forEach(initSuggest);
}

document.addEventListener('DOMContentLoaded', initAllSuggests);
document.addEventListener('turbo:frame-load', initAllSuggests);
