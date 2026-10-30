/**
 * Perilaku interaktif halaman Admin - Kelola Status Akun (FR-004) dengan JavaScript murni.
 *
 * - Memilih baris pengguna dari tabel untuk memperbarui panel detail & aksi di samping.
 * - Membuka <dialog> konfirmasi bawaan browser sebelum menangguhkan atau mengaktifkan kembali akun.
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
    const reasonError = document.getElementById('reason-client-error');
    const submitBtn = document.getElementById('panel-submit-button');

    const dialogTitle = document.getElementById('dialog-title');
    const dialogDesc = document.getElementById('dialog-description');
    const dialogReasonPreview = document.getElementById('dialog-reason-preview');
    const dialogCancelBtn = document.getElementById('dialog-cancel-button');
    const dialogConfirmBtn = document.getElementById('dialog-confirm-button');

    let confirmedByDialog = false;

    function clearClientError() {
        if (!reasonError) {
            return;
        }
        reasonError.textContent = '';
        reasonError.classList.add('hidden');
        reasonInput?.removeAttribute('aria-invalid');
    }

    function showClientError(message) {
        if (!reasonError) {
            return;
        }
        reasonError.textContent = message;
        reasonError.classList.remove('hidden');
        reasonInput?.setAttribute('aria-invalid', 'true');
        reasonInput?.focus();
    }

    // 1. Klik tombol "Pilih" pada baris tabel pengguna
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-select-user]');

        if (!trigger) {
            return;
        }

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
            row.classList.toggle('bg-[#f7faf7]', row.dataset.userRow === userId);
        });

        // Perbarui teks panel
        if (nameEl) {
            nameEl.textContent = userName;
        }
        if (emailEl) {
            emailEl.textContent = userEmail;
        }

        // Perbarui badge status
        if (badgeEl) {
            badgeEl.textContent = userStatusLabel;
            badgeEl.classList.toggle('bg-[#e0f5e8]', isActive);
            badgeEl.classList.toggle('text-[#127345]', isActive);
            badgeEl.classList.toggle('bg-[#ffe5e5]', !isActive);
            badgeEl.classList.toggle('text-[#b22626]', !isActive);
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

        if (submitBtn) {
            submitBtn.disabled = !canModify;
            submitBtn.textContent = isActive ? 'Tangguhkan akun' : 'Aktifkan kembali';
            submitBtn.classList.toggle('bg-[#c33]', isActive);
            submitBtn.classList.toggle('hover:bg-[#b22626]', isActive);
            submitBtn.classList.toggle('bg-[#178c5c]', !isActive);
            submitBtn.classList.toggle('hover:bg-[#127345]', !isActive);
        }

        if (canModify && reasonInput) {
            reasonInput.focus();
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

                // Sesuaikan tampilan dialog dengan jenis aksi (Tangguhkan vs Aktifkan)
                dialog.classList.toggle('border-[#c33]', isSuspending);
                dialog.classList.toggle('border-[#178c5c]', !isSuspending);

                if (dialogTitle) {
                    dialogTitle.textContent = isSuspending
                        ? `Tangguhkan akun ${targetName}?`
                        : `Aktifkan kembali akun ${targetName}?`;
                    dialogTitle.classList.toggle('text-[#c33]', isSuspending);
                    dialogTitle.classList.toggle('text-[#178c5c]', !isSuspending);
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
                    dialogConfirmBtn.classList.toggle('bg-[#c33]', isSuspending);
                    dialogConfirmBtn.classList.toggle('hover:bg-[#b22626]', isSuspending);
                    dialogConfirmBtn.classList.toggle('bg-[#178c5c]', !isSuspending);
                    dialogConfirmBtn.classList.toggle('hover:bg-[#127345]', !isSuspending);
                }

                dialog.showModal();
            },
            true // capture phase agar berjalan sebelum initSubmitOnce() di forms.js
        );

        dialogCancelBtn?.addEventListener('click', () => {
            dialog.close();
        });

        // Tutup dialog jika klik di area backdrop luar kotak dialog
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        dialogConfirmBtn?.addEventListener('click', () => {
            confirmedByDialog = true;
            dialog.close();

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }
}
