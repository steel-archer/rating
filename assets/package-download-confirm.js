// @ts-check
import { trans } from './trans.js';

/**
 * Intercepts clicks on question-package download links and shows a warning
 * modal: downloading the package means the player can no longer play the
 * tournament, and the download is recorded. The user must confirm to proceed.
 */
function init() {
    document.addEventListener('click', (event) => {
        const link = /** @type {HTMLElement} */ (event.target).closest('.document-link');
        if (!link || !(link instanceof HTMLAnchorElement)) {
            return;
        }

        event.preventDefault();
        openModal(link.href);
    });
}

/**
 * @param {string} href
 */
function openModal(href) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'modal-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');

    const title = document.createElement('h2');
    title.className = 'modal-title';
    title.textContent = trans('package_download.title');

    const text = document.createElement('p');
    text.textContent = trans('package_download.warning');

    const actions = document.createElement('div');
    actions.className = 'modal-actions';

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-muted';
    cancelBtn.textContent = trans('package_download.cancel');

    const confirmBtn = document.createElement('button');
    confirmBtn.type = 'button';
    confirmBtn.className = 'btn';
    confirmBtn.textContent = trans('package_download.confirm');

    actions.append(cancelBtn, confirmBtn);
    dialog.append(title, text, actions);
    overlay.append(dialog);
    document.body.append(overlay);

    const close = () => overlay.remove();

    cancelBtn.addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            close();
        }
    });
    document.addEventListener('keydown', function onKey(event) {
        if (event.key === 'Escape') {
            close();
            document.removeEventListener('keydown', onKey);
        }
    });

    confirmBtn.addEventListener('click', () => {
        // The download is served as an attachment and does not navigate away,
        // so close the modal right after the download starts.
        window.location.href = href;
        close();
    });

    confirmBtn.focus();
}

init();
