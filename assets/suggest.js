function initSuggest(inputId, hiddenId, dropdownId, apiUrl) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const dropdown = document.getElementById(dropdownId);
    if (!input || !hidden || !dropdown || input.dataset.suggestInit) return;
    input.dataset.suggestInit = '1';

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
        if (!item) return;
        input.value = item.textContent;
        hidden.value = item.dataset.id;
        dropdown.innerHTML = '';
        dropdown.hidden = true;
        input.closest('form')?.requestSubmit();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.suggest-wrapper')) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
        }
    });
}

function initAllSuggests() {
    initSuggest('town-search', 'town-id', 'town-dropdown', '/api/towns/suggest');
    initSuggest('country-search', 'country-id', 'country-dropdown', '/api/countries/suggest');
}

document.addEventListener('DOMContentLoaded', initAllSuggests);
document.addEventListener('turbo:frame-load', initAllSuggests);
