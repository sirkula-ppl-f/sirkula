@use('App\Enums\ListingType')
@use('Illuminate\Support\Facades\Storage')

@php
    $advancedFilterCount = collect([
        $categoryId !== null && $categoryId !== '' ? $categoryId : null,
        $condition !== '' ? $condition : null,
        $regionId !== null && $regionId !== '' ? $regionId : null,
        $hubId !== null && $hubId !== '' ? $hubId : null,
        $minPrice !== null && $minPrice !== '' ? $minPrice : null,
        $maxPrice !== null && $maxPrice !== '' ? $maxPrice : null,
        $sort !== 'newest' ? $sort : null,
    ])->filter()->count();

    $hasAdvancedFilters = $advancedFilterCount > 0;

    $hasFilters = $search !== ''
        || $type !== ''
        || $hasAdvancedFilters;

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
    <p class="-mt-4 mb-5 max-w-3xl text-sm text-gray-600">
        Jelajahi barang bekas layak pakai, material daur ulang terpilah, dan produk upcycle dari berbagai wilayah dan Hub Komunitas di Indonesia.
    </p>

    {{-- Form Pencarian (Satu baris ringkas baik di HP maupun Desktop) --}}
    <form
        method="GET"
        action="{{ route('catalog.index') }}"
        role="search"
        aria-label="Pencarian katalog"
        class="mb-3 flex items-center gap-2"
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

        <div class="min-w-0 flex-1">
            <label for="catalog-search" class="sr-only">Cari berdasarkan judul atau deskripsi</label>
            <input
                id="catalog-search"
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari berdasarkan judul atau deskripsi..."
                dusk="catalog-search-input"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-600 sm:px-4 sm:py-2.5"
            >
        </div>

        <button
            type="submit"
            dusk="catalog-search-button"
            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-xs transition hover:bg-brand-700 sm:px-5 sm:py-2.5"
        >
            Cari
        </button>
    </form>

    {{-- Chip Filter Tipe Listing + Tombol Lipat Filter di Seluler --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap gap-1.5 sm:gap-2" role="group" aria-label="Filter tipe listing">
            <a
                href="{{ route('catalog.index', collect($filterParams)->except('type')->all()) }}"
                dusk="catalog-type-all"
                @class([
                    'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition sm:px-4',
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
                        'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition sm:px-4',
                        'border-brand-600 bg-brand-600 text-white' => $isActiveType,
                        'border-gray-300 bg-white text-gray-700 hover:border-brand-600 hover:text-brand-700' => ! $isActiveType,
                    ])
                    @if ($isActiveType) aria-current="true" @endif
                >
                    {{ $listingType->label() }}
                </a>
            @endforeach
        </div>

        {{-- Tombol buka/tutup panel filter di perangkat seluler (< md) --}}
        <button
            type="button"
            hidden
            data-filter-toggle
            data-filter-active="{{ $hasAdvancedFilters ? 'true' : 'false' }}"
            aria-controls="catalog-filter-panel"
            aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"
            dusk="catalog-filter-toggle"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition hover:bg-gray-50 md:hidden"
        >
            <svg class="h-3.5 w-3.5 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
            </svg>
            <span>Filter &amp; Urutkan</span>
            @if ($advancedFilterCount > 0)
                <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-700 ring-1 ring-brand-600/20">
                    {{ $advancedFilterCount }}
                </span>
            @endif
            <svg data-filter-chevron class="h-3.5 w-3.5 text-gray-400 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    {{-- Form Filter Lanjutan & Pengurutan (2 kolom ringkas di HP, 4 kolom di Desktop) --}}
    <form
        id="catalog-filter-panel"
        method="GET"
        action="{{ route('catalog.index') }}"
        aria-label="Filter dan pengurutan katalog"
        class="mb-5 rounded-xl bg-white p-3.5 shadow-sm ring-1 ring-gray-200 sm:p-4"
    >
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4">
            <div>
                <label for="filter-category" class="block text-xs font-medium text-gray-700">Kategori</label>
                <select
                    id="filter-category"
                    name="category_id"
                    dusk="catalog-filter-category"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
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
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
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
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
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
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
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
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs placeholder:text-gray-400 focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
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
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs placeholder:text-gray-400 focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
                >
            </div>

            <div>
                <label for="filter-sort" class="block text-xs font-medium text-gray-700">Urutkan</label>
                <select
                    id="filter-sort"
                    name="sort"
                    dusk="catalog-filter-sort"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-2xs focus:border-brand-600 sm:px-3 sm:py-2 sm:text-sm"
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
                    class="inline-flex flex-1 items-center justify-center rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700 sm:flex-none sm:px-4 sm:py-2 sm:text-sm"
                >
                    Terapkan
                </button>

                <a
                    href="{{ route('catalog.index') }}"
                    dusk="catalog-filter-reset"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 sm:px-4 sm:py-2 sm:text-sm"
                >
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Ringkasan Jumlah Hasil --}}
    <p class="mb-3 text-xs text-gray-600 sm:mb-4" dusk="catalog-results-count">
        Menampilkan {{ $listings->count() }} dari {{ $listings->total() }} listing disetujui
    </p>

    {{-- Grid Kartu Katalog (2 kolom di HP seperti Shopee/Tokopedia, 3 kolom di tablet, 4 kolom di desktop) --}}
    <section aria-label="Daftar produk katalog" class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4" dusk="catalog-grid">
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
                class="group flex min-w-0 flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
            >
                <div class="relative flex h-32 w-full items-center justify-center overflow-hidden sm:h-40 {{ $style['photo'] }}">
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

                <div class="flex min-w-0 flex-1 flex-col p-2.5 sm:p-4">
                    <div class="mb-1.5 flex flex-wrap items-center gap-1 sm:mb-2 sm:gap-1.5">
                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset sm:px-2 sm:text-[11px] {{ $style['badge'] }}">
                            {{ $listingType?->label() ?? 'Listing' }}
                        </span>

                        @if ($listing->item_condition)
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-700 sm:px-2 sm:text-[11px]">
                                {{ $listing->item_condition }}
                            </span>
                        @endif
                    </div>

                    <h2 class="line-clamp-2 text-xs font-semibold text-gray-900 group-hover:text-brand-700 sm:text-sm">
                        {{ $listing->title }}
                    </h2>

                    <p class="mt-1.5 text-sm font-bold text-brand-700 sm:mt-2 sm:text-base">
                        Rp{{ number_format($listing->price, 0, ',', '.') }}
                        <span class="text-[11px] font-normal text-gray-500 sm:text-xs">
                            / {{ strtolower($listing->sale_unit ?? 'item') }}
                        </span>
                    </p>

                    <div class="mt-auto space-y-0.5 border-t border-gray-100 pt-2 text-[11px] text-gray-600 sm:pt-3 sm:text-xs">
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
