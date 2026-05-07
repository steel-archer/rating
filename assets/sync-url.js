// @ts-check
document.addEventListener('turbo:frame-load', (event) => {
    const frame = /** @type {HTMLElement} */ (event.target);
    if (!frame.hasAttribute('data-sync-url')) {
        return;
    }

    const src = frame.getAttribute('src');
    if (!src) {
        return;
    }

    const frameUrl = new URL(src, window.location.origin);
    const pageUrl = new URL(window.location.href);

    pageUrl.search = frameUrl.search;
    window.history.replaceState({}, '', pageUrl);
});
