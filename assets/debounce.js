// @ts-check

/**
 * @template {(...args: any[]) => void} T
 * @param {T} func
 * @param {number} delay
 * @returns {T}
 */
export function debounce(func, delay) {
    /** @type {ReturnType<typeof setTimeout>|undefined} */
    let timer;
    return /** @type {T} */ ((...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => func(...args), delay);
    });
}
