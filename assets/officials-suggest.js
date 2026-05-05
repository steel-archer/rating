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
                    dropdown.replaceChildren(...items.map(item => {
                        const div = document.createElement('div');
                        div.className = 'suggest-item';
                        div.dataset.id = item.id;
                        div.textContent = item.name;
                        return div;
                    }));
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

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = `${fieldName}[]`;
        hidden.value = playerId;

        const link = document.createElement('a');
        link.href = `/player/${playerId}`;
        link.textContent = item.textContent;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-remove';
        btn.textContent = '\u00d7';

        entry.append(hidden, link, btn);
        group.appendChild(entry);

        dropdown.innerHTML = '';
        dropdown.hidden = true;
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
