@php
    $statusOptions = ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    $hasFilters = $filters['search'] !== null || $filters['status'] !== null;
@endphp

<x-layouts.app title="Material">
    <x-alert type="error" :message="session('error')" class="mb-6" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="max-w-2xl text-sm text-gray-700">
            Jenis material berlaku nasional dan dipakai oleh listing serta harga acuan.
            Material yang sudah dipakai hanya dapat dinonaktifkan.
        </p>

        <a
            href="{{ route('admin.materials.create') }}"
            class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
            dusk="material-create-link"
        >Tambah material</a>
    </div>

    <form
        method="GET"
        action="{{ route('admin.materials.index') }}"
        role="search"
        aria-label="Cari dan filter material"
        class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_auto] lg:items-end"
    >
        <x-form.input name="cari" label="Cari nama" type="search" :value="$filters['search']" maxlength="100" dusk="material-search" />

        <x-form.select name="status" label="Status" placeholder="Semua status" :options="$statusOptions" :value="$filters['status']" dusk="material-status-filter" />

        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="material-filter-button"
            >Terapkan</button>

            @if ($hasFilters)
                <a
                    href="{{ route('admin.materials.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="material-filter-reset"
                >Reset</a>
            @endif
        </div>
    </form>

    {{--
        Di layar sempit tabel di-scroll di dalam wadah ini, halaman tidak ikut bergeser.
        Kelas relative membuat span sr-only (position:absolute) di tombol aksi ikut terpotong wadah.
    --}}
    <div
        class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        role="region"
        aria-label="{{ __('master-data.materials.table_label') }}"
        tabindex="0"
        dusk="material-table-region"
    >
        <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="material-table">
            <caption class="sr-only">
                {{ __('master-data.materials.table_caption', ['page' => $materials->currentPage(), 'last' => max($materials->lastPage(), 1)]) }}
            </caption>

            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Nama</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Dipakai</th>
                    <th scope="col" class="px-4 py-3">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse ($materials as $material)
                    <tr dusk="material-row-{{ $material->slug }}">
                        <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900">
                            {{ $material->name }}
                            <span class="block text-xs font-normal text-gray-500">{{ $material->slug }}</span>
                        </th>
                        <td class="px-4 py-3">
                            <x-status-badge :active="$material->is_active" />
                        </td>
                        <td class="px-4 py-3 text-gray-800">{{ $material->isInUse() ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3">
                            <x-master-data.row-actions
                                :name="$material->name"
                                :dusk-key="$material->slug"
                                :active="$material->is_active"
                                :in-use="$material->isInUse()"
                                :edit-url="route('admin.materials.edit', $material)"
                                :deactivate-url="route('admin.materials.deactivate', $material)"
                                :activate-url="route('admin.materials.activate', $material)"
                                :delete-url="route('admin.materials.destroy', $material)"
                                entity="materials"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-600" dusk="material-empty">
                            {{ $hasFilters ? __('master-data.materials.empty_filtered') : __('master-data.materials.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $materials->links() }}
    </div>

    <x-confirm-dialog />
</x-layouts.app>
