/**
 * Panel filter katalog yang dapat dilipat di perangkat seluler (< md).
 * Di layar >= md, panel filter tetap terbuka secara bawaan.
 */
export function initCatalogFilter() {
    const toggle = document.querySelector('[data-filter-toggle]');

    if (!toggle) {
        return;
    }

    const panelId = toggle.getAttribute('aria-controls');
    const panel = panelId ? document.getElementById(panelId) : null;

    if (!panel) {
        return;
    }

    const hasActive = toggle.getAttribute('data-filter-active') === 'true';
    const chevron = toggle.querySelector('[data-filter-chevron]');

    function setExpanded(expanded) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        panel.classList.toggle('max-md:hidden', !expanded);
        chevron?.classList.toggle('rotate-180', expanded);
    }

    toggle.hidden = false;
    setExpanded(hasActive);

    toggle.addEventListener('click', () => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        setExpanded(!isExpanded);
    });
}
