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
        input.value = item.textContent;
        hidden.value = item.dataset.id;
        dropdown.innerHTML = '';
        dropdown.hidden = true;
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
