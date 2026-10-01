// @ts-check
import { trans } from './trans.js';

/**
 * Refreshes the navigation menu badges after a partial page update.
 *
 * The badges are rendered server-side in base.html.twig and tagged with
 * data-menu-badge keys ("moderation.*" / "my.*"). This fetches the current
 * counts and updates every badge in place, so a partial DOM update (without a
 * full reload) still keeps the menu in sync.
 *
 * @returns {Promise<void>}
 */
export function refreshMenuCounts() {
    return fetch('/api/menu-counts', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(response => (response.ok ? response.json() : null))
        .then(data => {
            if (!data) {
                return;
            }
            applyGroup('moderation', data.moderation);
            applyGroup('my', data.my);
        })
        .catch(() => {
            // A failed refresh must not break the page; the next full reload fixes it.
        });
}

/**
 * @param {string} group
 * @param {Record<string, number>|null} counts
 */
function applyGroup(group, counts) {
    if (counts === null) {
        return;
    }
    for (const [field, value] of Object.entries(counts)) {
        updateBadge(`${group}.${field}`, value);
    }
}

/**
 * @param {string} key
 * @param {number} value
 */
function updateBadge(key, value) {
    const badge = /** @type {HTMLElement|null} */ (
        document.querySelector(`[data-menu-badge="${key}"]`)
    );
    if (!badge) {
        return;
    }
    const label = trans('nav.pending_actions', { '%count%': value });
    badge.textContent = String(value);
    badge.title = label;
    badge.setAttribute('aria-label', label);
    badge.hidden = value <= 0;
}
