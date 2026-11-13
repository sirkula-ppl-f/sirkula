<x-layouts.app title="Tambah Hub">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Tambah Hub</h1>
            <p class="text-sm text-gray-600">
                Tambahkan bank sampah, RW, atau komunitas.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('admin.hubs.store') }}"
            class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
        >
            @include('admin.hubs._form', [
                'hub' => null,
                'regions' => $regions,
                'method' => 'POST',
            ])
        </form>
    </div>
</x-layouts.app>