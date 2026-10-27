{{--
    Form tambah/ubah kategori.
    Variabel: $category (null saat tambah), $action, $method, $submitLabel, $listingTypes, $typesInUse.
--}}

@php
    $selectedTypes = old('listing_types', $category?->listingTypes()->map(fn ($type) => $type->value)->all() ?? []);
    $selectedTypes = is_array($selectedTypes) ? $selectedTypes : [];
    $typesError = $errors->first('listing_types') ?: $errors->first('listing_types.*');
    $typesDescribedBy = collect([
        'listing-types-hint',
        $typesInUse->isNotEmpty() ? 'listing-types-in-use' : null,
        $typesError ? 'listing-types-error' : null,
    ])->filter()->implode(' ');
@endphp

<form method="POST" action="{{ $action }}" class="max-w-2xl space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200" data-submit-once novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-form.input name="name" label="Nama" :value="$category?->name" maxlength="100" required autofocus dusk="name" />

    <x-form.textarea
        name="description"
        label="Deskripsi"
        hint="Opsional, maksimal 500 karakter."
        :value="$category?->description"
        maxlength="500"
        dusk="description"
    />

    <fieldset aria-describedby="{{ $typesDescribedBy }}">
        <legend class="text-sm font-medium text-gray-800">Tipe listing</legend>
        <p id="listing-types-hint" class="mt-1 text-sm text-gray-600">Pilih minimal satu tipe listing yang boleh memakai kategori ini.</p>

        <div class="mt-3 space-y-2">
            @foreach ($listingTypes as $type)
                @php($typeId = 'listing-type-'.strtolower($type->value))

                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="{{ $typeId }}"
                        name="listing_types[]"
                        value="{{ $type->value }}"
                        @checked(in_array($type->value, $selectedTypes, true))
                        @if ($typesError) aria-invalid="true" @endif
                        class="size-4 rounded border-gray-300 accent-brand-600"
                        dusk="{{ $typeId }}"
                    >
                    <label for="{{ $typeId }}" class="text-sm text-gray-800">{{ $type->label() }}</label>
                </div>
            @endforeach
        </div>

        @if ($typesInUse->isNotEmpty())
            <p id="listing-types-in-use" class="mt-2 text-sm text-gray-600">
                {{ __('master-data.categories.types_in_use_hint', ['types' => $typesInUse->map(fn ($type) => $type->label())->join(', ', ' dan ')]) }}
            </p>
        @endif

        @if ($typesError)
            <p id="listing-types-error" class="mt-2 text-sm text-red-700">{{ $typesError }}</p>
        @endif
    </fieldset>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.categories.index') }}"
            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
            dusk="cancel-link"
        >Batal</a>

        <x-button class="sm:w-auto" dusk="save-button">{{ $submitLabel }}</x-button>
    </div>
</form>
