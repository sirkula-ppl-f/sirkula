/**
 * Dialog konfirmasi untuk aksi berisiko (JavaScript murni, tanpa library).
 *
 * - <form data-confirm="Pesan" data-confirm-title="Judul" data-confirm-label="Teks tombol" data-confirm-variant="danger">:
 *   sebelum terkirim, tampilkan <x-confirm-dialog />. Form baru dikirim setelah tombol konfirmasi ditekan.
 * - Batal, Esc, atau klik backdrop menutup dialog tanpa mengirim, lalu fokus kembali ke tombol pemicu.
 * - Tanpa JS, tanpa dialog di halaman, atau browser tanpa showModal()/requestSubmit(): form terkirim biasa.
 */

export function initConfirmDialogs() {
    const dialog = document.querySelector('[data-confirm-dialog]');

    if (!dialog || typeof dialog.showModal !== 'function' || typeof HTMLFormElement.prototype.requestSubmit !== 'function') {
        return;
    }

    const title = dialog.querySelector('[data-confirm-dialog-title]');
    const message = dialog.querySelector('[data-confirm-dialog-message]');
    const confirmButton = dialog.querySelector('[data-confirm-dialog-confirm]');
    const defaultTitle = title.textContent;
    const defaultLabel = confirmButton.textContent;

    let pending = null;
    let pressedOnBackdrop = false;

    // Fase capture: jalan sebelum listener submit lain (termasuk data-submit-once di forms.js),
    // sehingga kiriman yang belum dikonfirmasi tidak membuat tombol nonaktif.
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;

            if (!form.matches('form[data-confirm]') || form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (dialog.open) {
                return;
            }

            pending = { form, submitter: event.submitter };

            title.textContent = form.dataset.confirmTitle || defaultTitle;
            message.textContent = form.dataset.confirm;
            confirmButton.textContent = form.dataset.confirmLabel || defaultLabel;

            if (form.dataset.confirmVariant === 'danger') {
                confirmButton.dataset.variant = 'danger';
            } else {
                delete confirmButton.dataset.variant;
            }

            dialog.returnValue = '';
            dialog.showModal();
        },
        true,
    );

    dialog.addEventListener('close', () => {
        if (!pending) {
            return;
        }

        const { form, submitter } = pending;

        pending = null;

        if (dialog.returnValue === 'confirm') {
            // Kirim ulang lewat requestSubmit supaya event submit (dan data-submit-once) tetap berjalan.
            form.dataset.confirmed = 'true';
            form.requestSubmit(submitter);
            delete form.dataset.confirmed;

            return;
        }

        (submitter ?? form.querySelector('[type="submit"]'))?.focus();
    });

    // Tutup lewat backdrop hanya bila tekan dan lepas sama-sama di backdrop,
    // jadi seleksi teks yang diseret keluar dialog tidak menutupnya.
    dialog.addEventListener('pointerdown', (event) => {
        pressedOnBackdrop = event.target === dialog;
    });

    dialog.addEventListener('click', (event) => {
        if (pressedOnBackdrop && event.target === dialog) {
            dialog.close('cancel');
        }

        pressedOnBackdrop = false;
    });
}
