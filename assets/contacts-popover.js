// @ts-check

document.addEventListener('click', (event) => {
    const target = /** @type {HTMLElement} */ (event.target);
    const trigger = target.closest('.btn-contacts');

    if (!trigger) {
        if (!target.closest('.contacts-popover')) {
            document.querySelectorAll('.contacts-popover').forEach(el => el.classList.add('contacts-popover--hidden'));
        }
        return;
    }

    const popover = trigger.nextElementSibling;
    if (!popover || !popover.classList.contains('contacts-popover')) {
        return;
    }

    const isOpen = !popover.classList.contains('contacts-popover--hidden');

    // Close all popovers
    document.querySelectorAll('.contacts-popover').forEach(el => el.classList.add('contacts-popover--hidden'));

    if (!isOpen) {
        popover.classList.remove('contacts-popover--hidden');
    }
});
