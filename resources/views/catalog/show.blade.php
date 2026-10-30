
<x-layouts.app :title="$listing['title']">
    <div class="mx-auto max-w-4xl">
        <a
            href="{{ route('catalog.index') }}"
            class="mb-6 inline-flex items-center font-medium text-brand-700 hover:underline"
        >
            &larr; Kembali ke katalog
        </a>

        <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="inline-flex rounded-md bg-green-100 px-3 py-1.5 text-xs font-bold text-green-800">
                {{ $listing['type'] }}
            </span>

            <h2 class="mt-5 text-2xl font-bold text-gray-900">
                {{ $listing['title'] }}
            </h2>

            <p class="mt-4 text-2xl font-bold text-brand-700">
                Rp{{ number_format($listing['price'], 0, ',', '.') }}
                @if (! empty($listing['unit']))
                    <span class="text-base font-medium text-gray-600">
                        / {{ $listing['unit'] }}
                    </span>
                @endif
            </p>

            <div class="mt-6 grid gap-4 border-t border-gray-100 pt-6 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">Kategori</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $listing['category'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Kondisi</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $listing['condition'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Wilayah</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $listing['region'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Hub</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $listing['hub'] }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Stok</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $listing['stock'] }}</p>
                </div>
            </div>

            <section class="mt-8 border-t border-gray-100 pt-6">
                <h3 class="text-lg font-semibold text-gray-900">Deskripsi produk</h3>
                <p class="mt-3 leading-relaxed text-gray-600">
                    {{ $listing['description'] }}
                </p>
            </section>

            <div class="mt-8">
                <a
                    href="{{ route('catalog.index') }}"
                    class="inline-flex rounded-lg bg-brand-700 px-5 py-3 font-semibold text-white hover:opacity-90"
                >
                    Kembali ke katalog
                </a>
            </div>
        </article>
    </div>
</x-layouts.app>
