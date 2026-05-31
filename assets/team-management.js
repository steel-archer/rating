// @ts-check
import { trans } from './trans.js';
import { apiPost, showError } from './api.js';

/** @type {Array<{id: number, name: string}>} */
let pendingAdds = [];

/** @type {Array<number>} */
let pendingRemoves = [];

/** @type {number|null} */
let pendingCaptainId = null;

function initTeamManagement() {
    pendingAdds = [];
    pendingRemoves = [];
    pendingCaptainId = null;
    initUpdateForm();
    initAddPlayer();
    initLeaveTeam();
    initSaveSquad();
    updateSaveSection();
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
        const playerNameInput = /** @type {HTMLInputElement} */ (document.getElementById('add-player-input'));
        const playerId = parseInt(playerIdInput.value);

        if (!playerId) {
            const status = /** @type {HTMLElement} */ (document.getElementById('squad-status'));
            showError(status, 'team_management.error.select_player');
            return;
        }

        if (pendingAdds.some(p => p.id === playerId)) {
            return;
        }

        // Check if player is already in the current squad (server-rendered row)
        if (document.querySelector(`[data-player-id="${playerId}"]`)
            && !pendingRemoves.includes(playerId)
        ) {
            const status = /** @type {HTMLElement} */ (document.getElementById('squad-status'));
            showError(status, 'team_management.error.already_in_team:' + playerNameInput.value);
            return;
        }

        // Client-side max players check
        const currentCount = document.querySelectorAll('[data-player-id]').length;
        const pendingCount = currentCount - pendingRemoves.length + pendingAdds.length + 1;
        if (pendingCount > 8) {
            const status = /** @type {HTMLElement} */ (document.getElementById('squad-status'));
            showError(status, 'team_management.error.max_players');
            return;
        }

        // If player was pending removal, just undo the removal
        const removeIndex = pendingRemoves.indexOf(playerId);
        if (removeIndex !== -1) {
            pendingRemoves.splice(removeIndex, 1);
            markPlayerRow(playerId, false);
        } else {
            pendingAdds.push({ id: playerId, name: playerNameInput.value });
            addPendingPlayerRow(playerId, playerNameInput.value);
        }

        playerIdInput.value = '';
        playerNameInput.value = '';
        updateSaveSection();
    });
}

function initSaveSquad() {
    const saveBtn = /** @type {HTMLButtonElement|null} */ (document.getElementById('save-squad-btn'));
    const cancelBtn = /** @type {HTMLButtonElement|null} */ (document.getElementById('cancel-squad-btn'));
    if (!saveBtn || !cancelBtn) {
        return;
    }

    saveBtn.addEventListener('click', () => {
        if (pendingCaptainId !== null && !confirm(trans('team_management.confirm_captain'))) {
            return;
        }

        saveBtn.disabled = true;
        const status = /** @type {HTMLElement} */ (document.getElementById('squad-status'));

        /** @type {Record<string, any>} */
        const payload = {
            addPlayerIds: pendingAdds.map(p => p.id),
            removePlayerIds: pendingRemoves,
        };
        if (pendingCaptainId !== null) {
            payload.newCaptainId = pendingCaptainId;
        }

        apiPost('/my/team/update-squad', payload)
            .then(({ok, body}) => {
                if (ok) {
                    pendingAdds = [];
                    pendingRemoves = [];
                    pendingCaptainId = null;
                    window.location.reload();
                } else {
                    saveBtn.disabled = false;
                    showError(status, body.error);
                }
            })
            .catch(() => {
                saveBtn.disabled = false;
                showError(status, null);
            });
    });

    cancelBtn.addEventListener('click', () => {
        pendingAdds = [];
        pendingRemoves = [];
        pendingCaptainId = null;
        window.location.reload();
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

/**
 * @param {number} playerId
 * @param {boolean} removed
 */
function markPlayerRow(playerId, removed) {
    const row = document.querySelector(`[data-remove-player="${playerId}"]`)?.closest('tr');
    if (!row) {
        return;
    }

    if (removed) {
        row.classList.add('row-pending-remove');
    } else {
        row.classList.remove('row-pending-remove');
    }
}

/**
 * @param {number} playerId
 * @param {string} playerName
 */
function addPendingPlayerRow(playerId, playerName) {
    const squadCard = document.getElementById('squad-save-section')?.closest('.card');
    const tbody = squadCard?.querySelector('table tbody');
    if (!tbody) {
        return;
    }

    const row = document.createElement('tr');
    row.classList.add('row-pending-add');
    row.dataset.pendingPlayerId = String(playerId);
    row.innerHTML = `
        <td>${escapeHtml(playerName)}</td>
        <td></td>
        <td><button type="button" class="btn-remove" data-undo-add="${playerId}" title="${trans('team_management.remove_player')}">×</button></td>
    `;
    tbody.appendChild(row);
}

/**
 * @param {number} playerId
 */
function removePendingPlayerRow(playerId) {
    const row = document.querySelector(`[data-pending-player-id="${playerId}"]`);
    if (row) {
        row.remove();
    }
}

/**
 * @param {number} playerId
 */
function setCaptainPending(playerId) {
    pendingCaptainId = playerId;

    // Remove current captain badge first
    const currentCaptainBadge = document.querySelector('.badge.badge-published');
    if (currentCaptainBadge) {
        const currentCaptainRow = currentCaptainBadge.closest('tr');
        if (currentCaptainRow) {
            const captainCell = currentCaptainRow.querySelectorAll('td')[1];
            captainCell.innerHTML = '';
        }
    }

    // Set new captain badge and hide other captain buttons
    document.querySelectorAll('[data-set-captain]').forEach(btn => {
        const row = btn.closest('tr');
        if (!row) {
            return;
        }
        const btnPlayerId = parseInt(/** @type {HTMLElement} */ (btn).dataset.setCaptain || '0');
        const captainCell = row.querySelectorAll('td')[1];
        if (btnPlayerId === playerId) {
            captainCell.innerHTML = `<span class="badge badge-published">${trans('squad.captain')}</span>`;
        } else {
            captainCell.innerHTML = '';
        }
    });

    updateSaveSection();
}

function updateSaveSection() {
    const section = document.getElementById('squad-save-section');
    if (!section) {
        return;
    }

    const hasChanges = pendingAdds.length > 0 || pendingRemoves.length > 0 || pendingCaptainId !== null;
    section.hidden = !hasChanges;

    // Hide error when changes are modified
    const status = document.getElementById('squad-status');
    if (status) {
        status.hidden = true;
    }
}

/**
 * @param {string} text
 * @returns {string}
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('click', (event) => {
    const removeBtn = /** @type {HTMLElement} */ (event.target).closest('[data-remove-player]');
    if (removeBtn) {
        const playerId = parseInt(/** @type {HTMLElement} */ (removeBtn).dataset.removePlayer || '0');
        if (!playerId) {
            return;
        }

        if (pendingRemoves.includes(playerId)) {
            pendingRemoves = pendingRemoves.filter(id => id !== playerId);
            markPlayerRow(playerId, false);
        } else {
            pendingRemoves.push(playerId);
            markPlayerRow(playerId, true);
        }
        updateSaveSection();
        return;
    }

    const undoAddBtn = /** @type {HTMLElement} */ (event.target).closest('[data-undo-add]');
    if (undoAddBtn) {
        const playerId = parseInt(/** @type {HTMLElement} */ (undoAddBtn).dataset.undoAdd || '0');
        if (!playerId) {
            return;
        }

        pendingAdds = pendingAdds.filter(p => p.id !== playerId);
        removePendingPlayerRow(playerId);
        updateSaveSection();
        return;
    }

    const addQuickBtn = /** @type {HTMLElement} */ (event.target).closest('[data-add-player-quick]');
    if (addQuickBtn) {
        const playerId = parseInt(/** @type {HTMLElement} */ (addQuickBtn).dataset.addPlayerQuick || '0');
        if (!playerId) {
            return;
        }

        if (pendingAdds.some(p => p.id === playerId)) {
            return;
        }

        const currentCount = document.querySelectorAll('[data-player-id]').length;
        const pendingCount = currentCount - pendingRemoves.length + pendingAdds.length + 1;
        if (pendingCount > 8) {
            const status = /** @type {HTMLElement} */ (document.getElementById('squad-status'));
            showError(status, 'team_management.error.max_players');
            return;
        }

        const playerName = addQuickBtn.closest('tr')?.querySelector('td')?.textContent?.trim() || '';
        pendingAdds.push({ id: playerId, name: playerName });
        addPendingPlayerRow(playerId, playerName);
        addQuickBtn.closest('tr')?.classList.add('row-pending-add-source');
        updateSaveSection();
        return;
    }

    const captainBtn = /** @type {HTMLElement} */ (event.target).closest('[data-set-captain]');
    if (captainBtn) {
        const playerId = parseInt(/** @type {HTMLElement} */ (captainBtn).dataset.setCaptain || '0');
        if (!playerId) {
            return;
        }

        setCaptainPending(playerId);
    }
});

document.addEventListener('turbo:before-visit', (event) => {
    if (pendingAdds.length > 0 || pendingRemoves.length > 0 || pendingCaptainId !== null) {
        if (!confirm(trans('team_management.confirm_unsaved'))) {
            event.preventDefault();
        }
    }
});

window.addEventListener('beforeunload', (event) => {
    if (pendingAdds.length > 0 || pendingRemoves.length > 0 || pendingCaptainId !== null) {
        event.preventDefault();
    }
});

document.addEventListener('turbo:load', initTeamManagement);
