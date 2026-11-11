{{--
    Form tambah/ubah material.
    Variabel: $material (null saat tambah), $action, $method, $submitLabel.
--}}

<form method="POST" action="{{ $action }}" class="max-w-2xl space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200" data-submit-once novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-form.input name="name" label="Nama" :value="$material?->name" maxlength="100" required autofocus dusk="name" />

    <x-form.textarea
        name="description"
        label="Deskripsi"
        hint="Opsional, maksimal 500 karakter."
        :value="$material?->description"
        maxlength="500"
        dusk="description"
    />

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.materials.index') }}"
            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
            dusk="cancel-link"
        >Batal</a>

        <x-button class="sm:w-auto" dusk="save-button">{{ $submitLabel }}</x-button>
    </div>
</form>
