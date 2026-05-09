// @ts-check
import { trans } from './trans.js';

/**
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('click', (event) => {
    const target = /** @type {HTMLElement} */ (event.target);
    const trigger = target.closest('[data-contacts-trigger]');

    if (!trigger) {
        if (!target.closest('.contacts-popover')) {
            document.querySelectorAll('.contacts-popover').forEach(el => el.remove());
        }
        return;
    }

    // Toggle if already open
    const wrapper = trigger.closest('td') || trigger.parentElement;
    const existing = wrapper?.querySelector('.contacts-popover');
    if (existing) {
        existing.remove();
        return;
    }

    // Close other popovers
    document.querySelectorAll('.contacts-popover').forEach(el => el.remove());

    const sessionId = trigger.dataset.contactsTrigger;
    const url = `/api/sessions/${sessionId}/contacts`;

    const popover = document.createElement('div');
    popover.className = 'contacts-popover';
    popover.textContent = trans('common.loading');
    wrapper?.style.setProperty('position', 'relative');
    wrapper?.appendChild(popover);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                popover.textContent = trans('common.error');
                return;
            }

            const items = [];
            if (data.email) {
                const email = escapeHtml(data.email);
                items.push(`<svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 4 10 8 10-8"/></svg> <a href="mailto:${email}">${email}</a>`);
            }
            if (data.telegram) {
                const tg = escapeHtml(data.telegram);
                items.push(`<svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.03-1.99 1.27-5.62 3.72-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.06-.49-.83-.27-1.49-.42-1.43-.88.03-.24.37-.49 1.02-.74 3.99-1.74 6.65-2.89 7.99-3.44 3.8-1.58 4.59-1.86 5.1-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.01.06.01.24 0 .38z"/></svg> <a href="https://t.me/${tg}" target="_blank" rel="noopener">@${tg}</a>`);
            }
            if (data.facebook) {
                const fb = escapeHtml(data.facebook);
                items.push(`<svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> <a href="https://facebook.com/${fb}" target="_blank" rel="noopener">${fb}</a>`);
            }
            if (data.phone) {
                const phone = escapeHtml(data.phone);
                items.push(`<svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> <a href="tel:${phone}">${phone}</a>`);
            }

            popover.innerHTML = '';
            if (items.length === 0) {
                popover.textContent = '—';
                return;
            }
            items.forEach(item => {
                const line = document.createElement('div');
                line.innerHTML = item;
                popover.appendChild(line);
            });
        })
        .catch(() => {
            popover.textContent = trans('common.error');
        });
});
