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

    let debounceTimer;

    input.addEventListener('input', () => {
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

        const playerId = item.dataset.id;
        const existing = group.querySelectorAll(`input[value="${playerId}"]`);
        if (existing.length > 0) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
            input.value = '';
            return;
        }

        const entry = document.createElement('div');
        entry.className = 'official-entry';
        entry.innerHTML = `<input type="hidden" name="${fieldName}[]" value="${playerId}"><a href="/player/${playerId}">${item.textContent}</a><button type="button" class="btn-remove" onclick="this.parentElement.remove()">×</button>`;
        group.appendChild(entry);

        dropdown.innerHTML = '';
        dropdown.hidden = true;
        input.value = '';
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.suggest-wrapper')) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
        }
    });
}

function initAllOfficialsSuggests() {
    document.querySelectorAll('.officials-group + .suggest-wrapper[data-suggest-url]').forEach(initOfficialsSuggest);
}

document.addEventListener('turbo:load', initAllOfficialsSuggests);
document.addEventListener('turbo:frame-load', initAllOfficialsSuggests);
