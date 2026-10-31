/**
 * Perilaku interaktif halaman Admin - Kelola Status Akun (FR-004) dengan JavaScript murni.
 *
 * - Memilih baris pengguna dari tabel untuk memperbarui panel detail & aksi di samping (tanpa reload).
 * - Membuka <dialog> konfirmasi bawaan browser sebelum menangguhkan atau mengaktifkan kembali akun.
 * - Mengembalikan fokus ke tombol pemicu saat dialog ditutup tanpa konfirmasi.
 */

export function initAdminUsers() {
    const panel = document.getElementById('user-action-panel');
    const form = document.getElementById('user-status-form');
    const dialog = document.getElementById('confirm-status-dialog');

    if (!panel || !form) {
        return;
    }

    const nameEl = document.getElementById('panel-user-name');
    const emailEl = document.getElementById('panel-user-email');
    const badgeEl = document.getElementById('panel-user-status-badge');
    const guardNoticeEl = document.getElementById('panel-guard-notice');
    const reasonInput = document.getElementById('reason-textarea');
    const reasonError = document.getElementById('reason-error');
    const submitBtn = document.getElementById('panel-submit-button');

    const dialogTitle = document.getElementById('dialog-title');
    const dialogDesc = document.getElementById('dialog-description');
    const dialogReasonPreview = document.getElementById('dialog-reason-preview');
    const dialogCancelBtn = document.getElementById('dialog-cancel-button');
    const dialogConfirmBtn = document.getElementById('dialog-confirm-button');

    let confirmedByDialog = false;
    let pressedOnBackdrop = false;

    function clearClientError() {
        if (!reasonError) {
            return;
        }
        reasonError.textContent = '';
        reasonError.classList.add('hidden');
        reasonInput?.removeAttribute('aria-invalid');
        reasonInput?.removeAttribute('aria-describedby');
        reasonInput?.classList.remove('border-red-600');
        reasonInput?.classList.add('border-gray-300', 'focus:border-brand-600');
    }

    function showClientError(message) {
        if (!reasonError) {
            return;
        }
        reasonError.textContent = message;
        reasonError.classList.remove('hidden');
        reasonInput?.setAttribute('aria-invalid', 'true');
        reasonInput?.setAttribute('aria-describedby', 'reason-error');
        reasonInput?.classList.remove('border-gray-300', 'focus:border-brand-600');
        reasonInput?.classList.add('border-red-600');
        reasonInput?.focus();
    }

    // 1. Klik tautan "Pilih" pada baris tabel pengguna (progressive enhancement)
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-select-user]');

        if (!trigger) {
            return;
        }

        // Biarkan klik dengan modifier (Ctrl/Cmd/Shift/klik tengah) membuka tab baru seperti biasa
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();

        const userId = trigger.dataset.userId;
        const userName = trigger.dataset.userName || '';
        const userEmail = trigger.dataset.userEmail || '';
        const userStatus = trigger.dataset.userStatus || 'ACTIVE';
        const userStatusLabel = trigger.dataset.userStatusLabel || '';
        const suspendUrl = trigger.dataset.suspendUrl || '';
        const activateUrl = trigger.dataset.activateUrl || '';
        const canModify = trigger.dataset.canModify === 'true';
        const guardMessage = trigger.dataset.guardMessage || '';

        const isActive = userStatus === 'ACTIVE';

        // Highlight baris terpilih
        document.querySelectorAll('tr[data-user-row]').forEach((row) => {
            row.classList.toggle('bg-brand-50/40', row.dataset.userRow === userId);
        });

        // Perbarui teks panel
        if (nameEl) {
            nameEl.textContent = userName;
        }
        if (emailEl) {
            emailEl.textContent = userEmail;
        }

        // Perbarui badge status (konsisten dengan <x-status-badge />)
        if (badgeEl) {
            badgeEl.textContent = userStatusLabel;
            badgeEl.classList.toggle('bg-brand-50', isActive);
            badgeEl.classList.toggle('text-brand-800', isActive);
            badgeEl.classList.toggle('ring-brand-100', isActive);
            badgeEl.classList.toggle('bg-red-50', !isActive);
            badgeEl.classList.toggle('text-red-700', !isActive);
            badgeEl.classList.toggle('ring-red-200', !isActive);
        }

        // Perbarui pesan guard bila ada (diri sendiri / admin terakhir)
        if (guardNoticeEl) {
            guardNoticeEl.textContent = guardMessage;
            guardNoticeEl.classList.toggle('hidden', canModify || !guardMessage);
        }

        // Perbarui form action & state
        form.action = isActive ? suspendUrl : activateUrl;
        form.dataset.currentStatus = userStatus;
        form.dataset.targetName = userName;

        if (reasonInput) {
            reasonInput.value = '';
            reasonInput.disabled = !canModify;
        }

        clearClientError();

        // Tombol aksi konsisten dengan gaya row-actions & button di Kategori/Material
        if (submitBtn) {
            submitBtn.disabled = !canModify;
            submitBtn.textContent = isActive ? 'Tangguhkan akun' : 'Aktifkan kembali';
            submitBtn.classList.toggle('border-red-300', isActive);
            submitBtn.classList.toggle('bg-white', isActive);
            submitBtn.classList.toggle('text-red-700', isActive);
            submitBtn.classList.toggle('hover:bg-red-50', isActive);
            submitBtn.classList.toggle('border-brand-600', !isActive);
            submitBtn.classList.toggle('bg-brand-600', !isActive);
            submitBtn.classList.toggle('text-white', !isActive);
            submitBtn.classList.toggle('hover:bg-brand-700', !isActive);
        }

        if (window.innerWidth < 1024) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        if (canModify && reasonInput) {
            reasonInput.focus({ preventScroll: window.innerWidth < 1024 });
        }
    });

    reasonInput?.addEventListener('input', clearClientError);

    // 2. Intersep submit form untuk menampilkan <dialog> konfirmasi
    if (dialog && typeof dialog.showModal === 'function') {
        form.addEventListener(
            'submit',
            (event) => {
                if (confirmedByDialog) {
                    confirmedByDialog = false;

                    return;
                }

                // Tahan submit agar listener data-submit-once tidak mengunci tombol sebelum dikonfirmasi
                event.preventDefault();
                event.stopImmediatePropagation();

                const reason = (reasonInput?.value || '').trim();

                if (reason.length === 0) {
                    showClientError('Alasan wajib diisi.');

                    return;
                }

                if (reason.length < 5) {
                    showClientError('Alasan minimal 5 karakter.');

                    return;
                }

                clearClientError();

                const isSuspending = form.dataset.currentStatus === 'ACTIVE';
                const targetName = form.dataset.targetName || 'pengguna ini';

                if (dialogTitle) {
                    dialogTitle.textContent = isSuspending
                        ? `Tangguhkan akun “${targetName}”?`
                        : `Aktifkan kembali akun “${targetName}”?`;
                }

                if (dialogDesc) {
                    dialogDesc.textContent = isSuspending
                        ? 'Seluruh sesi aktif pengguna akan langsung dicabut di semua perangkat dan mereka tidak dapat membuat listing atau checkout baru.'
                        : 'Akun pengguna akan kembali aktif dan dapat masuk serta bertransaksi seperti biasa.';
                }

                if (dialogReasonPreview) {
                    dialogReasonPreview.textContent = reason;
                }

                if (dialogConfirmBtn) {
                    dialogConfirmBtn.textContent = isSuspending ? 'Ya, tangguhkan' : 'Ya, aktifkan';
                    dialogConfirmBtn.classList.toggle('border-red-300', isSuspending);
                    dialogConfirmBtn.classList.toggle('bg-white', isSuspending);
                    dialogConfirmBtn.classList.toggle('text-red-700', isSuspending);
                    dialogConfirmBtn.classList.toggle('hover:bg-red-50', isSuspending);
                    dialogConfirmBtn.classList.toggle('border-brand-600', !isSuspending);
                    dialogConfirmBtn.classList.toggle('bg-brand-600', !isSuspending);
                    dialogConfirmBtn.classList.toggle('text-white', !isSuspending);
                    dialogConfirmBtn.classList.toggle('hover:bg-brand-700', !isSuspending);
                }

                dialog.returnValue = '';
                dialog.showModal();
            },
            true // capture phase agar berjalan sebelum initSubmitOnce() di forms.js
        );

        dialogCancelBtn?.addEventListener('click', () => {
            dialog.close('cancel');
        });

        // Tutup lewat backdrop hanya bila pointerdown dan click sama-sama mengenai <dialog>
        dialog.addEventListener('pointerdown', (event) => {
            pressedOnBackdrop = event.target === dialog;
        });

        dialog.addEventListener('click', (event) => {
            if (pressedOnBackdrop && event.target === dialog) {
                dialog.close('cancel');
            }
            pressedOnBackdrop = false;
        });

        dialog.addEventListener('close', () => {
            if (dialog.returnValue !== 'confirm') {
                submitBtn?.focus();
            }
        });

        dialogConfirmBtn?.addEventListener('click', () => {
            confirmedByDialog = true;
            dialog.close('confirm');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }
}
