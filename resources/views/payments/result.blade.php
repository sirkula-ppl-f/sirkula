@php
    $payment = $order->payment;

    // Status selalu dari database: halaman ini tidak membaca parameter dari redirect Midtrans.
    $state = match (true) {
        $order->status === \App\Models\Order::STATUS_PAID => 'berhasil',
        $order->status === \App\Models\Order::STATUS_PAYMENT_REVIEW => 'diperiksa',
        (bool) $payment?->status?->isFailure() => 'gagal',
        default => 'menunggu',
    };
@endphp

<x-layouts.app title="Hasil Pembayaran">
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200" aria-labelledby="hasil" dusk="payment-result" data-state="{{ $state }}">
        <div class="mb-2 flex flex-wrap items-center gap-3">
            <h2 id="hasil" class="text-lg font-semibold" dusk="payment-status">
                @switch($state)
                    @case('berhasil') Pembayaran berhasil @break
                    @case('diperiksa') Pembayaran sedang diperiksa @break
                    @case('gagal') Pembayaran gagal @break
                    @default Menunggu pembayaran
                @endswitch
            </h2>
            <span class="rounded-md bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800" dusk="sandbox-label">SANDBOX</span>
        </div>

        <p class="text-sm text-gray-600">Nomor pesanan <span class="font-medium text-gray-900">{{ $order->order_number }}</span></p>
        <p class="mt-1 text-sm text-gray-600">Total <span class="font-medium text-gray-900">Rp{{ number_format($order->total, 0, ',', '.') }}</span></p>

        <p class="mt-4 text-sm text-gray-700" dusk="payment-message">
            @switch($state)
                @case('berhasil')
                    Pesanan sudah LUNAS. Serah-terima barang dilakukan langsung antara pembeli dan penjual;
                    Sirkula tidak menangani pengiriman.
                    @break
                @case('diperiksa')
                    Status pembayaran sedang dicek ke payment gateway. Pesananmu tidak dianggap gagal.
                    @break
                @case('gagal')
                    Pembayaran tidak berhasil.
                    @break
                @default
                    Kami belum menerima konfirmasi pembayaran. Halaman ini diperbarui setelah notifikasi diterima.
            @endswitch
        </p>

        @if ($state === 'menunggu' && $order->isPayable())
            <a href="{{ route('payments.show', $order->id) }}" class="mt-4 inline-block text-sm font-medium text-brand-700 hover:underline" dusk="back-to-pay">
                Kembali ke halaman pembayaran
            </a>
        @endif
    </section>
</x-layouts.app>
