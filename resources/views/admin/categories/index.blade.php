@php
    $statusOptions = ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    $typeOptions = collect($listingTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all();
    $hasFilters = $filters['search'] !== null || $filters['status'] !== null || $filters['type'] !== null;
@endphp

<x-layouts.app title="Kategori">
    <x-alert type="error" :message="session('error')" class="mb-6" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="max-w-2xl text-sm text-gray-700">
            Kategori produk berlaku nasional dan menentukan tipe listing yang dapat memakainya.
            Kategori yang sudah dipakai listing hanya dapat dinonaktifkan.
        </p>

        <a
            href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
            dusk="category-create-link"
        >Tambah kategori</a>
    </div>

    <form
        method="GET"
        action="{{ route('admin.categories.index') }}"
        role="search"
        aria-label="Cari dan filter kategori"
        class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_auto] lg:items-end"
    >
        <x-form.input name="cari" label="Cari nama" type="search" :value="$filters['search']" maxlength="100" dusk="category-search" />

        <x-form.select name="status" label="Status" placeholder="Semua status" :options="$statusOptions" :value="$filters['status']" dusk="category-status-filter" />

        <x-form.select name="tipe" label="Tipe listing" placeholder="Semua tipe" :options="$typeOptions" :value="$filters['type']?->value" dusk="category-type-filter" />

        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="category-filter-button"
            >Terapkan</button>

            @if ($hasFilters)
                <a
                    href="{{ route('admin.categories.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="category-filter-reset"
                >Reset</a>
            @endif
        </div>
    </form>

    {{-- Di layar sempit tabel di-scroll di dalam wadah ini, halaman tidak ikut bergeser. --}}
    <div
        class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        role="region"
        aria-label="{{ __('master-data.categories.table_label') }}"
        tabindex="0"
        dusk="category-table-region"
    >
        <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="category-table">
            <caption class="sr-only">
                {{ __('master-data.categories.table_caption', ['page' => $categories->currentPage(), 'last' => max($categories->lastPage(), 1)]) }}
            </caption>

            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Nama</th>
                    <th scope="col" class="px-4 py-3">Tipe listing</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Dipakai</th>
                    <th scope="col" class="px-4 py-3">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse ($categories as $category)
                    <tr dusk="category-row-{{ $category->slug }}">
                        <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900">
                            {{ $category->name }}
                            <span class="block text-xs font-normal text-gray-500">{{ $category->slug }}</span>
                        </th>
                        <td class="px-4 py-3">
                            <ul class="flex flex-wrap gap-1">
                                @foreach ($category->listingTypes() as $type)
                                    <li class="whitespace-nowrap rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-800">{{ $type->label() }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :active="$category->is_active" />
                        </td>
                        <td class="px-4 py-3 text-gray-800">{{ $category->isInUse() ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3">
                            <x-master-data.row-actions
                                :name="$category->name"
                                :dusk-key="$category->slug"
                                :active="$category->is_active"
                                :in-use="$category->isInUse()"
                                :edit-url="route('admin.categories.edit', $category)"
                                :deactivate-url="route('admin.categories.deactivate', $category)"
                                :activate-url="route('admin.categories.activate', $category)"
                                :delete-url="route('admin.categories.destroy', $category)"
                                entity="categories"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-600" dusk="category-empty">
                            {{ $hasFilters ? __('master-data.categories.empty_filtered') : __('master-data.categories.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <x-confirm-dialog />
</x-layouts.app>
