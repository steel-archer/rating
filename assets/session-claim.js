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
                { data: {comment}, onSuccess: () => removeSessionClaimCard(rejectBtn) },
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
    const row = btn.closest('[data-session-claim-id]');
    if (!row) {
        return;
    }

    const pendingCard = row.closest('.card');
    const tournamentId = pendingCard ? pendingCard.id.replace('tournament-claims-', '') : null;

    // Remove actions column from the row
    const actionsCell = row.querySelector('td:last-child');
    if (actionsCell && actionsCell.querySelector('.moderation-actions')) {
        actionsCell.remove();
    }

    // Remove venue warning info (not relevant for approved)
    row.querySelectorAll('.venue-sessions-count, .warning-badge').forEach(el => el.remove());
    row.removeAttribute('data-venue-sessions');
    row.removeAttribute('data-session-claim-id');

    // Find or create the approved card for this tournament
    const approvedSection = document.getElementById('approved-section');
    if (!approvedSection) {
        return;
    }

    // Remove empty state message if present
    const emptyState = approvedSection.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    const approvedCard = tournamentId ? document.getElementById(`tournament-approved-${tournamentId}`) : null;

    if (approvedCard) {
        const tbody = approvedCard.querySelector('tbody');
        if (tbody) {
            tbody.appendChild(row);
        }
    } else if (pendingCard && tournamentId) {
        // Create a new approved card cloning the structure
        const heading = pendingCard.querySelector('h3');
        const headingHtml = heading ? heading.outerHTML : '';
        const tableLabel = pendingCard.querySelector('table')?.getAttribute('aria-label') || '';

        const newCard = document.createElement('div');
        newCard.className = 'card card-wide';
        newCard.id = `tournament-approved-${tournamentId}`;
        newCard.innerHTML = `${headingHtml}
                <table aria-label="${tableLabel}">
                    <thead>
                        ${getApprovedTableHeader(pendingCard)}
                    </thead>
                    <tbody></tbody>
                </table>`;
        approvedSection.appendChild(newCard);

        const tbody = newCard.querySelector('tbody');
        if (tbody) {
            tbody.appendChild(row);
        }
    }

    // Remove pending card if empty
    if (pendingCard && pendingCard.querySelectorAll('[data-session-claim-id]').length === 0) {
        pendingCard.remove();
    }
}

/**
 * @param {Element} pendingCard
 * @returns {string}
 */
function getApprovedTableHeader(pendingCard) {
    const headerRow = pendingCard.querySelector('thead tr');
    if (!headerRow) {
        return '';
    }
    // Clone header without the last column (actions)
    const clone = /** @type {HTMLElement} */ (headerRow.cloneNode(true));
    const lastTh = clone.querySelector('th:last-child');
    if (lastTh) {
        lastTh.remove();
    }
    return clone.outerHTML;
}

initSessionClaimActions();

document.addEventListener('turbo:load', () => {
    initSessionClaimForm();
    initSessionClaimEditForm();
});
