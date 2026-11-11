<x-layouts.app title="Tambah Material">
    @include('admin.materials.partials.form', [
        'material' => null,
        'action' => route('admin.materials.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan material',
    ])
</x-layouts.app>
