// @ts-check
import { initSuggestBehavior } from './suggest-base.js';

/**
 * @param {HTMLElement} wrapper
 */
function initSuggest(wrapper) {
    const input = /** @type {HTMLInputElement|null} */ (wrapper.querySelector('[data-suggest-input]'));
    const hidden = /** @type {HTMLInputElement|null} */ (wrapper.querySelector('[data-suggest-hidden]'));
    const dropdown = /** @type {HTMLElement|null} */ (wrapper.querySelector('[data-suggest-dropdown]'));
    const apiUrl = wrapper.dataset.suggestUrl;
    if (!input || !dropdown || !apiUrl || wrapper.dataset.suggestInit) {
        return;
    }
    wrapper.dataset.suggestInit = '1';

    input.addEventListener('input', () => {
        if (hidden) {
            hidden.value = '';
        }
    });

    const countryHidden = resolveCountryHidden(wrapper);
    if (countryHidden) {
        // Reset the chosen town whenever the linked country changes.
        countryHidden.addEventListener('change', () => {
            input.value = '';
            if (hidden) {
                hidden.value = '';
            }
        });
    }

    // A fixed country id (e.g. moderator claim row) takes precedence over a linked field.
    const fixedCountryId = wrapper.dataset.suggestCountryId;
    let getExtraParams;
    if (fixedCountryId) {
        getExtraParams = () => ({ countryId: fixedCountryId });
    } else if (countryHidden) {
        getExtraParams = () => ({ countryId: countryHidden.value });
    }

    initSuggestBehavior(
        input,
        dropdown,
        apiUrl,
        (item) => {
            input.value = item.name;
            if (hidden) {
                hidden.value = item.id;
                hidden.dispatchEvent(new Event('change'));
            }
            delete input.dataset.townIsNew;
        },
        getExtraParams,
    );
}

/**
 * Resolves the country hidden field this suggest depends on, if any.
 * The `data-suggest-country` value is first tried as an element id (unambiguous
 * when several country/town pairs live in one form) and then as a field name
 * scoped to the enclosing form (used by list filters with a single pair).
 *
 * @param {HTMLElement} wrapper
 * @returns {HTMLInputElement|null}
 */
function resolveCountryHidden(wrapper) {
    const dependsOn = wrapper.dataset.suggestCountry;
    if (!dependsOn) {
        return null;
    }
    const byId = /** @type {HTMLInputElement|null} */ (document.getElementById(dependsOn));
    if (byId) {
        return byId;
    }
    const scope = wrapper.closest('form') ?? document;
    return /** @type {HTMLInputElement|null} */ (scope.querySelector(`[name="${dependsOn}"]`));
}

function initAllSuggests() {
    document.querySelectorAll('[data-suggest-url]').forEach(element => initSuggest(/** @type {HTMLElement} */ (element)));
}

document.addEventListener('click', (event) => {
    if (!/** @type {HTMLElement} */ (event.target).closest('.suggest-wrapper')) {
        document.querySelectorAll('[data-suggest-dropdown]').forEach(dropdown => {
            /** @type {HTMLElement} */ (dropdown).innerHTML = '';
            /** @type {HTMLElement} */ (dropdown).hidden = true;
        });
    }
});

document.addEventListener('turbo:load', initAllSuggests);
document.addEventListener('turbo:frame-load', initAllSuggests);
