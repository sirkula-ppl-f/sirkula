<x-layouts.app :title="__('hub.show.title', [], 'id')">
    <div class="mx-auto max-w-5xl space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $hub->name }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ __('hub.show.description', [], 'id') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('admin.hubs.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                >
                    {{ __('hub.show.back', [], 'id') }}
                </a>

                <a
                    href="{{ route('admin.hubs.edit', $hub) }}"
                    class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                >
                    {{ __('hub.show.edit_button', [], 'id') }}
                </a>
            </div>
        </div>

        {{-- Notifikasi --}}
        <x-alert type="success" :message="session('success')" />
        <x-alert type="error" :message="session('error')" />

        @if (session('info'))
            <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">
                <p class="font-semibold">
                    {{ __('hub.validation.errors_title', [], 'id') }}
                </p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Informasi Hub --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h2 class="mb-5 text-lg font-semibold text-gray-900">
                {{ __('hub.show.information', [], 'id') }}
            </h2>

            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">
                        {{ __('hub.fields.name', [], 'id') }}
                    </dt>
                    <dd class="font-semibold text-gray-900">{{ $hub->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">
                        {{ __('hub.fields.type', [], 'id') }}
                    </dt>
                    <dd class="text-gray-900">
                        {{ __('hub.types.' . $hub->type, [], 'id') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">
                        {{ __('hub.fields.region', [], 'id') }}
                    </dt>
                    <dd class="text-gray-900">{{ $hub->region?->name ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">
                        {{ __('hub.fields.status', [], 'id') }}
                    </dt>
                    <dd>
                        @if ($hub->status === 'ACTIVE')
                            <x-status-badge :active="true" />
                        @else
                            <x-status-badge :active="false" />
                        @endif
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm text-gray-500">
                        {{ __('hub.fields.public_address', [], 'id') }}
                    </dt>
                    <dd class="whitespace-pre-line text-gray-900">{{ $hub->public_address }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm text-gray-500">
                        {{ __('hub.fields.description', [], 'id') }}
                    </dt>
                    <dd class="whitespace-pre-line text-gray-900">{{ $hub->description ?: '-' }}</dd>
                </div>
            </dl>
        </section>

        {{-- Pengelola Hub --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ __('hub.manager.title', [], 'id') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('hub.manager.description', [], 'id') }}
                </p>
            </div>

            {{-- Form Penugasan --}}
            @if ($hub->status === 'ACTIVE')
                <form
                    method="POST"
                    action="{{ route('admin.hubs.managers.store', $hub) }}"
                    class="mb-6 rounded-lg bg-gray-50 p-4"
                    dusk="hub-manager-assignment-form"
                >
                    @csrf

                    <label for="user_id" class="mb-2 block text-sm font-medium text-gray-700">
                        {{ __('hub.manager.assign_title', [], 'id') }}
                    </label>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <select
                            id="user_id"
                            name="user_id"
                            required
                            class="min-w-0 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600"
                            dusk="hub-manager-user-select"
                        >
                            <option value="">
                                {{ __('hub.manager.choose_user', [], 'id') }}
                            </option>

                            @foreach ($eligibleUsers as $user)
                                <option
                                    value="{{ $user->id }}"
                                    @selected((string) old('user_id') === (string) $user->id)
                                >
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>

                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                            dusk="hub-manager-assign-button"
                        >
                            {{ __('hub.manager.assign_button', [], 'id') }}
                        </button>
                    </div>

                    @error('user_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if ($eligibleUsers->isEmpty())
                        <p class="mt-2 text-sm text-gray-500">
                            {{ __('hub.manager.no_available_users', [], 'id') }}
                        </p>
                    @endif
                </form>
            @else
                <div class="mb-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                    {{ __('hub.manager.archived_notice', [], 'id') }}
                </div>
            @endif

            {{-- Daftar Pengelola --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">
                                {{ __('hub.manager.user', [], 'id') }}
                            </th>
                            <th scope="col" class="px-4 py-3">
                                {{ __('hub.manager.assigned_at', [], 'id') }}
                            </th>
                            <th scope="col" class="px-4 py-3">
                                {{ __('hub.manager.status', [], 'id') }}
                            </th>
                            <th scope="col" class="px-4 py-3">
                                {{ __('hub.manager.actions', [], 'id') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse ($hub->managers as $manager)
                            <tr dusk="hub-manager-row-{{ $manager->id }}">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">
                                        {{ $manager->user?->name ?? __('hub.manager.unavailable_user', [], 'id') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $manager->user?->email ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $manager->assigned_at?->format('d M Y') ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    @if ($manager->assignment_status === 'ACTIVE')
                                        <x-status-badge :active="true" />
                                    @else
                                        <x-status-badge :active="false" />
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if ($manager->assignment_status === 'ACTIVE')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.hubs.managers.deactivate', [$hub, $manager]) }}"
                                            data-confirm="{{ __('hub.manager.deactivate_confirm', ['name' => $manager->user?->name ?? __('hub.manager.unavailable_user', [], 'id')], 'id') }}"
                                            data-confirm-title="{{ __('hub.manager.deactivate_title', [], 'id') }}"
                                            data-confirm-label="{{ __('hub.manager.deactivate', [], 'id') }}"
                                            data-confirm-variant="danger"
                                            dusk="hub-manager-deactivate-form-{{ $manager->id }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="font-semibold text-red-600 hover:text-red-800"
                                                dusk="hub-manager-deactivate-button-{{ $manager->id }}"
                                            >
                                                {{ __('hub.manager.deactivate', [], 'id') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">
                                            {{ __('hub.manager.no_action', [], 'id') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    {{ __('hub.manager.empty', [], 'id') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <x-confirm-dialog />
</x-layouts.app>
