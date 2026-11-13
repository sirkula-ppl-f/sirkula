@csrf

@if (($method ?? 'POST') === 'PUT')
    @method('PUT')
@endif

<div>
    <label for="name" class="mb-1 block font-medium">
        {{ __('hub.fields.name', [], 'id') }}
    </label>

    <input
        id="name"
        name="name"
        type="text"
        value="{{ old('name', $hub?->name) }}"
        required
        maxlength="150"
        class="w-full rounded-lg border border-gray-300 p-3"
    >

    @error('name')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="type" class="mb-1 block font-medium">
        {{ __('hub.fields.type', [], 'id') }}
    </label>

    <select
        id="type"
        name="type"
        required
        class="w-full rounded-lg border border-gray-300 p-3"
    >
        <option value="">
            {{ __('hub.form.choose_type', [], 'id') }}
        </option>

        @foreach ([
            'BANK_SAMPAH' => __('hub.types.BANK_SAMPAH', [], 'id'),
            'RW' => __('hub.types.RW', [], 'id'),
            'KOMUNITAS' => __('hub.types.KOMUNITAS', [], 'id'),
        ] as $value => $label)
            <option
                value="{{ $value }}"
                @selected(old('type', $hub?->type) === $value)
            >
                {{ $label }}
            </option>
        @endforeach
    </select>

    @error('type')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="region_id" class="mb-1 block font-medium">
        {{ __('hub.fields.region', [], 'id') }}
    </label>

    <select
        id="region_id"
        name="region_id"
        required
        class="w-full rounded-lg border border-gray-300 p-3"
    >
        <option value="">
            {{ __('hub.form.choose_region', [], 'id') }}
        </option>

        @foreach ($regions as $region)
            <option
                value="{{ $region->id }}"
                @selected(old('region_id', $hub?->region_id) == $region->id)
            >
                {{ $region->name }}
            </option>
        @endforeach
    </select>

    @error('region_id')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="public_address" class="mb-1 block font-medium">
        {{ __('hub.fields.public_address', [], 'id') }}
    </label>

    <textarea
        id="public_address"
        name="public_address"
        rows="3"
        required
        maxlength="2000"
        class="w-full rounded-lg border border-gray-300 p-3"
    >{{ old('public_address', $hub?->public_address) }}</textarea>

    @error('public_address')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="description" class="mb-1 block font-medium">
        {{ __('hub.fields.description', [], 'id') }}
    </label>

    <textarea
        id="description"
        name="description"
        rows="4"
        maxlength="5000"
        class="w-full rounded-lg border border-gray-300 p-3"
    >{{ old('description', $hub?->description) }}</textarea>

    @error('description')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="flex gap-3">
    <button
        type="submit"
        class="rounded-lg bg-blue-600 px-5 py-2 text-white"
    >
        {{ $method === 'PUT'
            ? __('hub.form.update', [], 'id')
            : __('hub.form.save', [], 'id') }}
    </button>

    <a
        href="{{ route('admin.hubs.index') }}"
        class="rounded-lg bg-gray-100 px-5 py-2 text-gray-700"
    >
        {{ __('hub.form.cancel', [], 'id') }}
    </a>
</div>
