// @ts-check
import './stimulus_bootstrap.js';
import './styles/app.css';
import './officials-suggest.js';
import './player-claim.js';
import './session-claim.js';
import './suggest.js';
import './sync-url.js';
import './tournament-edit.js';
import './contacts.js';
import './contacts-popover.js';
import './venue-edit.js';
import { trans } from './trans.js';

let allExpanded = false;

document.addEventListener('click', (event) => {
    const singleToggle = /** @type {HTMLElement} */ (event.target).closest('[data-toggle-squad]');
    if (singleToggle) {
        const list = /** @type {HTMLElement} */ (singleToggle.parentElement?.querySelector('.squad-list'));
        const isHidden = list.hidden;
        list.hidden = !isHidden;
        singleToggle.textContent = isHidden ? trans('squad.hide') : trans('squad.show');
        return;
    }

    const allToggle = /** @type {HTMLElement} */ (event.target).closest('[data-toggle-all-squads]');
    if (allToggle) {
        allExpanded = !allExpanded;
        document.querySelectorAll('.squad-list').forEach(list => /** @type {HTMLElement} */ (list).hidden = !allExpanded);
        document.querySelectorAll('[data-toggle-squad]').forEach(btn => btn.textContent = allExpanded ? trans('squad.hide') : trans('squad.show'));
        allToggle.textContent = allExpanded ? trans('squad.hide_all') : trans('squad.show_all');
    }
});
