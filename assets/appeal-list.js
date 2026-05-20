// @ts-check
import { trans } from './trans.js';

let allAppealsExpanded = false;

document.addEventListener('click', (event) => {
    const appealToggle = /** @type {HTMLElement} */ (event.target).closest('[data-toggle-appeal]');
    if (appealToggle) {
        const row = appealToggle.closest('tr');
        if (!row) {
            return;
        }
        const details = row.querySelectorAll('.appeal-details');
        const isHidden = /** @type {HTMLElement} */ (details[0])?.hidden;
        details.forEach(el => /** @type {HTMLElement} */ (el).hidden = !isHidden);
        appealToggle.textContent = isHidden ? trans('squad.hide') : trans('squad.show');
        return;
    }

    const allAppealsToggle = /** @type {HTMLElement} */ (event.target).closest('[data-toggle-all-appeals]');
    if (allAppealsToggle) {
        allAppealsExpanded = !allAppealsExpanded;
        document.querySelectorAll('.appeal-details').forEach(el => /** @type {HTMLElement} */ (el).hidden = !allAppealsExpanded);
        document.querySelectorAll('[data-toggle-appeal]').forEach(btn => btn.textContent = allAppealsExpanded ? trans('squad.hide') : trans('squad.show'));
        allAppealsToggle.textContent = allAppealsExpanded ? trans('squad.hide_all') : trans('squad.show_all');
    }
});
