// @ts-check
import { trans } from './trans.js';
import { apiPost } from './api.js';

/** @type {Array<{id: number|null, name: string, lastName?: string, firstName?: string, patronymic?: string, townId?: number|null}>} */
const selectedPlayers = [];

/** @type {number|null} */
let captainId = null;

/** @type {number|null} */
let baseSquadCaptainId = null;

/**
 * @param {string} errorKey
 * @returns {string}
 */
function transError(errorKey) {
    const colonIndex = errorKey.indexOf(':');
    if (colonIndex === -1) {
        return trans(errorKey);
    }
    const key = errorKey.substring(0, colonIndex);
    const param = errorKey.substring(colonIndex + 1);
    return trans(key).replace('%name%', param);
}

function initSquadForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('squad-form'));
    if (!form) {
        return;
    }

    selectedPlayers.length = 0;
    captainId = null;
    baseSquadCaptainId = null;

    const isEditMode = form.dataset.editMode === '1';

    if (!isEditMode) {
        initTeamModeToggle();
        initTeamSelect(form);
    } else {
        const teamIdInput = /** @type {HTMLInputElement|null} */ (document.getElementById('team-id'));
        if (teamIdInput && teamIdInput.value) {
            loadTeamPlayers(form, parseInt(teamIdInput.value));
        }

        const editPlayersJson = form.dataset.editPlayers;
        if (editPlayersJson) {
            const editPlayers = JSON.parse(editPlayersJson);
            editPlayers.forEach(/** @param {{id: number, name: string, isCaptain: boolean}} p */ (p) => {
                selectedPlayers.push({ id: p.id, name: p.name });
                if (p.isCaptain) {
                    captainId = p.id;
                }
            });
            renderPlayers();
        }
    }

    initPlayerSearch(form);
    initNewPlayerForm();
    initFormSubmit(form);
}

function initTeamModeToggle() {
    const radios = /** @type {NodeListOf<HTMLInputElement>} */ (document.querySelectorAll('input[name="teamMode"]'));
    const existingSection = /** @type {HTMLElement} */ (document.getElementById('existing-team-section'));
    const newSection = /** @type {HTMLElement} */ (document.getElementById('new-team-section'));

    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            const isNew = radio.value === 'new' && radio.checked;
            existingSection.hidden = isNew;
            newSection.hidden = !isNew;
            /** @type {HTMLElement} */ (document.getElementById('squad-suggestions')).hidden = true;

            const teamStatus = /** @type {HTMLElement|null} */ (document.getElementById('new-team-status'));
            if (teamStatus) {
                teamStatus.hidden = true;
            }
        });
    });
}

/**
 * @param {HTMLFormElement} form
 */
function initTeamSelect(form) {
    const teamIdInput = /** @type {HTMLInputElement} */ (document.getElementById('team-id'));

    teamIdInput.addEventListener('change', () => {
        const teamId = teamIdInput.value;
        if (teamId) {
            loadTeamPlayers(form, parseInt(teamId));
        } else {
            /** @type {HTMLElement} */ (document.getElementById('squad-suggestions')).hidden = true;
        }
    });
}

/**
 * @param {HTMLFormElement} form
 * @param {number} teamId
 */
function loadTeamPlayers(form, teamId) {
    const urlTemplate = form.dataset.teamPlayersUrl || '';
    const url = urlTemplate.replace('__TEAM_ID__', String(teamId));

    fetch(url)
        .then(response => response.json())
        .then(/** @param {Array<{id: number, name: string, group: string, isCaptain?: boolean}>} players */ (players) => {
            const suggestionsEl = /** @type {HTMLElement} */ (document.getElementById('squad-suggestions'));
            const baseSection = /** @type {HTMLElement} */ (document.getElementById('base-squad-section'));
            const seasonSection = /** @type {HTMLElement} */ (document.getElementById('season-players-section'));
            const baseList = /** @type {HTMLElement} */ (document.getElementById('base-squad-players'));
            const seasonList = /** @type {HTMLElement} */ (document.getElementById('season-players'));

            const basePlayers = players.filter(p => p.group === 'base');
            const seasonPlayers = players.filter(p => p.group === 'season');

            baseList.innerHTML = '';
            seasonList.innerHTML = '';

            basePlayers.forEach(player => {
                baseList.appendChild(createSuggestionRow(player));
            });

            seasonPlayers.forEach(player => {
                seasonList.appendChild(createSuggestionRow(player));
            });

            // Remember base squad captain id
            const baseCaptain = basePlayers.find(p => p.isCaptain);
            baseSquadCaptainId = baseCaptain ? baseCaptain.id : null;

            // Auto-select base squad captain if no captain chosen yet
            if (captainId === null && baseCaptain && selectedPlayers.some(p => p.id === baseCaptain.id)) {
                captainId = baseCaptain.id;
                renderPlayers();
            }

            baseSection.hidden = basePlayers.length === 0;
            seasonSection.hidden = seasonPlayers.length === 0;
            suggestionsEl.hidden = basePlayers.length === 0 && seasonPlayers.length === 0;
            syncSuggestionRows();
        })
        .catch(() => {
            /** @type {HTMLElement} */ (document.getElementById('squad-suggestions')).hidden = true;
        });
}

/**
 * @param {{id: number, name: string}} player
 * @returns {HTMLElement}
 */
function createSuggestionRow(player) {
    const tr = document.createElement('tr');
    tr.dataset.playerId = String(player.id);

    const tdAdd = document.createElement('td');
    const addBtn = document.createElement('button');
    addBtn.type = 'button';
    addBtn.className = 'btn-add-player';
    addBtn.textContent = '+';
    addBtn.addEventListener('click', () => {
        addPlayer({ id: player.id, name: player.name });
    });
    tdAdd.appendChild(addBtn);

    const tdName = document.createElement('td');
    tdName.textContent = player.name;

    tr.append(tdAdd, tdName);
    return tr;
}

function syncSuggestionRows() {
    document.querySelectorAll('#base-squad-players tr, #season-players tr').forEach(row => {
        const tr = /** @type {HTMLElement} */ (row);
        const playerId = parseInt(tr.dataset.playerId || '0');
        tr.hidden = selectedPlayers.some(p => p.id === playerId);
    });
}

/**
 * @param {HTMLFormElement} form
 */
function initPlayerSearch(form) {
    const dropdown = /** @type {HTMLElement|null} */ (form.querySelector('#player-search + [data-suggest-dropdown], #player-search ~ [data-suggest-dropdown]'));
    if (!dropdown) {
        return;
    }

    // Hide already selected players from suggest dropdown
    const observer = new MutationObserver(() => {
        dropdown.querySelectorAll('.suggest-item').forEach(item => {
            const el = /** @type {HTMLElement} */ (item);
            const id = parseInt(el.dataset.id || '0');
            el.hidden = selectedPlayers.some(p => p.id === id);
        });
    });
    observer.observe(dropdown, { childList: true });

    dropdown.addEventListener('click', (event) => {
        const item = /** @type {HTMLElement} */ (event.target).closest('.suggest-item');
        if (!item) {
            return;
        }
        const id = parseInt(/** @type {string} */ (item.dataset.id));
        const name = /** @type {string} */ (item.textContent);
        addPlayer({ id, name });
        const input = /** @type {HTMLInputElement} */ (document.getElementById('player-search'));
        setTimeout(() => {
            input.value = '';
        }, 0);
        dropdown.innerHTML = '';
        dropdown.hidden = true;
    });
}

function initNewPlayerForm() {
    const toggleBtn = /** @type {HTMLElement} */ (document.getElementById('toggle-new-player'));
    const newPlayerForm = /** @type {HTMLElement} */ (document.getElementById('new-player-form'));
    const addBtn = /** @type {HTMLElement} */ (document.getElementById('add-new-player-btn'));

    toggleBtn.addEventListener('click', () => {
        newPlayerForm.hidden = !newPlayerForm.hidden;
    });

    addBtn.addEventListener('click', () => {
        const lastName = /** @type {HTMLInputElement} */ (document.getElementById('new-player-last-name')).value.trim();
        const firstName = /** @type {HTMLInputElement} */ (document.getElementById('new-player-first-name')).value.trim();
        const patronymic = /** @type {HTMLInputElement} */ (document.getElementById('new-player-patronymic')).value.trim();
        const townId = /** @type {HTMLInputElement} */ (document.getElementById('new-player-town-id')).value;

        if (!lastName || !firstName) {
            return;
        }

        const name = [lastName, firstName, patronymic].filter(Boolean).join(' ');
        addPlayer({
            id: null,
            name,
            lastName,
            firstName,
            patronymic: patronymic || undefined,
            townId: townId ? parseInt(townId) : undefined,
        });

        /** @type {HTMLInputElement} */ (document.getElementById('new-player-last-name')).value = '';
        /** @type {HTMLInputElement} */ (document.getElementById('new-player-first-name')).value = '';
        /** @type {HTMLInputElement} */ (document.getElementById('new-player-patronymic')).value = '';
        /** @type {HTMLInputElement} */ (document.getElementById('new-player-town')).value = '';
        /** @type {HTMLInputElement} */ (document.getElementById('new-player-town-id')).value = '';
    });
}

/**
 * @param {{id: number|null, name: string, lastName?: string, firstName?: string, patronymic?: string, townId?: number}} player
 */
function addPlayer(player) {
    if (player.id !== null && selectedPlayers.some(p => p.id === player.id)) {
        return;
    }

    selectedPlayers.push(player);

    if (captainId === null && player.id !== null && player.id === baseSquadCaptainId) {
        captainId = player.id;
    }

    renderPlayers();
}

function renderPlayers() {
    const tbody = /** @type {HTMLElement} */ (document.querySelector('#players-table tbody'));
    const table = /** @type {HTMLElement} */ (document.getElementById('players-table'));
    const countEl = /** @type {HTMLElement} */ (document.getElementById('player-count'));

    tbody.innerHTML = '';
    countEl.textContent = String(selectedPlayers.length);
    table.hidden = selectedPlayers.length === 0;

    selectedPlayers.forEach((player, index) => {
        const tr = document.createElement('tr');

        const tdName = document.createElement('td');
        tdName.textContent = player.name;

        const tdCaptain = document.createElement('td');
        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'captain';
        if (player.id !== null) {
            radio.checked = captainId === player.id;
        } else {
            radio.checked = captainId === index;
        }
        radio.addEventListener('change', () => {
            captainId = player.id !== null ? player.id : index;
        });
        tdCaptain.appendChild(radio);

        const tdRemove = document.createElement('td');
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-remove';
        removeBtn.textContent = '\u00d7';
        removeBtn.addEventListener('click', () => {
            selectedPlayers.splice(index, 1);
            if (captainId === player.id || captainId === index) {
                captainId = null;
            }
            renderPlayers();
        });
        tdRemove.appendChild(removeBtn);

        tr.append(tdName, tdCaptain, tdRemove);
        tbody.appendChild(tr);
    });

    syncSuggestionRows();
}

/**
 * @param {HTMLFormElement} form
 */
function initFormSubmit(form) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const redirect = /** @type {string} */ (form.dataset.redirect);
        const status = /** @type {HTMLElement} */ (document.getElementById('squad-status'));

        if (selectedPlayers.length < 1) {
            status.textContent = trans('squad.error.min_players');
            status.hidden = false;
            return;
        }

        if (selectedPlayers.length > 8) {
            status.textContent = trans('squad.error.max_players');
            status.hidden = false;
            return;
        }

        if (resolveCaptainIndex() === null) {
            status.textContent = trans('squad.error.captain_required');
            status.hidden = false;
            return;
        }

        const isEditMode = form.dataset.editMode === '1';

        /** @type {object} */
        const data = {
            players: selectedPlayers.map(p => p.id !== null
                ? { id: p.id }
                : { lastName: p.lastName, firstName: p.firstName, patronymic: p.patronymic || null, townId: p.townId || null },
            ),
            captainIndex: resolveCaptainIndex(),
        };

        if (isEditMode) {
            const teamId = /** @type {HTMLInputElement} */ (document.getElementById('team-id')).value;
            data.teamId = parseInt(teamId);
            const oneTimeName = /** @type {HTMLInputElement} */ (document.getElementById('one-time-name')).value.trim();
            data.oneTimeName = oneTimeName || null;
        } else {
            const teamMode = /** @type {HTMLInputElement} */ (form.querySelector('input[name="teamMode"]:checked')).value;
            const teamStatus = /** @type {HTMLElement|null} */ (document.getElementById('new-team-status'));

            if (teamMode === 'existing') {
                const teamId = /** @type {HTMLInputElement} */ (document.getElementById('team-id')).value;
                if (!teamId) {
                    status.textContent = trans('squad.error.team_required');
                    status.hidden = false;
                    return;
                }
                data.teamId = parseInt(teamId);
                const oneTimeName = /** @type {HTMLInputElement} */ (document.getElementById('one-time-name')).value.trim();
                if (oneTimeName) {
                    data.oneTimeName = oneTimeName;
                }
            } else {
                const teamName = /** @type {HTMLInputElement} */ (document.getElementById('new-team-name')).value.trim();
                const townId = /** @type {HTMLInputElement} */ (document.getElementById('new-team-town-id')).value;
                if (!teamName) {
                    if (teamStatus) {
                        teamStatus.textContent = trans('squad.error.team_required');
                        teamStatus.hidden = false;
                    }
                    return;
                }
                if (!townId) {
                    if (teamStatus) {
                        teamStatus.textContent = trans('squad.error.town_required');
                        teamStatus.hidden = false;
                    }
                    return;
                }
                if (teamStatus) {
                    teamStatus.hidden = true;
                }
                data.teamName = teamName;
                data.townId = parseInt(townId);
            }
        }

        status.hidden = true;

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.href = redirect;
                } else {
                    status.textContent = body.error ? transError(body.error) : trans('common.error');
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.hidden = false;
            });
    });
}

/**
 * @returns {number|null}
 */
function resolveCaptainIndex() {
    if (captainId === null) {
        return null;
    }

    for (let i = 0; i < selectedPlayers.length; i++) {
        const player = selectedPlayers[i];
        if (player.id !== null && player.id === captainId) {
            return i;
        }
        if (player.id === null && captainId === i) {
            return i;
        }
    }

    return null;
}

document.addEventListener('turbo:load', initSquadForm);
