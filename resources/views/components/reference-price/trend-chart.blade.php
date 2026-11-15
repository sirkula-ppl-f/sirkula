@props([
    // Koleksi ReferencePrice, satu per tanggal berlaku (ReferencePriceService::trendFor()). Minimal satu.
    'prices',
    // Judul grafik untuk pembaca layar (<title>).
    'title',
    // Awalan id <title> dan <desc>. Ganti bila ada lebih dari satu grafik di satu halaman.
    'id' => 'trend-chart',
])

@use('App\Support\IndonesianFormat')
@use('App\Support\TrendChart')

@php
    // Semua koordinat dihitung di App\Support\TrendChart; Blade ini hanya merender.
    $chart = TrendChart::fromReferencePrices($prices);
    $first = $chart->first();
    $last = $chart->last();

    $summary = $chart->isSinglePoint()
        ? __('reference-prices.chart.summary_single', [
            'price' => IndonesianFormat::pricePerKg($last['price']),
            'date' => IndonesianFormat::date($last['date']),
        ])
        : __('reference-prices.chart.summary', [
            'from_price' => IndonesianFormat::pricePerKg($first['price']),
            'from_date' => IndonesianFormat::date($first['date']),
            'to_price' => IndonesianFormat::pricePerKg($last['price']),
            'to_date' => IndonesianFormat::date($last['date']),
            'count' => $chart->count(),
        ]);
@endphp

{{--
    SVG dari server tanpa JS. Lebar mengikuti wadah (maksimal 30rem), teks ikut diskalakan; ukuran viewBox
    dan font dijelaskan di App\Support\TrendChart. Tren dibaca dari garis, titik, dan label, bukan warna saja.
--}}
<figure {{ $attributes->class(['w-full max-w-[30rem]']) }}>
    <svg
        viewBox="0 0 {{ TrendChart::WIDTH }} {{ TrendChart::HEIGHT }}"
        class="block h-auto w-full"
        role="img"
        aria-labelledby="{{ $id }}-title {{ $id }}-desc"
        font-size="{{ TrendChart::FONT_SIZE }}"
        dusk="trend-chart"
    >
        <title id="{{ $id }}-title">{{ $title }}</title>
        <desc id="{{ $id }}-desc">{{ $summary }}</desc>

        {{-- Garis bantu dan label sumbu harga --}}
        @foreach ($chart->priceTicks() as $tick)
            <line
                x1="{{ $chart->plotLeft() }}" y1="{{ $tick['y'] }}"
                x2="{{ $chart->plotRight() }}" y2="{{ $tick['y'] }}"
                class="stroke-gray-200" stroke-width="1"
            />
            <text x="{{ $tick['labelX'] }}" y="{{ $tick['labelY'] }}" text-anchor="end" class="fill-gray-600" data-price-tick>{{ $tick['label'] }}</text>
        @endforeach

        {{-- Sumbu tanggal --}}
        <line
            x1="{{ $chart->plotLeft() }}" y1="{{ $chart->plotBottom() }}"
            x2="{{ $chart->plotRight() }}" y2="{{ $chart->plotBottom() }}"
            class="stroke-gray-400" stroke-width="1"
        />

        @foreach ($chart->dateLabels() as $label)
            <text x="{{ $label['x'] }}" y="{{ $label['y'] }}" text-anchor="{{ $label['anchor'] }}" class="fill-gray-700" data-date-label>{{ $label['label'] }}</text>
        @endforeach

        @unless ($chart->isSinglePoint())
            <polyline
                points="{{ $chart->polylinePoints() }}"
                fill="none"
                class="stroke-brand-600"
                stroke-width="2.5"
                stroke-linejoin="round"
                stroke-linecap="round"
            />
        @endunless

        {{-- Tiap titik punya <title> (tooltip bawaan browser) berisi tanggal dan harga. --}}
        @foreach ($chart->points() as $point)
            <circle
                cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ TrendChart::POINT_RADIUS }}"
                class="fill-white stroke-brand-700"
                stroke-width="2"
                data-point
            ><title>{{ __('reference-prices.chart.point', [
                'date' => IndonesianFormat::date($point['date']),
                'price' => IndonesianFormat::pricePerKg($point['price']),
            ]) }}</title></circle>
        @endforeach
    </svg>

    @if ($chart->isSinglePoint())
        <figcaption class="mt-2 text-sm text-gray-600" dusk="trend-chart-single">{{ __('reference-prices.chart.single_point') }}</figcaption>
    @endif
</figure>
