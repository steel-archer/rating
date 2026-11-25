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
    // Keep the existing state: Turbo stores its restoration identifier there,
    // and without it the back button changes only the URL, not the page.
    window.history.replaceState(window.history.state, '', pageUrl);
});
