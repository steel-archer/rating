// @ts-check
import { debounce } from './debounce.js';

/**
 * @param {HTMLInputElement} input
 * @param {HTMLElement} dropdown
 * @param {string} apiUrl
 * @param {function({id: string, name: string, townName?: string|null, countryName?: string|null}): void} onSelect
 * @param {function(): Object<string, string>} [getExtraParams]
 */
export function initSuggestBehavior(input, dropdown, apiUrl, onSelect, getExtraParams) {
    const search = debounce(/** @param {string} query */ (query) => {
        const params = new URLSearchParams({ q: query });
        if (getExtraParams) {
            Object.entries(getExtraParams()).forEach(([key, value]) => {
                if (value) {
                    params.set(key, value);
                }
            });
        }
        fetch(`${apiUrl}?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(/** @param {Array<{id: string, name: string, townName?: string|null, countryName?: string|null}>} items */ (items) => {
                if (items.length === 0) {
                    dropdown.innerHTML = '';
                    dropdown.hidden = true;
                    return;
                }
                dropdown.replaceChildren(...items.map(item => {
                    const div = document.createElement('div');
                    div.className = 'suggest-item';
                    div.dataset.id = item.id;
                    if (item.townName) {
                        div.dataset.townName = item.townName;
                    }
                    if (item.countryName) {
                        div.dataset.countryName = item.countryName;
                    }
                    // Location (if any) is shown as a muted hint so players with the
                    // same name can be told apart, while the item text stays the name.
                    const location = [item.townName, item.countryName].filter(Boolean).join(', ');
                    div.textContent = item.name;
                    if (location) {
                        const hint = document.createElement('span');
                        hint.className = 'suggest-item-hint';
                        hint.textContent = ` (${location})`;
                        div.appendChild(hint);
                    }
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
        const el = /** @type {HTMLElement} */ (item);
        // Take the plain name from the first text node, not the location hint span.
        const name = el.childNodes[0]?.textContent ?? el.textContent ?? '';
        onSelect({
            id: /** @type {string} */ (el.dataset.id),
            name,
            townName: el.dataset.townName ?? null,
            countryName: el.dataset.countryName ?? null,
        });
        dropdown.innerHTML = '';
        dropdown.hidden = true;
    });
}
