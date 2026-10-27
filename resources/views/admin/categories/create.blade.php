<x-layouts.app title="Tambah Kategori">
    @include('admin.categories.partials.form', [
        'category' => null,
        'action' => route('admin.categories.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan kategori',
        'typesInUse' => collect(),
    ])
</x-layouts.app>
