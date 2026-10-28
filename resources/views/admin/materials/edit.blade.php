<x-layouts.app title="Ubah Material">
    <dl class="mb-6 flex max-w-2xl flex-wrap gap-x-8 gap-y-2 text-sm">
        <div>
            <dt class="font-medium text-gray-700">Slug</dt>
            <dd class="text-gray-900" dusk="material-slug">{{ $material->slug }} <span class="text-gray-600">(tidak berubah)</span></dd>
        </div>
        <div>
            <dt class="font-medium text-gray-700">Status</dt>
            <dd><x-status-badge :active="$material->is_active" /></dd>
        </div>
    </dl>

    @include('admin.materials.partials.form', [
        'action' => route('admin.materials.update', $material),
        'method' => 'PUT',
        'submitLabel' => 'Simpan perubahan',
    ])
</x-layouts.app>
