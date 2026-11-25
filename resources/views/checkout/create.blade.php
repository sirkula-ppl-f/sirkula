<x-layouts.app title="Checkout">
    <div class="mb-6">
        <a href="{{ route('cart.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
            &larr; Kembali ke keranjang
        </a>

        <h1 class="mt-3 text-xl font-semibold text-gray-900">
            Checkout
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            Periksa kembali barang dan harga sebelum membuat pesanan.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">
            <p class="font-semibold">Checkout belum dapat diproses.</p>

            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (collect($items)->contains(fn ($item) => $item['price_changed']))
        <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800" role="alert">
            <p class="font-semibold">Ada perubahan harga barang.</p>
            <p class="mt-1">
                Beberapa barang mengalami perubahan harga sejak ditambahkan ke keranjang.
                Periksa harga terbaru sebelum mengonfirmasi pesanan.
            </p>
        </div>
    @endif

    <section class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h2 class="mb-2 text-base font-semibold text-gray-900">
            Informasi Penjual
        </h2>

        <p class="text-sm text-gray-700">
            {{ $seller->name }}
        </p>
    </section>

    <section class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h2 class="mb-4 text-base font-semibold text-gray-900">
            Ringkasan Pesanan
        </h2>

        <ul class="divide-y divide-gray-100">
            @foreach ($items as $item)
                <li class="flex flex-wrap items-start justify-between gap-4 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900">
                            {{ $item['title'] }}
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ $item['quantity'] }} x
                            Rp{{ number_format($item['unit_price'], 0, ',', '.') }}
                        </p>

                        <p class="text-xs text-gray-500">
                            {{ $item['type_label'] }}
                        </p>

                        @if ($item['price_changed'])
                            <p class="mt-2 text-xs font-medium text-amber-700">
                                Harga berubah dari
                                Rp{{ number_format($item['price_seen'], 0, ',', '.') }}
                                menjadi
                                Rp{{ number_format($item['unit_price'], 0, ',', '.') }}.
                            </p>
                        @endif
                    </div>

                    <p class="font-medium text-gray-900">
                        Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                    </p>
                </li>
            @endforeach
        </ul>

        <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
            <span class="font-semibold text-gray-900">
                Total
            </span>

            <span class="text-lg font-bold text-gray-900">
                Rp{{ number_format($total, 0, ',', '.') }}
            </span>
        </div>
    </section>

    <form
        action="{{ route('checkout.store', ['seller' => $seller->id]) }}"
        method="POST"
    >
        @csrf

        <input
            type="hidden"
            name="checkout_key"
            value="{{ $checkoutKey }}"
        >

        <input
            type="hidden"
            name="preview_hash"
            value="{{ $previewHash }}"
        >

        <button
            type="submit"
            class="w-full rounded-lg bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-700"
        >
            Konfirmasi &amp; Buat Pesanan
        </button>

        <p class="mt-3 text-center text-xs text-gray-500">
            Harga dan ketersediaan barang akan divalidasi kembali saat pesanan dibuat.
            Jika ada perubahan setelah halaman ini dibuka, kamu perlu meninjau ulang checkout.
        </p>
    </form>
</x-layouts.app>
