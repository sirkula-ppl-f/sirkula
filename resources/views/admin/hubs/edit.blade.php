<x-layouts.app :title="__('hub.edit.title', [], 'id')">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">
                {{ __('hub.edit.title', [], 'id') }}
            </h1>
            <p class="text-sm text-gray-600">
                {{ __('hub.edit.description', [], 'id') }}
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
