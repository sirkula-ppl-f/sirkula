<x-layouts.app title="Pembayaran">
    <p class="-mt-4 mb-6 text-sm text-gray-600">
        Selesaikan pembayaran pesananmu melalui Midtrans. Ini mode simulasi, tidak ada transaksi uang riil.
    </p>

    @error('payment')
        <x-alert type="error" :message="$message" class="mb-4" />
    @enderror

    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200" aria-labelledby="ringkasan" dusk="payment-summary">
        <h2 id="ringkasan" class="mb-4 text-base font-semibold">Ringkasan pembayaran</h2>

        <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-gray-600">Nomor pesanan</dt>
                <dd class="font-medium" dusk="order-number">{{ $order->order_number }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-600">Penjual</dt>
                <dd class="font-medium">{{ $order->seller?->name ?? 'Belum tersedia' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                <dt class="text-gray-600">Total pembayaran</dt>
                <dd class="text-lg font-semibold text-brand-700" dusk="order-total">Rp{{ number_format($order->total, 0, ',', '.') }}</dd>
            </div>
        </dl>

        <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800" dusk="sandbox-notice">
            Mode SANDBOX: tidak ada transaksi uang riil. Status LUNAS ditetapkan setelah server memverifikasi
            notifikasi dari payment gateway, bukan dari halaman ini.
        </p>

        <div class="mt-6">
            @if ($order->isPayable())
                <form method="POST" action="{{ route('payments.store', $order->id) }}" data-submit-once>
                    @csrf
                    <x-button dusk="pay-button">Bayar via Midtrans (Sandbox)</x-button>
                </form>
            @else
                <p class="text-sm text-gray-700" dusk="not-payable">Pesanan ini tidak dapat dibayar lagi.</p>
                <a href="{{ route('payments.result', $order->id) }}" class="mt-3 inline-block text-sm font-medium text-brand-700 hover:underline">
                    Lihat status pembayaran
                </a>
            @endif
        </div>
    </section>
</x-layouts.app>
