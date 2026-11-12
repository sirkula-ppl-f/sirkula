@use('App\Services\ReferencePriceService')
@use('App\Support\IndonesianFormat')

<x-layouts.app :title="__('reference-prices.admin.create_title')">
    <div class="max-w-2xl space-y-6">
        <div class="space-y-2 text-sm text-gray-700">
            <p>{{ __('reference-prices.admin.form.intro') }}</p>
            <p>{{ __('reference-prices.admin.append_only') }}</p>
        </div>

        {{-- Tampil saat form dikirim ulang karena validasi gagal: harga yang berlaku untuk pilihan sebelumnya. --}}
        @if ($selection)
            <section
                aria-labelledby="current-price-title"
                class="rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-800"
                dusk="current-price"
            >
                <h2 id="current-price-title" class="font-semibold">{{ __('reference-prices.admin.current.title') }}</h2>
                <p class="mt-1 wrap-break-word">
                    @if ($selection['price'])
                        {{ __('reference-prices.admin.current.found', [
                            'material' => $selection['material']->name,
                            'region' => $selection['region']->name,
                            'price' => IndonesianFormat::pricePerKg($selection['price']->price_per_kg),
                            'date' => IndonesianFormat::date($selection['price']->effective_date),
                            'source' => $selection['price']->source,
                        ]) }}
                    @else
                        {{ __('reference-prices.admin.current.none', [
                            'material' => $selection['material']->name,
                            'region' => $selection['region']->name,
                        ]) }}
                    @endif
                </p>
            </section>
        @endif

        {{--
            Konfirmasi lewat <x-confirm-dialog /> karena harga yang terbit tidak bisa diubah.
            Tanpa JavaScript, form tetap terkirim biasa.
        --}}
        <form
            method="POST"
            action="{{ route('admin.reference-prices.store') }}"
            class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
            data-submit-once
            data-confirm="{{ __('reference-prices.admin.confirm.message') }}"
            data-confirm-title="{{ __('reference-prices.admin.confirm.title') }}"
            data-confirm-label="{{ __('reference-prices.admin.confirm.label') }}"
            novalidate
            dusk="reference-price-form"
        >
            @csrf

            <x-form.select
                name="material_id"
                :label="__('reference-prices.admin.form.material')"
                :placeholder="__('reference-prices.admin.form.material_placeholder')"
                :options="$materials"
                required
                dusk="material_id"
            />

            <x-form.select
                name="region_id"
                :label="__('reference-prices.admin.form.region')"
                :placeholder="__('reference-prices.admin.form.region_placeholder')"
                :options="$provinces"
                required
                dusk="region_id"
            />

            <x-form.input
                name="price_per_kg"
                :label="__('reference-prices.admin.form.price')"
                :hint="__('reference-prices.admin.form.price_hint', ['max' => IndonesianFormat::rupiah(ReferencePriceService::MAX_PRICE)])"
                inputmode="numeric"
                autocomplete="off"
                required
                dusk="price_per_kg"
            />

            <x-form.input
                name="effective_date"
                type="date"
                :label="__('reference-prices.admin.form.effective_date')"
                :hint="__('reference-prices.admin.form.effective_date_hint', [
                    'min' => IndonesianFormat::date($minDate),
                    'max' => IndonesianFormat::date($maxDate),
                ])"
                :value="$today->toDateString()"
                :min="$minDate->toDateString()"
                :max="$maxDate->toDateString()"
                required
                dusk="effective_date"
            />

            <x-form.input
                name="source"
                :label="__('reference-prices.admin.form.source')"
                :hint="__('reference-prices.admin.form.source_hint')"
                maxlength="255"
                required
                dusk="source"
            />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a
                    href="{{ route('admin.reference-prices.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="cancel-link"
                >{{ __('reference-prices.admin.form.cancel') }}</a>

                <x-button class="sm:w-auto" dusk="publish-button">{{ __('reference-prices.admin.form.submit') }}</x-button>
            </div>
        </form>
    </div>

    <x-confirm-dialog />
</x-layouts.app>
