
<x-layouts.app title="Tambah Hub">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Tambah Hub</h1>
            <p class="text-sm text-gray-600">
                Tambahkan bank sampah, RW, atau komunitas.
            </p>
        </div>

        <form method="POST"
              action="{{ route('admin.hubs.store') }}"
              class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            @csrf

            <div>
                <label for="name" class="mb-1 block font-medium">Nama Hub</label>
                <input id="name" name="name" type="text"
                       value="{{ old('name') }}" required maxlength="150"
                       class="w-full rounded-lg border border-gray-300 p-3">
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="mb-1 block font-medium">Tipe Hub</label>
                <select id="type" name="type" required
                        class="w-full rounded-lg border border-gray-300 p-3">
                    <option value="">Pilih tipe</option>
                    @foreach (['BANK_SAMPAH' => 'Bank Sampah', 'RW' => 'RW', 'KOMUNITAS' => 'Komunitas'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('type') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="region_id" class="mb-1 block font-medium">Wilayah</label>
                <select id="region_id" name="region_id" required
                        class="w-full rounded-lg border border-gray-300 p-3">
                    <option value="">Pilih wilayah</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
                @error('region_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="public_address" class="mb-1 block font-medium">Alamat Publik</label>
                <textarea id="public_address" name="public_address" rows="3" required
                          class="w-full rounded-lg border border-gray-300 p-3">{{ old('public_address') }}</textarea>
                @error('public_address') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="mb-1 block font-medium">Deskripsi</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full rounded-lg border border-gray-300 p-3">{{ old('description') }}</textarea>
                @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-white">
                    Simpan Hub
                </button>
                <a href="{{ route('admin.hubs.index') }}"
                   class="rounded-lg bg-gray-100 px-5 py-2 text-gray-700">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-layouts.app>
