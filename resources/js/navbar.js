/**
 * Navigasi responsif untuk perangkat seluler (JavaScript murni).
 *
 * - <button data-nav-toggle aria-controls="id-panel">: membuka/menutup panel navigasi di layar sempit (< md).
 *   Tombol diberi atribut `hidden` di HTML dan baru dimunculkan di sini, sehingga tanpa JS seluruh
 *   tautan navigasi tetap terlihat.
 * - Saat JS aktif, panel diberi kelas `max-md:hidden` saat tertutup dan dibuka saat tombol diklik.
 * - Tombol Escape atau klik di luar area navigasi menutup panel dan mengembalikan fokus ke tombol pemicu.
 */

export function initNavbar() {
    const toggle = document.querySelector('[data-nav-toggle]');

    if (!toggle) {
        return;
    }

    const panelId = toggle.getAttribute('aria-controls');
    const panel = panelId ? document.getElementById(panelId) : null;

    if (!panel) {
        return;
    }

    const openIcon = toggle.querySelector('[data-nav-icon-open]');
    const closeIcon = toggle.querySelector('[data-nav-icon-close]');
    const label = toggle.querySelector('[data-nav-toggle-label]');

    function setExpanded(expanded) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        panel.classList.toggle('max-md:hidden', !expanded);
        openIcon?.classList.toggle('hidden', expanded);
        closeIcon?.classList.toggle('hidden', !expanded);

        if (label) {
            label.textContent = expanded ? 'Tutup menu navigasi' : 'Buka menu navigasi';
        }
    }

    // Aktifkan tombol toggle dan sembunyikan panel di layar < md secara bawaan.
    toggle.hidden = false;
    setExpanded(false);

    toggle.addEventListener('click', () => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        setExpanded(!isExpanded);
    });

    // Tutup panel saat tombol Escape ditekan di layar sempit.
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || toggle.getAttribute('aria-expanded') !== 'true') {
            return;
        }

        setExpanded(false);
        toggle.focus();
    });

    // Tutup panel saat pengguna mengklik di luar header navigasi.
    document.addEventListener('click', (event) => {
        if (toggle.getAttribute('aria-expanded') !== 'true') {
            return;
        }

        const nav = toggle.closest('nav');

        if (nav && !nav.contains(event.target)) {
            setExpanded(false);
        }
    });

    initCatalogFilter();
}

/**
 * Panel filter katalog yang dapat dilipat di perangkat seluler (< md).
 * Di layar >= md, panel filter tetap terbuka secara bawaan.
 */
function initCatalogFilter() {
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
    const stateLabel = toggle.querySelector('[data-filter-state-label]');

    function setExpanded(expanded) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        panel.classList.toggle('max-md:hidden', !expanded);
        chevron?.classList.toggle('rotate-180', expanded);

        if (stateLabel) {
            stateLabel.textContent = expanded ? 'Tutup' : 'Buka';
        }
    }

    toggle.hidden = false;
    setExpanded(hasActive);

    toggle.addEventListener('click', () => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        setExpanded(!isExpanded);
    });
}
