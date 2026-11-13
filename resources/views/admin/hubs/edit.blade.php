<x-layouts.app title="Edit Hub">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Edit Hub</h1>
            <p class="text-sm text-gray-600">
                Perbarui informasi Hub yang sudah terdaftar.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('admin.hubs.update', $hub) }}"
            class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
        >
            @include('admin.hubs._form', [
                'hub' => $hub,
                'regions' => $regions,
                'method' => 'PUT',
            ])
        </form>
    </div>
</x-layouts.app>