/**
 * Perilaku form yang dipakai ulang di semua halaman (JavaScript murni).
 *
 * - <button data-password-toggle aria-controls="id-input">: tampil/sembunyi kata sandi.
 *   Tombol diberi atribut `hidden` di HTML dan baru dimunculkan di sini, jadi tanpa JS tombol tidak tampil.
 * - <form data-submit-once>: mencegah form terkirim dua kali (klik ganda).
 */

function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.hidden = false;
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-password-toggle]');

        if (!button) {
            return;
        }

        const input = document.getElementById(button.getAttribute('aria-controls'));

        if (!input) {
            return;
        }

        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    });
}

function setSubmitting(form, submitting) {
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        button.disabled = submitting;
    });

    form.toggleAttribute('aria-busy', submitting);
    form.dataset.submitting = submitting ? 'true' : '';
}

function initSubmitOnce() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!form.matches('form[data-submit-once]')) {
            return;
        }

        if (form.dataset.submitting === 'true') {
            event.preventDefault();

            return;
        }

        setSubmitting(form, true);
    });

    // Saat kembali lewat tombol Back, browser bisa memulihkan halaman dengan tombol masih nonaktif.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }

        document.querySelectorAll('form[data-submit-once]').forEach((form) => setSubmitting(form, false));
    });
}

export function initForms() {
    initPasswordToggles();
    initSubmitOnce();
}
