// @ts-check
import { trans } from './trans.js';
import { buttonAction } from './button-action.js';

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (event) => {
        const approveBtn = /** @type {HTMLElement} */ (event.target).closest('[data-captain-claim-approve]');
        if (approveBtn) {
            const id = /** @type {HTMLElement} */ (approveBtn).dataset.captainClaimApprove || '';
            buttonAction(
                `/moderator/captain-claims/${id}/approve`,
                /** @type {HTMLButtonElement} */ (approveBtn),
                { onSuccess: () => removeModerationCard(approveBtn) },
            );
            return;
        }

        const rejectBtn = /** @type {HTMLElement} */ (event.target).closest('[data-captain-claim-reject]');
        if (rejectBtn) {
            const id = /** @type {HTMLElement} */ (rejectBtn).dataset.captainClaimReject || '';
            const commentInput = /** @type {HTMLInputElement|null} */ (
                document.querySelector(`[data-captain-claim-reject-comment="${id}"]`)
            );
            const comment = commentInput ? commentInput.value.trim() : '';
            if (!comment) {
                alert(trans('moderator.reject_comment_required'));
                commentInput?.focus();
                return;
            }
            buttonAction(
                `/moderator/captain-claims/${id}/reject`,
                /** @type {HTMLButtonElement} */ (rejectBtn),
                { data: { comment }, onSuccess: () => removeModerationCard(rejectBtn) },
            );
        }
    });
});

/**
 * @param {Element} btn
 */
function removeModerationCard(btn) {
    const card = btn.closest('.moderation-card');
    card?.remove();
    if (document.querySelectorAll('.moderation-card').length === 0) {
        const container = document.querySelector('h1')?.parentElement;
        if (container && !container.querySelector('.empty-state')) {
            const emptyState = document.createElement('p');
            emptyState.className = 'empty-state';
            emptyState.textContent = trans('moderator.no_captain_claims');
            container.appendChild(emptyState);
        }
    }
}
