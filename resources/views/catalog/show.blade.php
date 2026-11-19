@use('App\Enums\ListingType')
@use('App\Support\IndonesianFormat')
@use('Illuminate\Support\Facades\Storage')

@php
    $listingType = ListingType::tryFrom($listing->listing_type ?? '');
    $typeStyles = [
        ListingType::Preloved->value => [
            'badge' => 'bg-sky-50 text-sky-800 ring-sky-600/20',
            'photo' => 'bg-sky-50/70 text-sky-800',
        ],
        ListingType::Recyclable->value => [
            'badge' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
            'photo' => 'bg-emerald-50/70 text-emerald-800',
        ],
        ListingType::Upcycle->value => [
            'badge' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            'photo' => 'bg-amber-50/70 text-amber-800',
        ],
    ];
    $style = $typeStyles[$listingType?->value ?? ''] ?? [
        'badge' => 'bg-gray-100 text-gray-800 ring-gray-300',
        'photo' => 'bg-gray-100 text-gray-600',
    ];

    $images = $listing->images->sortByDesc('is_primary')->values();
    $primaryImage = $images->first();
    $regionName = $effectiveRegion?->name ?? 'Belum tersedia';
    $unitLabel = strtolower($listing->sale_unit ?? 'item');
    $isOwnListing = auth()->check() && (int) auth()->id() === (int) $listing->user_id;
@endphp

<x-layouts.app :title="$listing->title">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
            <a
                href="{{ route('catalog.index') }}"
                dusk="back-to-catalog"
                class="inline-flex items-center text-sm font-semibold text-brand-700 hover:underline"
            >
                &larr; Kembali ke katalog
            </a>

            <nav aria-label="Breadcrumb" class="text-xs text-gray-500">
                <a href="{{ route('catalog.index') }}" class="hover:text-brand-700 hover:underline">Katalog</a>
                @if ($listingType)
                    <span class="mx-1">/</span>
                    <a
                        href="{{ route('catalog.index', ['type' => $listingType->value]) }}"
                        class="hover:text-brand-700 hover:underline"
                    >{{ $listingType->label() }}</a>
                @endif
                <span class="mx-1">/</span>
                <span class="text-gray-700">{{ $listing->title }}</span>
            </nav>
        </div>

        <article class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8" dusk="catalog-detail">
            <div class="grid gap-8 lg:grid-cols-2">
                {{-- Kolom Kiri: Galeri Foto --}}
                <div>
                    <div
                        class="flex aspect-4/3 w-full items-center justify-center overflow-hidden rounded-xl border border-gray-200 {{ $style['photo'] }}"
                        dusk="listing-photo-container"
                    >
                        @if ($primaryImage)
                            <img
                                id="main-listing-photo"
                                src="{{ Storage::url($primaryImage->path_foto) }}"
                                alt="{{ $listing->title }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <span class="text-sm font-medium text-gray-500">Foto produk belum tersedia</span>
                        @endif
                    </div>

                    @if ($images->count() > 1)
                        <div class="mt-3 grid grid-cols-5 gap-2" role="group" aria-label="Galeri foto produk">
                            @foreach ($images as $index => $image)
                                <button
                                    type="button"
                                    data-photo-thumb="{{ Storage::url($image->path_foto) }}"
                                    aria-label="Lihat foto {{ $index + 1 }}"
                                    @class([
                                        'aspect-square overflow-hidden rounded-lg border-2 transition focus:outline-none',
                                        'border-brand-600' => $index === 0,
                                        'border-gray-200 opacity-75 hover:opacity-100' => $index !== 0,
                                    ])
                                >
                                    <img
                                        src="{{ Storage::url($image->path_foto) }}"
                                        alt="{{ $listing->title }} ({{ $index + 1 }})"
                                        class="h-full w-full object-cover"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Kolom Kanan: Rincian Listing, Harga Acuan & Keranjang --}}
                <div class="flex flex-col">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $style['badge'] }}">
                            {{ $listingType?->label() ?? 'Listing' }}
                        </span>

                        @if ($listing->review_status === \App\Models\Listing::REVIEW_APPROVED)
                            <span class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-100 ring-inset">
                                Terverifikasi Hub
                            </span>
                        @endif
                    </div>

                    <h2 class="mt-3 text-2xl font-bold text-gray-900">
                        {{ $listing->title }}
                    </h2>

                    <p class="mt-3 text-2xl font-bold text-brand-700" dusk="listing-price">
                        Rp{{ number_format($listing->price, 0, ',', '.') }}
                        <span class="text-base font-medium text-gray-600">
                            / {{ $unitLabel }}
                        </span>
                    </p>

                    {{-- Tabel Spesifikasi --}}
                    <dl class="mt-5 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-gray-50/60 px-4 py-2 text-sm">
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Kategori</dt>
                            <dd class="text-right font-medium text-gray-900">
                                {{ $listing->category?->name ?? 'Belum dikategorikan' }}
                            </dd>
                        </div>

                        @if ($listing->material)
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-gray-500">Jenis material</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $listing->material->name }}
                                </dd>
                            </div>
                        @endif

                        @if ($listing->item_condition)
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-gray-500">Kondisi</dt>
                                <dd class="text-right font-medium text-gray-900">
                                    {{ $listing->item_condition }}
                                </dd>
                            </div>
                        @endif

                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Satuan jual</dt>
                            <dd class="text-right font-medium text-gray-900">
                                Per {{ $unitLabel }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Stok tersedia</dt>
                            <dd class="text-right font-medium text-gray-900" dusk="listing-stock">
                                {{ $listing->stock }} {{ $unitLabel }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Wilayah</dt>
                            <dd class="text-right font-medium text-gray-900">
                                {{ $regionName }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-gray-500">Hub</dt>
                            <dd class="text-right font-medium text-gray-900">
                                {{ $listing->hub?->name ?? 'Belum tersedia' }}
                            </dd>
                        </div>
                    </dl>

                    {{-- Informasi Harga Acuan Material (FR-008 & FR-012) --}}
                    @if ($listing->material || $listingType === ListingType::Recyclable)
                        <div
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-950"
                            dusk="listing-reference-price"
                        >
                            @if ($referencePrice)
                                <p class="text-xs font-semibold text-amber-800">
                                    Harga acuan material · {{ $listing->material->name }} ({{ $referencePrice->region->name }})
                                </p>
                                <p class="mt-1 text-base font-bold text-gray-900">
                                    {{ IndonesianFormat::pricePerKg($referencePrice->price_per_kg) }}
                                </p>
                                <p class="mt-1 text-xs text-amber-800">
                                    Berlaku sejak {{ IndonesianFormat::date($referencePrice->effective_date) }} · Sumber: {{ $referencePrice->source }}.
                                    Informatif, tidak mengubah harga yang ditetapkan penjual.
                                </p>
                            @else
                                <p class="text-xs font-semibold text-amber-800">Harga acuan material</p>
                                <p class="mt-1 text-xs text-amber-900">
                                    Harga acuan belum tersedia untuk material dan wilayah ini.
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Form Tambah ke Keranjang (PBI-11 / FR-014) --}}
                    <div class="mt-5 border-t border-gray-100 pt-5">
                        @auth
                            @if ($isOwnListing)
                                <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600" dusk="own-listing-notice">
                                    Ini adalah listing milik Anda sendiri.
                                </p>
                            @else
                                <form method="POST" action="{{ route('cart.store') }}" data-submit-once class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="listing_id" value="{{ $listing->id }}">

                                    <div class="flex flex-wrap items-center gap-3">
                                        <label for="quantity" class="text-sm font-medium text-gray-700">
                                            Jumlah ({{ $unitLabel }})
                                        </label>

                                        <input
                                            id="quantity"
                                            name="quantity"
                                            type="number"
                                            min="1"
                                            max="{{ $listing->stock }}"
                                            value="{{ old('quantity', 1) }}"
                                            dusk="add-to-cart-qty"
                                            class="w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs focus:border-brand-600"
                                        >
                                    </div>

                                    @error('listing_id')
                                        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
                                    @enderror
                                    @error('quantity')
                                        <p class="text-sm text-red-700" role="alert">{{ $message }}</p>
                                    @enderror

                                    <button
                                        type="submit"
                                        dusk="add-to-cart-button"
                                        class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-xs transition hover:bg-brand-700 disabled:opacity-60"
                                    >
                                        Tambah ke Keranjang
                                    </button>
                                </form>
                            @endif
                        @else
                            <div class="space-y-3">
                                <a
                                    href="{{ route('login') }}"
                                    dusk="login-to-buy-button"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-xs transition hover:bg-brand-700"
                                >
                                    Masuk untuk Membeli
                                </a>
                            </div>
                        @endauth
                    </div>

                    {{-- Informasi Penjual --}}
                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50/60 p-4 text-sm">
                        <p class="text-xs text-gray-500">Penjual</p>
                        <p class="mt-0.5 font-semibold text-gray-900">
                            {{ $listing->seller?->name ?? 'Belum tersedia' }}
                        </p>
                        @if ($listing->hub)
                            <p class="mt-0.5 text-xs text-gray-600">
                                Terdaftar di {{ $listing->hub->name }} · Listing disetujui pengelola hub
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Deskripsi & Informasi Produk --}}
            <section class="mt-8 border-t border-gray-100 pt-6">
                <h3 class="text-lg font-semibold text-gray-900">Informasi produk</h3>
                <p class="mt-3 whitespace-pre-line leading-relaxed text-gray-700">
                    {{ $listing->description ?: 'Informasi produk ditampilkan berdasarkan data listing yang tersedia.' }}
                </p>
            </section>

            {{-- Cuplikan Panduan Kontekstual (PBI-18 / FR-024) --}}
            <div class="mt-6">
                <x-guide-tip placement="detail_page" />
            </div>

            <div class="mt-8 border-t border-gray-100 pt-6">
                <a
                    href="{{ route('catalog.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 transition hover:bg-gray-50"
                >
                    Kembali ke katalog
                </a>
            </div>
        </article>
    </div>

    @if ($images->count() > 1)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const mainPhoto = document.getElementById('main-listing-photo');
                const thumbs = document.querySelectorAll('[data-photo-thumb]');

                thumbs.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (mainPhoto) {
                            mainPhoto.src = btn.dataset.photoThumb;
                        }
                        thumbs.forEach(function (t) {
                            t.classList.remove('border-brand-600');
                            t.classList.add('border-gray-200', 'opacity-75');
                        });
                        btn.classList.remove('border-gray-200', 'opacity-75');
                        btn.classList.add('border-brand-600');
                    });
                });
            });
        </script>
    @endif
</x-layouts.app>
