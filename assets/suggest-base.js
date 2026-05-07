/**
 * @param {HTMLInputElement} input
 * @param {HTMLElement} dropdown
 * @param {string} apiUrl
 * @param {function(object): void} onSelect
 */
export function initSuggestBehavior(input, dropdown, apiUrl, onSelect) {
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
        onSelect({ id: item.dataset.id, name: item.textContent });
        dropdown.innerHTML = '';
        dropdown.hidden = true;
    });
}
