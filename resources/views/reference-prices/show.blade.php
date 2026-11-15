@use('App\Support\IndonesianFormat')
@use('App\Support\TrendChart')

@php
    $pair = ['material' => $material->name, 'region' => $region->name];
    $columns = __('reference-prices.public.history.columns');
@endphp

<x-layouts.app :title="__('reference-prices.public.show_title', $pair)">
    <div class="mb-6 space-y-3 text-sm text-gray-700">
        <a
            href="{{ $backUrl }}"
            class="inline-flex items-center gap-1 rounded-sm font-semibold text-brand-700 underline underline-offset-4 hover:text-brand-800"
            dusk="reference-price-back"
        ><span aria-hidden="true">&larr;</span> {{ __('reference-prices.public.back') }}</a>

        <p class="max-w-3xl" dusk="reference-price-informative">{{ __('reference-prices.public.informative') }}</p>
    </div>

    {{-- grid-cols-1 dan min-w-0: teks panjang tanpa spasi (misalnya URL sumber) dipatahkan, tidak melebarkan halaman. --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] lg:items-start">
        <section
            aria-labelledby="latest-price-title"
            class="min-w-0 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6"
            dusk="reference-price-latest"
        >
            <h2 id="latest-price-title" class="text-base font-semibold text-gray-900">{{ __('reference-prices.public.latest.title') }}</h2>

            <p class="mt-2 text-3xl font-semibold text-brand-800" dusk="reference-price-latest-price">{{ IndonesianFormat::pricePerKg($latest->price_per_kg) }}</p>

            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-gray-600">{{ __('reference-prices.public.latest.effective_date') }}</dt>
                    <dd class="font-medium text-gray-900" dusk="reference-price-latest-date">{{ IndonesianFormat::date($latest->effective_date) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">{{ __('reference-prices.public.latest.source') }}</dt>
                    <dd class="wrap-break-word font-medium text-gray-900" dusk="reference-price-latest-source">{{ $latest->source }}</dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="trend-title" class="min-w-0 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6">
            <h2 id="trend-title" class="text-base font-semibold text-gray-900">{{ __('reference-prices.public.trend.title') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ __('reference-prices.public.trend.intro', ['max' => TrendChart::MAX_POINTS]) }}</p>

            <x-reference-price.trend-chart class="mt-4" :prices="$trend" :title="__('reference-prices.chart.title', $pair)" />
        </section>
    </div>

    {{-- Tabel riwayat juga menjadi alternatif teks untuk grafik. --}}
    <section aria-labelledby="history-title" class="mt-8">
        <h2 id="history-title" class="text-lg font-semibold text-gray-900">{{ __('reference-prices.public.history.title') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-gray-700">{{ __('reference-prices.public.history.intro') }}</p>

        <div
            class="relative mt-4 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            role="region"
            aria-label="{{ __('reference-prices.public.history.table_label') }}"
            tabindex="0"
            dusk="reference-price-history-region"
        >
            <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="reference-price-history-table">
                <caption class="sr-only">
                    {{ __('reference-prices.public.history.table_caption', [...$pair, 'page' => $history->currentPage(), 'last' => max($history->lastPage(), 1)]) }}
                </caption>

                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">{{ $columns['effective_date'] }}</th>
                        <th scope="col" class="px-4 py-3 text-right">{{ $columns['price'] }}</th>
                        <th scope="col" class="px-4 py-3">{{ $columns['source'] }}</th>
                        <th scope="col" class="px-4 py-3">{{ $columns['status'] }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @foreach ($history as $price)
                        <tr dusk="reference-price-history-row-{{ $price->id }}">
                            <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-medium text-gray-900">{{ IndonesianFormat::date($price->effective_date) }}</th>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ IndonesianFormat::pricePerKg($price->price_per_kg) }}</td>
                            <td class="min-w-48 max-w-xs px-4 py-3 wrap-break-word text-gray-800">{{ $price->source }}</td>
                            <td class="px-4 py-3">
                                <x-reference-price.status-badge :status="$price->statusOn($today)" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $history->links() }}
        </div>
    </section>
</x-layouts.app>
