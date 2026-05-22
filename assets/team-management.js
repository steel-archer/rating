// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

function initTeamManagement() {
    initUpdateForm();
    initAddPlayer();
    initAddPlayerQuick();
    initRemovePlayer();
    initSetCaptain();
    initLeaveTeam();
}

function initUpdateForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('team-update-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('team-update-status'));
        const nameInput = /** @type {HTMLInputElement} */ (document.getElementById('team-name'));
        const townIdInput = /** @type {HTMLInputElement} */ (document.getElementById('team-town-id'));

        const townId = parseInt(townIdInput.value) || parseInt(form.dataset.initialTownId || '0');
        if (!nameInput.value.trim() || !townId) {
            showError(status, 'team_management.error.fill_all_fields');
            return;
        }

        apiPost(url, { name: nameInput.value.trim(), townId })
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => showError(status, null));
    });
}

function initAddPlayer() {
    const btn = /** @type {HTMLButtonElement|null} */ (document.getElementById('add-player-btn'));
    if (!btn) {
        return;
    }

    btn.addEventListener('click', () => {
        const playerIdInput = /** @type {HTMLInputElement} */ (document.getElementById('add-player-id'));
        const status = /** @type {HTMLElement} */ (document.getElementById('roster-status'));
        const playerId = parseInt(playerIdInput.value);

        if (!playerId) {
            showError(status, 'team_management.error.select_player');
            return;
        }

        apiPost('/my/team/add-player', { playerId })
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => showError(status, null));
    });
}

function initAddPlayerQuick() {
    document.addEventListener('click', (event) => {
        const btn = /** @type {HTMLElement} */ (event.target).closest('[data-add-player-quick]');
        if (!btn) {
            return;
        }

        const playerId = parseInt(/** @type {HTMLElement} */ (btn).dataset.addPlayerQuick || '0');
        if (!playerId) {
            return;
        }

        const status = /** @type {HTMLElement} */ (document.getElementById('roster-status'));

        apiPost('/my/team/add-player', { playerId })
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => showError(status, null));
    });
}

function initRemovePlayer() {
    document.addEventListener('click', (event) => {
        const btn = /** @type {HTMLElement} */ (event.target).closest('[data-remove-player]');
        if (!btn) {
            return;
        }

        const playerId = parseInt(/** @type {HTMLElement} */ (btn).dataset.removePlayer || '0');
        if (!playerId || !confirm(trans('team_management.confirm_remove'))) {
            return;
        }

        const status = /** @type {HTMLElement} */ (document.getElementById('roster-status'));

        apiPost('/my/team/remove-player', { playerId })
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => showError(status, null));
    });
}

function initSetCaptain() {
    document.addEventListener('click', (event) => {
        const btn = /** @type {HTMLElement} */ (event.target).closest('[data-set-captain]');
        if (!btn) {
            return;
        }

        const playerId = parseInt(/** @type {HTMLElement} */ (btn).dataset.setCaptain || '0');
        if (!playerId || !confirm(trans('team_management.confirm_captain'))) {
            return;
        }

        const status = /** @type {HTMLElement} */ (document.getElementById('roster-status'));

        apiPost('/my/team/set-captain', { playerId })
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => showError(status, null));
    });
}

function initLeaveTeam() {
    const btn = /** @type {HTMLButtonElement|null} */ (document.getElementById('leave-team-btn'));
    if (!btn) {
        return;
    }

    btn.addEventListener('click', () => {
        if (!confirm(trans('team_management.confirm_leave'))) {
            return;
        }

        const status = /** @type {HTMLElement} */ (document.getElementById('leave-status'));

        apiPost('/my/team/leave', {})
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = '/';
                } else {
                    showError(status, body.error);
                }
            })
            .catch(() => showError(status, null));
    });
}

document.addEventListener('turbo:load', initTeamManagement);
