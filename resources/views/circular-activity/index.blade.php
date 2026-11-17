@use('App\Enums\ListingType')
@use('App\Support\IndonesianFormat')

@php
    $hasFilters = $filters['period'] !== 'all' || $filters['role'] !== 'all';
    $hasMonthlyActivity = collect($monthlySeries)->sum('orders_count') > 0;
    $userId = (int) auth()->id();

    // Format berat Indonesia: 12,5 kg (tanpa nol berlebih).
    $formatKg = fn (float $kg) => rtrim(rtrim(number_format($kg, 2, ',', '.'), '0'), ',').' kg';

    $typeBadgeClasses = [
        ListingType::Preloved->value => 'bg-sky-50 text-sky-800 ring-sky-600/20',
        ListingType::Recyclable->value => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
        ListingType::Upcycle->value => 'bg-purple-50 text-purple-800 ring-purple-600/20',
    ];
@endphp

<x-layouts.app title="Aktivitas Sirkular">
    <div class="mb-6 max-w-3xl space-y-2">
        <h1 class="text-2xl font-semibold text-gray-900">Catatan Aktivitas Sirkular</h1>
        <p class="text-sm text-gray-700">
            Ringkasan barang dan material yang sudah kamu salurkan atau beli melalui transaksi lunas di Sirkula.
        </p>
    </div>

    {{-- Disclaimer estimasi (Batasan proposal Bab 1.3, FR-022) --}}
    <div
        class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
        role="note"
        dusk="circular-estimate-disclaimer"
    >
        <p class="font-semibold">Angka di halaman ini bersifat estimasi</p>
        <p class="mt-1">
            Dihitung hanya dari transaksi berstatus <strong>Lunas</strong> di sistem, bukan hasil pengukuran fisik
            atau bukti sertifikasi daur ulang. Item tanpa data berat tidak dihitung sebagai 0 kg.
        </p>
    </div>

    {{-- Filter --}}
    <form
        method="GET"
        action="{{ route('circular-activity.index') }}"
        aria-label="Filter aktivitas sirkular"
        class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end"
    >
        <x-form.select
            name="periode"
            label="Periode"
            :options="$periodOptions"
            :value="$filters['period']"
            dusk="circular-filter-period"
        />

        <x-form.select
            name="peran"
            label="Peran"
            :options="$roleOptions"
            :value="$filters['role']"
            dusk="circular-filter-role"
        />

        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="circular-filter-apply"
            >Terapkan</button>

            @if ($hasFilters)
                <a
                    href="{{ route('circular-activity.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="circular-filter-reset"
                >Reset</a>
            @endif
        </div>
    </form>

    {{-- Kartu ringkasan utama --}}
    <section aria-labelledby="ringkasan-heading" class="mb-8">
        <h2 id="ringkasan-heading" class="mb-3 text-lg font-semibold text-gray-900">Ringkasan</h2>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <dt class="text-sm text-gray-600">Pesanan Lunas</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900" dusk="circular-summary-orders">
                    {{ number_format($summary['total_paid_orders'], 0, ',', '.') }}
                </dd>
                <p class="mt-1 text-xs text-gray-500">Satu pesanan dihitung sekali, berapa pun isinya.</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <dt class="text-sm text-gray-600">Total Barang / Lot</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900" dusk="circular-summary-quantity">
                    {{ number_format($summary['total_items_quantity'], 0, ',', '.') }}
                </dd>
                <p class="mt-1 text-xs text-gray-500">Jumlah unit atau lot dari pesanan lunas.</p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <dt class="text-sm text-gray-600">Total Nilai Transaksi</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900" dusk="circular-summary-amount">
                    {{ IndonesianFormat::rupiah($summary['total_amount']) }}
                </dd>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <dt class="text-sm text-gray-600">Estimasi Berat Tercatat</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900" dusk="circular-summary-weight">
                    @if ($summary['known_weight_kg'] !== null)
                        {{ $formatKg($summary['known_weight_kg']) }}
                    @else
                        <span class="text-base font-medium text-gray-500">Belum terdata</span>
                    @endif
                </dd>
                @if ($summary['items_without_weight_count'] > 0)
                    <p class="mt-1 text-xs text-gray-500" dusk="circular-summary-weight-missing">
                        {{ $summary['items_without_weight_count'] }} item tanpa data berat (tidak dianggap 0 kg).
                    </p>
                @endif
            </div>
        </dl>
    </section>

    {{-- Rincian per tipe listing --}}
    <section aria-labelledby="per-tipe-heading" class="mb-8">
        <h2 id="per-tipe-heading" class="mb-3 text-lg font-semibold text-gray-900">Rincian per Tipe</h2>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($byType as $typeKey => $type)
                <article class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200" dusk="circular-type-{{ $typeKey }}">
                    <h3 class="font-semibold text-gray-900">{{ $type['label'] }}</h3>

                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Pesanan lunas</dt>
                            <dd class="font-medium text-gray-900">{{ $type['orders_count'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Jumlah unit/lot</dt>
                            <dd class="font-medium text-gray-900">{{ $type['quantity_total'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Nilai transaksi</dt>
                            <dd class="font-medium text-gray-900">{{ IndonesianFormat::rupiah($type['subtotal_amount']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Estimasi berat</dt>
                            <dd class="font-medium text-gray-900">
                                {{ $type['known_weight_kg'] !== null ? $formatKg($type['known_weight_kg']) : 'Tidak diketahui' }}
                            </dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Grafik tren aktivitas bulanan (6 bulan terakhir, tanpa JS eksternal) --}}
    <section aria-labelledby="grafik-bulanan-heading" class="mb-8">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="grafik-bulanan-heading" class="text-lg font-semibold text-gray-900">
                Aktivitas Bulanan (6 Bulan Terakhir)
            </h2>
            <p class="text-xs text-gray-500">Berdasarkan waktu lunas (WIB) dan filter peran yang dipilih.</p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-6" dusk="circular-monthly-chart">
            @if ($hasMonthlyActivity)
                <div class="overflow-x-auto" role="region" aria-label="Grafik batang pesanan lunas per bulan" tabindex="0">
                    <div class="grid min-w-[28rem] grid-cols-6 items-end gap-3 pt-2">
                        @foreach ($monthlySeries as $month)
                            <div
                                class="flex flex-col items-center gap-2 text-center"
                                dusk="circular-month-{{ $month['month_key'] }}"
                            >
                                <span class="text-xs font-semibold text-gray-800">
                                    {{ $month['orders_count'] }}
                                </span>

                                <div
                                    class="flex h-36 w-full max-w-12 items-end overflow-hidden rounded-md bg-gray-100 p-1"
                                    title="{{ $month['label'] }}: {{ $month['orders_count'] }} pesanan lunas"
                                >
                                    @if ($month['height_percent'] > 0)
                                        <div
                                            class="w-full rounded-sm bg-brand-600 transition-all"
                                            style="height: {{ $month['height_percent'] }}%"
                                            aria-hidden="true"
                                        ></div>
                                    @endif
                                </div>

                                <div class="space-y-0.5">
                                    <p class="text-xs font-medium text-gray-800">{{ $month['label'] }}</p>
                                    <p class="text-[11px] text-gray-500">
                                        @if ($month['known_weight_kg'] !== null)
                                            {{ $formatKg($month['known_weight_kg']) }}
                                        @elseif ($month['orders_count'] > 0)
                                            Berat blm terdata
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Ringkasan teks untuk pembaca layar --}}
                <ul class="sr-only">
                    @foreach ($monthlySeries as $month)
                        <li>
                            {{ $month['label'] }}: {{ $month['orders_count'] }} pesanan lunas,
                            estimasi berat {{ $month['known_weight_kg'] !== null ? $formatKg($month['known_weight_kg']) : 'belum terdata' }}.
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="py-6 text-center text-sm text-gray-600" dusk="circular-monthly-empty">
                    Belum ada transaksi lunas dalam 6 bulan terakhir untuk peran yang dipilih.
                </p>
            @endif
        </div>
    </section>

    {{-- Tabel riwayat item aktivitas sirkular --}}
    <section aria-labelledby="riwayat-heading">
        <h2 id="riwayat-heading" class="mb-3 text-lg font-semibold text-gray-900">Riwayat Item Lunas</h2>

        <div
            class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            role="region"
            aria-label="Tabel riwayat item aktivitas sirkular"
            tabindex="0"
        >
            <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="circular-history-table">
                <caption class="sr-only">
                    Daftar item dari pesanan berstatus lunas halaman {{ $items->currentPage() }} dari {{ max($items->lastPage(), 1) }}.
                </caption>

                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Tanggal Lunas</th>
                        <th scope="col" class="px-4 py-3">Barang / Lot</th>
                        <th scope="col" class="px-4 py-3">Tipe</th>
                        <th scope="col" class="px-4 py-3">Peran</th>
                        <th scope="col" class="px-4 py-3 text-right">Jumlah</th>
                        <th scope="col" class="px-4 py-3 text-right">Estimasi Berat</th>
                        <th scope="col" class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse ($items as $item)
                        @php
                            $isSeller = (int) $item->order->seller_id === $userId;
                            $weightKg = $item->estimatedTotalWeightKg();
                            $typeValue = $item->type_snapshot->value;
                        @endphp
                        <tr dusk="circular-history-row-{{ $item->id }}">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $item->order->paid_at ? IndonesianFormat::dateTimeWib($item->order->paid_at) : '—' }}
                            </td>
                            <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900">
                                <span>{{ $item->title_snapshot }}</span>
                                <span class="block text-xs font-normal text-gray-500">
                                    #{{ $item->order->order_number }}
                                </span>
                            </th>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1',
                                    $typeBadgeClasses[$typeValue] ?? 'bg-gray-100 text-gray-800 ring-gray-300',
                                ])>
                                    {{ $item->type_snapshot->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $isSeller ? 'Penjual' : 'Pembeli' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900">
                                {{ number_format($item->quantity, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900">
                                @if ($weightKg !== null)
                                    {{ $formatKg($weightKg) }}
                                @else
                                    <span class="text-gray-500">Tidak diketahui</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">
                                {{ IndonesianFormat::rupiah($item->subtotal) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-600" dusk="circular-history-empty">
                                Belum ada item transaksi lunas untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="mt-4" dusk="circular-history-pagination">
                {{ $items->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
