// @ts-check

/**
 * Toggles the country/town location filters on the venue search form depending
 * on the selected venue mode. Online venues live in the dedicated "Online" town,
 * which is hidden from the town autocomplete, so location filters make no sense
 * in the "online only" mode. Disabled inputs are also skipped on form submit,
 * keeping stale town/country ids out of the query string.
 *
 * @param {HTMLSelectElement} select
 */
function syncVenueLocationFilters(select) {
    const row = select.closest('[data-venue-search]');
    if (!row) {
        return;
    }

    const onlineOnly = select.value === 'online';

    row.querySelectorAll('[data-venue-location]').forEach((wrapper) => {
        wrapper.querySelectorAll('input').forEach((input) => {
            const field = /** @type {HTMLInputElement} */ (input);
            field.disabled = onlineOnly;
            if (onlineOnly) {
                field.value = '';
            }
        });
    });
}

document.addEventListener('change', (event) => {
    const target = /** @type {HTMLElement} */ (event.target);
    if (target.matches('[data-venue-mode]')) {
        syncVenueLocationFilters(/** @type {HTMLSelectElement} */ (target));
    }
});
