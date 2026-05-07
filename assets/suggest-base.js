// @ts-check

/**
 * @param {HTMLInputElement} input
 * @param {HTMLElement} dropdown
 * @param {string} apiUrl
 * @param {function({id: string, name: string}): void} onSelect
 */
export function initSuggestBehavior(input, dropdown, apiUrl, onSelect) {
    /** @type {ReturnType<typeof setTimeout>|undefined} */
    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();
        if (query.length < 2) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch(`${apiUrl}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(/** @param {Array<{id: string, name: string}>} items */ (items) => {
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

    dropdown.addEventListener('click', (event) => {
        const item = /** @type {HTMLElement} */ (event.target).closest('.suggest-item');
        if (!item) {
            return;
        }
        onSelect({ id: /** @type {string} */ (item.dataset.id), name: /** @type {string} */ (item.textContent) });
        dropdown.innerHTML = '';
        dropdown.hidden = true;
    });
}
