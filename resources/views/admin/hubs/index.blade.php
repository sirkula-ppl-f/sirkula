<x-layouts.app :title="__('hub.index.title', [], 'id')">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ __('hub.index.title', [], 'id') }}
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('hub.index.description', [], 'id') }}
                </p>
            </div>

            <a
                href="{{ route('admin.hubs.create') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
                {{ __('hub.index.add_button', [], 'id') }}
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <form
                method="GET"
                action="{{ route('admin.hubs.index') }}"
                class="mb-6 flex flex-wrap gap-3"
            >
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('hub.index.search_placeholder', [], 'id') }}"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2"
                >

                <select
                    name="status"
                    class="rounded-lg border border-gray-300 px-4 py-2"
                >
                    <option value="">
                        {{ __('hub.index.all_statuses', [], 'id') }}
                    </option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>
                        {{ __('hub.status.active', [], 'id') }}
                    </option>
                    <option value="ARCHIVED" @selected(request('status') === 'ARCHIVED')>
                        {{ __('hub.status.archived', [], 'id') }}
                    </option>
                </select>

                <button
                    type="submit"
                    class="rounded-lg bg-gray-800 px-4 py-2 text-white"
                >
                    {{ __('hub.index.search_button', [], 'id') }}
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-gray-50 text-gray-700">
                        <tr>
                            <th class="p-3">{{ __('hub.fields.name', [], 'id') }}</th>
                            <th class="p-3">{{ __('hub.fields.type', [], 'id') }}</th>
                            <th class="p-3">{{ __('hub.fields.region', [], 'id') }}</th>
                            <th class="p-3">{{ __('hub.fields.status', [], 'id') }}</th>
                            <th class="p-3">{{ __('hub.index.actions', [], 'id') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse ($hubs as $hub)
                            <tr>
                                <td class="p-3 font-medium">
                                    {{ $hub->name }}
                                </td>
                                <td class="p-3">
                                    {{ __('hub.types.' . $hub->type, [], 'id') }}
                                </td>
                                <td class="p-3">
                                    {{ $hub->region?->name ?? '-' }}
                                </td>
                                <td class="p-3">
                                    {{ __('hub.status.' . strtolower($hub->status), [], 'id') }}
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-3">
                                        <a
                                            href="{{ route('admin.hubs.show', $hub) }}"
                                            class="text-blue-600 hover:underline"
                                        >
                                            {{ __('hub.index.detail_button', [], 'id') }}
                                        </a>

                                        <a
                                            href="{{ route('admin.hubs.edit', $hub) }}"
                                            class="text-amber-600 hover:underline"
                                        >
                                            {{ __('hub.index.edit_button', [], 'id') }}
                                        </a>

                                        @if ($hub->status === 'ACTIVE')
                                            <form
                                                method="POST"
                                                action="{{ route('admin.hubs.archive', $hub) }}"
                                                data-confirm="{{ __('hub.archive.confirm_message', ['name' => $hub->name], 'id') }}"
                                                data-confirm-title="{{ __('hub.archive.confirm_title', [], 'id') }}"
                                                data-confirm-label="{{ __('hub.archive.confirm_button', [], 'id') }}"
                                                data-confirm-variant="danger"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="text-red-600 hover:underline"
                                                >
                                                    {{ __('hub.index.archive_button', [], 'id') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">
                                    {{ __('hub.index.empty', [], 'id') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $hubs->links() }}
            </div>
        </div>
    </div>

    <x-confirm-dialog />
</x-layouts.app>
