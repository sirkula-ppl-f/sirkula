/**
 * Hitung mundur sisa waktu bayar (JavaScript murni).
 *
 * - <p data-payment-countdown data-expires-at="..."> berisi waktu kedaluwarsa dari server.
 * - <span data-countdown-text> menampilkan sisa waktu.
 * - Saat waktu habis, teks diganti dan tombol bayar (data-pay-button) dinonaktifkan.
 * Server tetap menolak pembayaran setelah expires_at, jadi ini hanya tampilan.
 */

export function initPaymentCountdown() {
    const wrapper = document.querySelector('[data-payment-countdown]');

    if (!wrapper) {
        return;
    }

    const expiresAt = new Date(wrapper.dataset.expiresAt).getTime();
    const text = wrapper.querySelector('[data-countdown-text]');
    const payButton = document.querySelector('[data-pay-button]');

    if (Number.isNaN(expiresAt) || !text) {
        return;
    }

    let timer = null;

    const format = (ms) => {
        const total = Math.max(0, Math.floor(ms / 1000));
        const jam = String(Math.floor(total / 3600)).padStart(2, '0');
        const menit = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
        const detik = String(total % 60).padStart(2, '0');

        return `${jam}:${menit}:${detik}`;
    };

    const tick = () => {
        const sisa = expiresAt - Date.now();

        if (sisa <= 0) {
            text.textContent = 'Waktu pembayaran habis';
            payButton?.setAttribute('disabled', 'disabled');
            clearInterval(timer);

            return;
        }

        text.textContent = format(sisa);
    };

    tick();
    timer = setInterval(tick, 1000);
}