@use('App\Support\IndonesianFormat')

@php
    $hasFilters = collect($filters)->contains(fn ($value) => $value !== null);
    $columns = __('reference-prices.admin.columns');
@endphp

<x-layouts.app :title="__('reference-prices.admin.index_title')">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl space-y-2 text-sm text-gray-700">
            <p>{{ __('reference-prices.admin.intro') }}</p>
            <p dusk="reference-price-append-only">{{ __('reference-prices.admin.append_only') }}</p>
        </div>

        <a
            href="{{ route('admin.reference-prices.create') }}"
            class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
            dusk="reference-price-create-link"
        >{{ __('reference-prices.admin.create_link') }}</a>
    </div>

    <form
        method="GET"
        action="{{ route('admin.reference-prices.index') }}"
        role="search"
        aria-label="{{ __('reference-prices.admin.filter_label') }}"
        class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end"
    >
        <x-form.select
            name="material"
            :label="__('reference-prices.admin.filter.material')"
            :placeholder="__('reference-prices.admin.filter.all_materials')"
            :options="$materials"
            :value="$filters['material']"
            dusk="reference-price-material-filter"
        />

        <x-form.select
            name="provinsi"
            :label="__('reference-prices.admin.filter.region')"
            :placeholder="__('reference-prices.admin.filter.all_regions')"
            :options="$provinces"
            :value="$filters['provinsi']"
            dusk="reference-price-region-filter"
        />

        <x-form.input name="dari" type="date" :label="__('reference-prices.admin.filter.from')" :value="$filters['dari']" dusk="reference-price-from-filter" />

        <x-form.input name="sampai" type="date" :label="__('reference-prices.admin.filter.until')" :value="$filters['sampai']" dusk="reference-price-until-filter" />

        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="reference-price-filter-button"
            >{{ __('reference-prices.admin.filter.apply') }}</button>

            @if ($hasFilters)
                <a
                    href="{{ route('admin.reference-prices.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="reference-price-filter-reset"
                >{{ __('reference-prices.admin.filter.reset') }}</a>
            @endif
        </div>
    </form>

    {{-- Di layar sempit tabel di-scroll di dalam wadah ini, halaman tidak ikut bergeser. --}}
    <div
        class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        role="region"
        aria-label="{{ __('reference-prices.admin.table_label') }}"
        tabindex="0"
        dusk="reference-price-table-region"
    >
        <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="reference-price-table">
            <caption class="sr-only">
                {{ __('reference-prices.admin.table_caption', ['page' => $prices->currentPage(), 'last' => max($prices->lastPage(), 1)]) }}
            </caption>

            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3">{{ $columns['material'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['region'] }}</th>
                    <th scope="col" class="px-4 py-3 text-right">{{ $columns['price'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['effective_date'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['status'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['source'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['publisher'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $columns['published_at'] }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse ($prices as $price)
                    <tr dusk="reference-price-row-{{ $price->id }}">
                        <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900">
                            {{ $price->material->name }}
                            @unless ($price->material->is_active)
                                <x-status-badge :active="false" class="ms-1" />
                            @endunless
                        </th>
                        <td class="px-4 py-3 text-gray-800">{{ $price->region->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ IndonesianFormat::pricePerKg($price->price_per_kg) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ IndonesianFormat::date($price->effective_date) }}</td>
                        <td class="px-4 py-3">
                            <x-reference-price.status-badge :status="$price->statusOn($today)" />
                        </td>
                        <td class="min-w-48 max-w-xs px-4 py-3 wrap-break-word text-gray-800">{{ $price->source }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $price->publisher->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ IndonesianFormat::dateTimeWib($price->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-600" dusk="reference-price-empty">
                            {{ $hasFilters ? __('reference-prices.admin.empty_filtered') : __('reference-prices.admin.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $prices->links() }}
    </div>
</x-layouts.app>
