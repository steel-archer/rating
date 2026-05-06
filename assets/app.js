import './stimulus_bootstrap.js';
import './styles/app.css';
import './suggest.js';
import './officials-suggest.js';
import './tournament-edit.js';
import './venue-edit.js';
import './player-claim.js';
import './sync-url.js';
import { trans } from './trans.js';

let allExpanded = false;

document.addEventListener('click', (e) => {
    const singleToggle = e.target.closest('[data-toggle-squad]');
    if (singleToggle) {
        const list = singleToggle.parentElement.querySelector('.squad-list');
        const isHidden = list.hidden;
        list.hidden = !isHidden;
        singleToggle.textContent = isHidden ? trans('squad.hide') : trans('squad.show');
        return;
    }

    const allToggle = e.target.closest('[data-toggle-all-squads]');
    if (allToggle) {
        allExpanded = !allExpanded;
        document.querySelectorAll('.squad-list').forEach(l => l.hidden = !allExpanded);
        document.querySelectorAll('[data-toggle-squad]').forEach(b => b.textContent = allExpanded ? trans('squad.hide') : trans('squad.show'));
        allToggle.textContent = allExpanded ? trans('squad.hide_all') : trans('squad.show_all');
    }
});
