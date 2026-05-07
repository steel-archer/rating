// @ts-check
import { debounce } from './debounce.js';

/**
 * @param {HTMLInputElement} input
 * @param {HTMLElement} dropdown
 * @param {string} apiUrl
 * @param {function({id: string, name: string}): void} onSelect
 */
export function initSuggestBehavior(input, dropdown, apiUrl, onSelect) {
    const search = debounce(/** @param {string} query */ (query) => {
        fetch(`${apiUrl}?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
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
            })
            .catch(() => {
                dropdown.innerHTML = '';
                dropdown.hidden = true;
            });
    }, 200);

    input.addEventListener('input', () => {
        const query = input.value.trim();
        if (query.length < 2) {
            dropdown.innerHTML = '';
            dropdown.hidden = true;
            return;
        }
        search(query);
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
