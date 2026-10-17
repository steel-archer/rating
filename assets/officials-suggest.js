// @ts-check
import { initSuggestBehavior } from './suggest-base.js';
import { trans } from './trans.js';

/** @type {WeakSet<HTMLElement>} */
const initializedWrappers = new WeakSet();

/**
 * Appends a chosen existing player to the role group as a removable entry
 * carrying a hidden input named after the role, so plain form-based pages
 * (session claim host, venue representatives) keep working unchanged.
 *
 * @param {HTMLElement} group
 * @param {string} fieldName
 * @param {{id: string|number, name: string}} item
 */
function appendExistingOfficial(group, fieldName, item) {
    const entry = document.createElement('div');
    entry.className = 'official-entry';

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = `${fieldName}[]`;
    hidden.value = String(item.id);

    const link = document.createElement('a');
    link.href = `/player/${item.id}`;
    link.textContent = item.name;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-remove';
    btn.textContent = '\u00d7';

    entry.append(hidden, link, btn);
    group.appendChild(entry);
}

/**
 * Appends a brand new (not yet saved) player to the role group. Its data is
 * carried in dataset attributes and serialized on form submit by tournament-edit.js.
 *
 * @param {HTMLElement} group
 * @param {{lastName: string, firstName: string, patronymic: string, townId: string, townName: string, countryId: string, label: string}} data
 */
function appendNewOfficial(group, data) {
    const entry = document.createElement('div');
    entry.className = 'official-entry';
    entry.dataset.newPlayer = '1';
    entry.dataset.lastName = data.lastName;
    entry.dataset.firstName = data.firstName;
    entry.dataset.patronymic = data.patronymic;
    entry.dataset.townId = data.townId;
    entry.dataset.townName = data.townName;
    entry.dataset.countryId = data.countryId;

    const span = document.createElement('span');
    span.textContent = data.label;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-remove';
    btn.textContent = '\u00d7';

    entry.append(span, btn);
    group.appendChild(entry);
}

/**
 * @param {HTMLElement} wrapper
 */
function initOfficialsSuggest(wrapper) {
    const input = /** @type {HTMLInputElement|null} */ (wrapper.querySelector('[data-suggest-input]'));
    const dropdown = /** @type {HTMLElement|null} */ (wrapper.querySelector('[data-suggest-dropdown]'));
    const apiUrl = wrapper.dataset.suggestUrl;
    const group = /** @type {HTMLElement|null} */ (wrapper.closest('.form-group')?.querySelector('.officials-group'));
    if (!input || !dropdown || !apiUrl || !group || initializedWrappers.has(wrapper)) {
        return;
    }
    const fieldName = /** @type {string} */ (group.dataset.role);
    initializedWrappers.add(wrapper);
    wrapper.dataset.suggestInit = '1';

    initSuggestBehavior(input, dropdown, apiUrl, (item) => {
        const existing = group.querySelectorAll(`input[value="${item.id}"]`);
        if (existing.length > 0) {
            input.value = '';
            return;
        }

        const isSingle = group.dataset.single !== undefined;
        if (isSingle && group.children.length > 0) {
            input.value = '';
            return;
        }

        appendExistingOfficial(group, fieldName, item);

        input.value = '';

        if (isSingle) {
            input.disabled = true;
        }
    });

    if (group.dataset.single !== undefined && group.children.length > 0) {
        input.disabled = true;
    }
}

/**
 * Wires the "create a new player" sub-form for a single tournament role group.
 *
 * @param {HTMLElement} formGroup
 */
function initNewOfficialForm(formGroup) {
    const toggleBtn = /** @type {HTMLElement|null} */ (formGroup.querySelector('[data-official-toggle-new]'));
    const newForm = /** @type {HTMLElement|null} */ (formGroup.querySelector('[data-official-new-player]'));
    const addBtn = /** @type {HTMLElement|null} */ (formGroup.querySelector('[data-official-add-new]'));
    const group = /** @type {HTMLElement|null} */ (formGroup.querySelector('.officials-group'));
    if (!toggleBtn || !newForm || !addBtn || !group || initializedWrappers.has(newForm)) {
        return;
    }
    initializedWrappers.add(newForm);

    const lastNameInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-last-name]'));
    const firstNameInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-first-name]'));
    const patronymicInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-patronymic]'));
    const countryInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-country]'));
    const countryIdInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-country-id]'));
    const townInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-town]'));
    const townIdInput = /** @type {HTMLInputElement} */ (newForm.querySelector('[data-official-new-town-id]'));
    const status = /** @type {HTMLElement} */ (newForm.querySelector('[data-official-new-status]'));

    toggleBtn.addEventListener('click', () => {
        newForm.hidden = !newForm.hidden;
    });

    addBtn.addEventListener('click', () => {
        const lastName = lastNameInput.value.trim();
        const firstName = firstNameInput.value.trim();
        const patronymic = patronymicInput.value.trim();
        const countryId = countryIdInput.value;
        const townId = townIdInput.value;
        const townName = !townId ? townInput.value.trim() : '';

        if (!lastName) {
            status.textContent = trans('player.error.last_name_required');
            status.hidden = false;
            return;
        }

        if (!firstName) {
            status.textContent = trans('player.error.first_name_required');
            status.hidden = false;
            return;
        }

        // A town (picked or typed) always needs a country attached to it.
        if ((townId || townName) && !countryId) {
            status.textContent = trans('player.error.country_required');
            status.hidden = false;
            return;
        }

        status.hidden = true;

        const countryName = countryId ? countryInput.value.trim() : '';
        const townLabel = townId ? townInput.value.trim() : townName;
        const location = [townLabel, countryName].filter(Boolean).join(', ');
        const name = [lastName, firstName, patronymic].filter(Boolean).join(' ');

        appendNewOfficial(group, {
            lastName,
            firstName,
            patronymic,
            townId,
            townName,
            countryId,
            label: location ? `${name} (${location})` : name,
        });

        lastNameInput.value = '';
        firstNameInput.value = '';
        patronymicInput.value = '';
        countryInput.value = '';
        countryIdInput.value = '';
        countryIdInput.dispatchEvent(new Event('change'));
        townInput.value = '';
        townIdInput.value = '';
        newForm.hidden = true;
    });
}

function initAllOfficialsSuggests() {
    document.querySelectorAll('.officials-group + .suggest-wrapper[data-suggest-url]')
        .forEach(element => initOfficialsSuggest(/** @type {HTMLElement} */ (element)));
    document.querySelectorAll('.form-group:has([data-official-new-player])')
        .forEach(element => initNewOfficialForm(/** @type {HTMLElement} */ (element)));
}

document.addEventListener('click', (event) => {
    if (!/** @type {HTMLElement} */ (event.target).closest('.suggest-wrapper')) {
        document.querySelectorAll('.officials-group + .suggest-wrapper [data-suggest-dropdown]').forEach(dropdown => {
            /** @type {HTMLElement} */ (dropdown).innerHTML = '';
            /** @type {HTMLElement} */ (dropdown).hidden = true;
        });
    }

    const removeBtn = /** @type {HTMLElement} */ (event.target).closest('.official-entry .btn-remove');
    if (removeBtn) {
        const entry = removeBtn.closest('.official-entry');
        const group = entry?.closest('.officials-group');
        entry?.remove();

        if (group && /** @type {HTMLElement} */ (group).dataset.single !== undefined && group.children.length === 0) {
            const wrapper = group.closest('.form-group')?.querySelector('.suggest-wrapper [data-suggest-input]');
            if (wrapper) {
                /** @type {HTMLInputElement} */ (wrapper).disabled = false;
            }
        }
    }
});

document.addEventListener('turbo:load', initAllOfficialsSuggests);
document.addEventListener('turbo:frame-load', initAllOfficialsSuggests);
