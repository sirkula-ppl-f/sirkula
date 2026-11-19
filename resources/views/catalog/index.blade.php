@use('App\Enums\ListingType')
@use('Illuminate\Support\Facades\Storage')

@php
    $hasFilters = $search !== ''
        || $type !== ''
        || ($categoryId !== null && $categoryId !== '')
        || $condition !== ''
        || ($regionId !== null && $regionId !== '')
        || ($hubId !== null && $hubId !== '')
        || ($minPrice !== null && $minPrice !== '')
        || ($maxPrice !== null && $maxPrice !== '')
        || $sort !== 'newest';

    $filterParams = array_filter([
        'search' => $search !== '' ? $search : null,
        'type' => $type !== '' ? $type : null,
        'category_id' => $categoryId,
        'condition' => $condition !== '' ? $condition : null,
        'region_id' => $regionId,
        'hub_id' => $hubId,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'sort' => $sort !== 'newest' ? $sort : null,
    ], fn ($val) => $val !== null && $val !== '');

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
@endphp

<x-layouts.app title="Katalog Sirkula">
    <p class="-mt-4 mb-6 max-w-3xl text-sm text-gray-600">
        Jelajahi barang bekas layak pakai, material daur ulang terpilah, dan produk upcycle dari berbagai wilayah dan Hub Komunitas di Indonesia.
    </p>

    {{-- Form Pencarian --}}
    <form
        method="GET"
        action="{{ route('catalog.index') }}"
        role="search"
        aria-label="Pencarian katalog"
        class="mb-4 flex flex-col gap-3 sm:flex-row"
    >
        @if ($type !== '')
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        @if ($categoryId !== null && $categoryId !== '')
            <input type="hidden" name="category_id" value="{{ $categoryId }}">
        @endif
        @if ($condition !== '')
            <input type="hidden" name="condition" value="{{ $condition }}">
        @endif
        @if ($regionId !== null && $regionId !== '')
            <input type="hidden" name="region_id" value="{{ $regionId }}">
        @endif
        @if ($hubId !== null && $hubId !== '')
            <input type="hidden" name="hub_id" value="{{ $hubId }}">
        @endif
        @if ($minPrice !== null && $minPrice !== '')
            <input type="hidden" name="min_price" value="{{ $minPrice }}">
        @endif
        @if ($maxPrice !== null && $maxPrice !== '')
            <input type="hidden" name="max_price" value="{{ $maxPrice }}">
        @endif
        @if ($sort !== 'newest')
            <input type="hidden" name="sort" value="{{ $sort }}">
        @endif

        <div class="flex-1">
            <label for="catalog-search" class="sr-only">Cari berdasarkan judul atau deskripsi</label>
            <input
                id="catalog-search"
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari berdasarkan judul atau deskripsi..."
                dusk="catalog-search-input"
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-600"
            >
        </div>

        <button
            type="submit"
            dusk="catalog-search-button"
            class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-brand-700"
        >
            Cari
        </button>
    </form>

    {{-- Chip Filter Tipe Listing --}}
    <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filter tipe listing">
        <a
            href="{{ route('catalog.index', collect($filterParams)->except('type')->all()) }}"
            dusk="catalog-type-all"
            @class([
                'inline-flex items-center rounded-full border px-4 py-1.5 text-xs font-semibold transition',
                'border-brand-600 bg-brand-600 text-white' => $type === '',
                'border-gray-300 bg-white text-gray-700 hover:border-brand-600 hover:text-brand-700' => $type !== '',
            ])
            @if ($type === '') aria-current="true" @endif
        >
            Semua
        </a>

        @foreach ($listingTypes as $listingType)
            @php
                $isActiveType = $type === $listingType->value;
            @endphp
            <a
                href="{{ route('catalog.index', array_merge(collect($filterParams)->except('type')->all(), ['type' => $listingType->value])) }}"
                dusk="catalog-type-{{ strtolower($listingType->value) }}"
                @class([
                    'inline-flex items-center rounded-full border px-4 py-1.5 text-xs font-semibold transition',
                    'border-brand-600 bg-brand-600 text-white' => $isActiveType,
                    'border-gray-300 bg-white text-gray-700 hover:border-brand-600 hover:text-brand-700' => ! $isActiveType,
                ])
                @if ($isActiveType) aria-current="true" @endif
            >
                {{ $listingType->label() }}
            </a>
        @endforeach
    </div>

    {{-- Form Filter Lanjutan & Pengurutan --}}
    <form
        method="GET"
        action="{{ route('catalog.index') }}"
        aria-label="Filter dan pengurutan katalog"
        class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200"
    >
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter-category" class="block text-xs font-medium text-gray-700">Kategori</label>
                <select
                    id="filter-category"
                    name="category_id"
                    dusk="catalog-filter-category"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs focus:border-brand-600"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" @selected((string) $categoryId === (string) $categoryOption->id)>
                            {{ $categoryOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-condition" class="block text-xs font-medium text-gray-700">Kondisi</label>
                <select
                    id="filter-condition"
                    name="condition"
                    dusk="catalog-filter-condition"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs focus:border-brand-600"
                >
                    <option value="">Semua Kondisi</option>
                    @foreach ($conditions as $conditionOption)
                        <option value="{{ $conditionOption->value }}" @selected($condition === $conditionOption->value)>
                            {{ $conditionOption->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-region" class="block text-xs font-medium text-gray-700">Wilayah</label>
                <select
                    id="filter-region"
                    name="region_id"
                    dusk="catalog-filter-region"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs focus:border-brand-600"
                >
                    <option value="">Semua Wilayah</option>
                    @foreach ($regions as $regionOption)
                        <option value="{{ $regionOption->id }}" @selected((string) $regionId === (string) $regionOption->id)>
                            {{ $regionOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-hub" class="block text-xs font-medium text-gray-700">Hub Komunitas</label>
                <select
                    id="filter-hub"
                    name="hub_id"
                    dusk="catalog-filter-hub"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs focus:border-brand-600"
                >
                    <option value="">Semua Hub</option>
                    @foreach ($hubs as $hubOption)
                        <option value="{{ $hubOption->id }}" @selected((string) $hubId === (string) $hubOption->id)>
                            {{ $hubOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-min-price" class="block text-xs font-medium text-gray-700">Harga Minimum (Rp)</label>
                <input
                    id="filter-min-price"
                    type="number"
                    min="0"
                    name="min_price"
                    value="{{ $minPrice }}"
                    placeholder="Harga min"
                    dusk="catalog-filter-min-price"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs placeholder:text-gray-400 focus:border-brand-600"
                >
            </div>

            <div>
                <label for="filter-max-price" class="block text-xs font-medium text-gray-700">Harga Maksimum (Rp)</label>
                <input
                    id="filter-max-price"
                    type="number"
                    min="0"
                    name="max_price"
                    value="{{ $maxPrice }}"
                    placeholder="Harga maks"
                    dusk="catalog-filter-max-price"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs placeholder:text-gray-400 focus:border-brand-600"
                >
            </div>

            <div>
                <label for="filter-sort" class="block text-xs font-medium text-gray-700">Urutkan</label>
                <select
                    id="filter-sort"
                    name="sort"
                    dusk="catalog-filter-sort"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs focus:border-brand-600"
                >
                    <option value="newest" @selected($sort === 'newest')>Urutkan: Terbaru</option>
                    <option value="price_asc" @selected($sort === 'price_asc')>Harga Terendah</option>
                    <option value="price_desc" @selected($sort === 'price_desc')>Harga Tertinggi</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    dusk="catalog-filter-apply"
                    class="inline-flex items-center justify-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"
                >
                    Terapkan
                </button>

                <a
                    href="{{ route('catalog.index') }}"
                    dusk="catalog-filter-reset"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Ringkasan Jumlah Hasil --}}
    <p class="mb-4 text-xs text-gray-600" dusk="catalog-results-count">
        Menampilkan {{ $listings->count() }} dari {{ $listings->total() }} listing disetujui
    </p>

    {{-- Grid Kartu Katalog --}}
    <section aria-label="Daftar produk katalog" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" dusk="catalog-grid">
        @forelse ($listings as $listing)
            @php
                $listingType = ListingType::tryFrom($listing->listing_type ?? '');
                $style = $typeStyles[$listingType?->value ?? ''] ?? [
                    'badge' => 'bg-gray-100 text-gray-800 ring-gray-300',
                    'photo' => 'bg-gray-100 text-gray-600',
                ];
                $primaryImage = $listing->images->firstWhere('is_primary', true) ?? $listing->images->first();
                $regionName = $listing->region?->name ?? $listing->seller?->region?->name ?? 'Belum tersedia';
            @endphp

            <a
                href="{{ route('catalog.show', $listing->id) }}"
                dusk="catalog-card-{{ $listing->id }}"
                class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
            >
                <div class="relative flex h-40 w-full items-center justify-center overflow-hidden {{ $style['photo'] }}">
                    @if ($primaryImage)
                        <img
                            src="{{ Storage::url($primaryImage->path_foto) }}"
                            alt="{{ $listing->title }}"
                            class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                            loading="lazy"
                        >
                    @else
                        <span class="text-xs font-medium text-gray-500">Foto produk</span>
                    @endif
                </div>

                <div class="flex flex-1 flex-col p-4">
                    <div class="mb-2 flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $style['badge'] }}">
                            {{ $listingType?->label() ?? 'Listing' }}
                        </span>

                        @if ($listing->item_condition)
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700">
                                {{ $listing->item_condition }}
                            </span>
                        @endif
                    </div>

                    <h2 class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-brand-700">
                        {{ $listing->title }}
                    </h2>

                    <p class="mt-2 text-base font-bold text-brand-700">
                        Rp{{ number_format($listing->price, 0, ',', '.') }}
                        <span class="text-xs font-normal text-gray-500">
                            / {{ strtolower($listing->sale_unit ?? 'item') }}
                        </span>
                    </p>

                    <div class="mt-auto space-y-0.5 border-t border-gray-100 pt-3 text-xs text-gray-600">
                        <p class="truncate">Kategori: {{ $listing->category?->name ?? 'Belum dikategorikan' }}</p>
                        <p class="truncate">Wilayah: {{ $regionName }}</p>
                        @if ($listing->hub)
                            <p class="truncate">Hub: {{ $listing->hub->name }}</p>
                        @endif
                        <p>Stok tersedia: {{ $listing->stock }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200" dusk="catalog-empty">
                <h2 class="text-base font-semibold text-gray-900">Tidak ada listing ditemukan</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Coba ubah kata kunci atau sesuaikan filter pencarian Anda.
                </p>
                @if ($hasFilters)
                    <a
                        href="{{ route('catalog.index') }}"
                        class="mt-4 inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    >
                        Lihat semua katalog
                    </a>
                @endif
            </div>
        @endforelse
    </section>

    {{-- Paginasi --}}
    @if ($listings->hasPages())
        <div class="mt-6" dusk="catalog-pagination">
            {{ $listings->links() }}
        </div>
    @endif
</x-layouts.app>
