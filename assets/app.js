import './stimulus_bootstrap.js';
import './styles/app.css';
import './suggest.js';
import './officials-suggest.js';
import './tournament-edit.js';
import './sync-url.js';

let allExpanded = false;

document.addEventListener('click', (e) => {
    const singleToggle = e.target.closest('[data-toggle-squad]');
    if (singleToggle) {
        const list = singleToggle.parentElement.querySelector('.squad-list');
        const isHidden = list.hidden;
        list.hidden = !isHidden;
        singleToggle.textContent = isHidden ? 'сховати' : 'показати';
        return;
    }

    const allToggle = e.target.closest('[data-toggle-all-squads]');
    if (allToggle) {
        allExpanded = !allExpanded;
        document.querySelectorAll('.squad-list').forEach(l => l.hidden = !allExpanded);
        document.querySelectorAll('[data-toggle-squad]').forEach(b => b.textContent = allExpanded ? 'сховати' : 'показати');
        allToggle.textContent = allExpanded ? 'сховати всі' : 'показати всі';
    }
});
