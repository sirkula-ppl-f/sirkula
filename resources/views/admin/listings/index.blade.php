@use('App\Enums\ListingModerationStatus')
@use('App\Enums\ListingType')
@use('App\Services\ListingModerationService')
@use('App\Support\IndonesianFormat')
@use('App\Support\ListingStatusLabel')

@php
    $hasFilters = collect($filters)->contains(fn ($value) => $value !== null);
    $columns = __('listing-moderation.admin.columns');
    $filterText = __('listing-moderation.admin.filter');
    $none = __('listing-moderation.admin.none');
    $typeOptions = collect(ListingType::cases())->mapWithKeys(fn (ListingType $type) => [$type->value => $type->label()])->all();
    $reviewOptions = collect(ListingModerationService::REVIEW_FILTER_VALUES)->mapWithKeys(fn (string $value) => [$value => ListingStatusLabel::review($value)])->all();
    $moderationOptions = collect(ListingModerationStatus::cases())->mapWithKeys(fn (ListingModerationStatus $status) => [$status->value => $status->label()])->all();
@endphp

<x-layouts.app :title="__('listing-moderation.admin.index_title')">
    <x-alert type="error" :message="session('error')" class="mb-6" />

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-2xl text-sm text-gray-700">{{ __('listing-moderation.admin.intro') }}</p>

        <a
            href="{{ route('admin.listings.moderations') }}"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
            dusk="listing-moderations-link"
        >{{ __('listing-moderation.admin.history_link') }}</a>
    </div>

    <form
        method="GET"
        action="{{ route('admin.listings.index') }}"
        role="search"
        aria-label="{{ __('listing-moderation.admin.filter_label') }}"
        class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 sm:items-end xl:grid-cols-4"
        dusk="listing-filter-form"
    >
        <x-form.input name="cari" type="search" :label="$filterText['search']" :value="$filters['cari']" maxlength="100" dusk="listing-search" />

        <x-form.select name="tipe" :label="$filterText['type']" :placeholder="$filterText['all_types']" :options="$typeOptions" :value="$filters['tipe']?->value" dusk="listing-type-filter" />

        <x-form.select name="kategori" :label="$filterText['category']" :placeholder="$filterText['all_categories']" :options="$options['categories']" :value="$filters['kategori']" dusk="listing-category-filter" />

        <x-form.select name="hub" :label="$filterText['hub']" :placeholder="$filterText['all_hubs']" :options="$options['hubs']" :value="$filters['hub']" dusk="listing-hub-filter" />

        <x-form.select name="provinsi" :label="$filterText['region']" :placeholder="$filterText['all_regions']" :options="$options['provinces']" :value="$filters['provinsi']" dusk="listing-region-filter" />

        <x-form.select name="verifikasi" :label="$filterText['review']" :placeholder="$filterText['all_reviews']" :options="$reviewOptions" :value="$filters['verifikasi']" dusk="listing-review-filter" />

        <x-form.select name="moderasi" :label="$filterText['moderation']" :placeholder="$filterText['all_moderations']" :options="$moderationOptions" :value="$filters['moderasi']?->value" dusk="listing-moderation-filter" />

        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="listing-filter-button"
            >{{ $filterText['apply'] }}</button>

            @if ($hasFilters)
                <a
                    href="{{ route('admin.listings.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="listing-filter-reset"
                >{{ $filterText['reset'] }}</a>
            @endif
        </div>
    </form>

    {{--
        Di layar sempit tabel di-scroll di dalam wadah ini, halaman tidak ikut bergeser.
        Kelas relative membuat span sr-only (position:absolute) di tautan Detail ikut terpotong wadah.
    --}}
    <div
        class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        role="region"
        aria-label="{{ __('listing-moderation.admin.table_label') }}"
        tabindex="0"
        dusk="listing-table-region"
    >
        <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="listing-table">
            <caption class="sr-only">
                {{ __('listing-moderation.admin.table_caption', ['page' => $listings->currentPage(), 'last' => max($listings->lastPage(), 1)]) }}
            </caption>

            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3">{{ $columns['listing'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['type'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['category'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['seller'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['hub'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['region'] }}</th>
                    <th scope="col" class="px-4 py-3 text-right">{{ $columns['price'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['review'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['moderation'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['catalog'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['action'] }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse ($listings as $listing)
                    <tr dusk="listing-row-{{ $listing->id }}">
                        <th scope="row" class="min-w-48 max-w-xs px-4 py-3 text-left font-medium text-gray-900">
                            <span class="wrap-break-word">{{ $listing->title }}</span>
                            <span class="block text-xs font-normal text-gray-500">{{ __('listing-moderation.admin.listing_id', ['id' => $listing->id]) }}</span>
                        </th>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ ListingType::tryFrom((string) $listing->listing_type)?->label() ?? ($listing->listing_type ?: $none) }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $listing->category?->name ?? $none }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $listing->seller?->name ?? $none }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $listing->hub?->name ?? $none }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $listing->region?->name ?? $none }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900">{{ IndonesianFormat::rupiah((int) $listing->price) }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ ListingStatusLabel::review($listing->review_status) }}</td>
                        <td class="px-4 py-3">
                            <x-listing-moderation.status-badge :status="$listing->moderation_status" />
                        </td>
                        <td class="px-4 py-3 text-gray-800" dusk="listing-catalog-{{ $listing->id }}">{{ $listing->isPurchasable() ? __('listing-moderation.catalog.yes') : __('listing-moderation.catalog.no') }}</td>
                        <td class="px-4 py-3">
                            <a
                                href="{{ route('admin.listings.show', $listing) }}"
                                class="font-semibold text-brand-700 underline-offset-4 hover:underline"
                                dusk="listing-detail-link-{{ $listing->id }}"
                            >{{ __('listing-moderation.admin.detail_link') }}<span class="sr-only"> {{ __('listing-moderation.admin.detail_link_context', ['title' => (string) $listing->title]) }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-600" dusk="listing-empty">
                            @if ($hasFilters)
                                {{ __('listing-moderation.admin.empty_filtered') }}
                                <a href="{{ route('admin.listings.index') }}" class="font-semibold text-brand-700 underline underline-offset-4" dusk="listing-empty-reset">{{ __('listing-moderation.admin.empty_reset') }}</a>
                            @else
                                {{ __('listing-moderation.admin.empty') }}
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $listings->links() }}
    </div>
</x-layouts.app>
