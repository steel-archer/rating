// @ts-check
import { trans } from './trans.js';
import { apiPost } from './api.js';
import { buttonAction } from './button-action.js';

function initSessionClaimForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('session-claim-form'));
    if (!form) {
        return;
    }

    const venueSelect = /** @type {HTMLSelectElement} */ (form.querySelector('[name="venueId"]'));
    const onlineGroup = document.getElementById('session-online-group');
    const onlineMode = form.dataset.onlineMode || 'mixed';

    // For strict online/offline tournaments: auto-set isOnline and hide the group
    if (onlineMode === 'online' || onlineMode === 'offline') {
        const value = onlineMode === 'online' ? '1' : '0';
        /** @type {HTMLInputElement} */ (form.querySelector(`input[name="isOnline"][value="${value}"]`)).checked = true;
        if (onlineGroup) {
            onlineGroup.style.display = 'none';
        }
    }

    if (venueSelect && onlineGroup) {
        venueSelect.addEventListener('change', () => {
            // Skip venue-based isOnline logic for strict tournaments
            if (onlineMode === 'online' || onlineMode === 'offline') {
                return;
            }

            const selected = venueSelect.selectedOptions[0];
            const venueIsOnline = selected?.dataset.online === '1';

            if (venueIsOnline) {
                onlineGroup.style.display = 'none';
                /** @type {HTMLInputElement} */ (form.querySelector('input[name="isOnline"][value="1"]')).checked = true;
            } else {
                onlineGroup.style.display = '';
            }
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const redirect = form.dataset.redirect || null;
        const status = /** @type {HTMLElement} */ (document.getElementById('session-claim-status'));
        const hostInput = /** @type {HTMLInputElement|null} */ (form.querySelector('.officials-group[data-role="claim-host"] input[type="hidden"]'));
        const isOnline = /** @type {HTMLInputElement} */ (form.querySelector('input[name="isOnline"]:checked')).value === '1';

        const data = {
            venueId: parseInt(/** @type {HTMLSelectElement} */ (form.querySelector('[name="venueId"]')).value) || null,
            playedAt: /** @type {HTMLInputElement} */ (form.querySelector('[name="playedAt"]')).value || null,
            estimatedTeams: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="estimatedTeams"]')).value) || null,
            hostId: hostInput ? parseInt(hostInput.value) || null : null,
            isOnline,
        };

        status.hidden = true;

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    if (redirect) {
                        window.location.href = redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    status.textContent = body.error ? trans(body.error) : trans('common.error');
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.hidden = false;
            });
    });
}

function initSessionClaimEditForm() {
    const form = /** @type {HTMLFormElement|null} */ (document.getElementById('session-claim-edit-form'));
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = /** @type {string} */ (form.dataset.url);
        const status = /** @type {HTMLElement} */ (document.getElementById('save-status'));
        const hostInput = /** @type {HTMLInputElement|null} */ (form.querySelector('.officials-group[data-role="host"] input[type="hidden"]'));

        const data = {
            playedAt: /** @type {HTMLInputElement} */ (form.querySelector('[name="playedAt"]')).value || null,
            estimatedTeams: parseInt(/** @type {HTMLInputElement} */ (form.querySelector('[name="estimatedTeams"]')).value) || null,
            hostId: hostInput ? parseInt(hostInput.value) || null : null,
        };

        status.hidden = true;

        apiPost(url, data)
            .then(({ok, body}) => {
                if (ok) {
                    window.location.reload();
                } else {
                    status.textContent = body.error ? trans(body.error) : trans('common.error');
                    status.hidden = false;
                }
            })
            .catch(() => {
                status.textContent = trans('common.error');
                status.hidden = false;
            });
    });
}

function initSessionClaimActions() {
    document.addEventListener('click', (event) => {
        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.sessionApprove || '';
            const row = approveBtn.closest('[data-venue-sessions]');
            const venueSessions = row ? parseInt(/** @type {HTMLElement} */ (row).dataset.venueSessions || '0') : 1;

            if (venueSessions === 0 && !confirm(trans('session_claim.confirm_approve_no_sessions'))) {
                return;
            }

            buttonAction(
                `/my/tournament-claims/${id}/approve`,
                /** @type {HTMLButtonElement} */ (approveBtn),
                { onSuccess: () => { moveClaimToApproved(approveBtn); } },
            );
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.sessionReject || '';
            const commentInput = /** @type {HTMLInputElement|null} */ (document.querySelector(`[data-session-reject-comment="${id}"]`));
            const comment = commentInput ? commentInput.value : null;
            buttonAction(
                `/my/tournament-claims/${id}/reject`,
                /** @type {HTMLButtonElement} */ (rejectBtn),
                { data: {comment}, onSuccess: () => moveClaimToRejected(rejectBtn, comment) },
            );
            return;
        }

        const resubmitBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-resubmit]');
        if (resubmitBtn) {
            const id = /** @type {HTMLElement} */ (resubmitBtn).dataset.sessionResubmit || '';
            buttonAction(
                `/my/session-claims/${id}/resubmit`,
                /** @type {HTMLButtonElement} */ (resubmitBtn),
            );
            return;
        }

        const deleteBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-delete]');
        if (deleteBtn) {
            const id = /** @type {HTMLElement} */ (deleteBtn).dataset.sessionDelete || '';
            const redirect = /** @type {HTMLElement} */ (deleteBtn).dataset.redirect || null;
            buttonAction(
                `/my/session-claims/${id}/delete`,
                /** @type {HTMLButtonElement} */ (deleteBtn),
                { onSuccess: () => redirect ? (window.location.href = redirect) : removeSessionClaimCard(deleteBtn) },
            );
        }

        const revokeBtn = /** @type {HTMLElement} */ (event.target).closest('[data-session-revoke]');
        if (revokeBtn) {
            if (!confirm(trans('session_claim.confirm_revoke'))) {
                return;
            }
            const id = /** @type {HTMLElement} */ (revokeBtn).dataset.sessionRevoke || '';
            buttonAction(
                `/my/tournament-claims/${id}/revoke`,
                /** @type {HTMLButtonElement} */ (revokeBtn),
                { onSuccess: () => removeApprovedClaimRow(revokeBtn) },
            );
        }

        const squadDeleteBtn = /** @type {HTMLElement} */ (event.target).closest('[data-squad-delete]');
        if (squadDeleteBtn) {
            const id = /** @type {HTMLElement} */ (squadDeleteBtn).dataset.squadDelete || '';
            buttonAction(
                `/my/session-teams/${id}/delete`,
                /** @type {HTMLButtonElement} */ (squadDeleteBtn),
                { onSuccess: () => window.location.reload() },
            );
        }
    });
}

/**
 * @param {Element} btn
 */
function removeSessionClaimCard(btn) {
    const row = btn.closest('[data-session-claim-id]');
    if (!row) {
        return;
    }
    const container = row.closest('.card');
    row.remove();
    if (container && container.querySelectorAll('[data-session-claim-id]').length === 0) {
        container.remove();
    }
}

/**
 * @param {Element} btn
 */
function removeApprovedClaimRow(btn) {
    const row = btn.closest('tr');
    if (!row) {
        return;
    }
    const card = row.closest('.card');
    row.remove();
    if (card && card.querySelectorAll('tbody tr').length === 0) {
        card.remove();
    }
}

/**
 * @param {Element} btn
 */
function moveClaimToApproved(btn) {
    const row = btn.closest('tr');
    if (!row) {
        return;
    }

    const sourceCard = row.closest('.card');
    const tournamentId = extractTournamentId(sourceCard);
    const id = resolveClaimId(row);

    // Swap the actions cell for a revoke cell (matches the server-rendered approved section).
    const actionsCell = row.querySelector('td:last-child');
    if (actionsCell) {
        actionsCell.innerHTML = `<button type="button" class="btn btn-reject" data-session-revoke="${id}">${trans('session_claim.revoke')}</button>`;
    }
    row.querySelectorAll('.venue-sessions-count, .warning-badge').forEach(el => el.remove());
    row.removeAttribute('data-venue-sessions');
    row.removeAttribute('data-session-claim-id');

    const approvedSection = document.getElementById('approved-section');
    if (!approvedSection) {
        return;
    }

    const emptyState = approvedSection.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    const approvedCard = tournamentId ? document.getElementById(`tournament-approved-${tournamentId}`) : null;

    if (approvedCard) {
        approvedCard.querySelector('tbody')?.appendChild(row);
    } else if (sourceCard && tournamentId) {
        const newCard = cloneCardShell(sourceCard, `tournament-approved-${tournamentId}`, true);
        approvedSection.appendChild(newCard);
        newCard.querySelector('tbody')?.appendChild(row);
    }

    removeCardIfEmpty(sourceCard);
}

/**
 * @param {Element} btn
 * @param {string|null} comment
 */
function moveClaimToRejected(btn, comment) {
    const row = btn.closest('tr');
    if (!row) {
        return;
    }

    const sourceCard = row.closest('.card');
    const tournamentId = extractTournamentId(sourceCard);

    // Replace the moderation actions cell with a reapprove cell showing the reason.
    const actionsCell = row.querySelector('td:last-child');
    if (actionsCell) {
        const id = resolveClaimId(row);
        const reasonHtml = comment
            ? `<p class="reject-comment">${trans('session_claim.reject_reason')}: ${escapeHtml(comment)}</p>`
            : '';
        actionsCell.innerHTML = `${reasonHtml}<button type="button" class="btn btn-approve" data-session-approve="${id}">${trans('moderator.approve')}</button>`;
    }
    row.querySelectorAll('.venue-sessions-count, .warning-badge').forEach(el => el.remove());
    row.removeAttribute('data-venue-sessions');

    const rejectedSection = document.getElementById('rejected-section');
    if (!rejectedSection) {
        removeCardIfEmpty(sourceCard);
        return;
    }

    const emptyState = rejectedSection.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    const rejectedCard = tournamentId ? document.getElementById(`tournament-rejected-${tournamentId}`) : null;

    if (rejectedCard) {
        rejectedCard.querySelector('tbody')?.appendChild(row);
    } else if (sourceCard && tournamentId) {
        const newCard = cloneCardShell(sourceCard, `tournament-rejected-${tournamentId}`, false);
        rejectedSection.appendChild(newCard);
        newCard.querySelector('tbody')?.appendChild(row);
    }

    removeCardIfEmpty(sourceCard);
}

/**
 * Resolve the session claim id from a row, regardless of which section it lives in.
 *
 * @param {Element} row
 * @returns {string}
 */
function resolveClaimId(row) {
    const actionBtn = row.querySelector('[data-session-approve], [data-session-reject], [data-session-revoke]');
    if (actionBtn) {
        const el = /** @type {HTMLElement} */ (actionBtn);
        return el.dataset.sessionApprove || el.dataset.sessionReject || el.dataset.sessionRevoke || '';
    }
    return row.getAttribute('data-session-claim-id') || '';
}

/**
 * @param {Element|null} card
 * @returns {string|null}
 */
function extractTournamentId(card) {
    if (!card) {
        return null;
    }
    const match = card.id.match(/^tournament-(?:claims|approved|rejected)-(\d+)$/);
    return match ? match[1] : null;
}

/**
 * Clone a card's heading/table shell (empty tbody) for another section.
 *
 * @param {Element} sourceCard
 * @param {string} newId
 * @param {boolean} keepActionsColumn
 * @returns {HTMLElement}
 */
function cloneCardShell(sourceCard, newId, keepActionsColumn) {
    const heading = sourceCard.querySelector('h3');
    const headingHtml = heading ? heading.outerHTML : '';
    const tableLabel = sourceCard.querySelector('table')?.getAttribute('aria-label') || '';

    const newCard = document.createElement('div');
    newCard.className = 'card card-wide';
    newCard.id = newId;
    newCard.innerHTML = `${headingHtml}
                <table aria-label="${tableLabel}">
                    <thead>
                        ${cloneTableHeader(sourceCard, keepActionsColumn)}
                    </thead>
                    <tbody></tbody>
                </table>`;

    return newCard;
}

/**
 * @param {Element} sourceCard
 * @param {boolean} keepActionsColumn
 * @returns {string}
 */
function cloneTableHeader(sourceCard, keepActionsColumn) {
    const headerRow = sourceCard.querySelector('thead tr');
    if (!headerRow) {
        return '';
    }
    const clone = /** @type {HTMLElement} */ (headerRow.cloneNode(true));
    if (!keepActionsColumn) {
        clone.querySelector('th:last-child')?.remove();
    }
    return clone.outerHTML;
}

/**
 * @param {Element|null} card
 */
function removeCardIfEmpty(card) {
    if (card && card.querySelectorAll('tbody tr').length === 0) {
        card.remove();
    }
}

/**
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
}

initSessionClaimActions();

document.addEventListener('turbo:load', () => {
    initSessionClaimForm();
    initSessionClaimEditForm();
});
