@php
    use App\Enums\UserRole;
    use App\Enums\UserStatus;

    $rawStatus = request()->query('status');
    $currentStatus = is_string($rawStatus) && UserStatus::tryFrom($rawStatus) !== null ? $rawStatus : null;

    $rawSearch = request()->query('search');
    $currentSearch = is_string($rawSearch) && trim($rawSearch) !== '' ? trim($rawSearch) : null;

    $chips = [
        ['label' => 'Semua', 'value' => null, 'dusk' => 'filter-all'],
        ['label' => 'Aktif', 'value' => UserStatus::Active->value, 'dusk' => 'filter-active'],
        ['label' => 'Ditangguhkan', 'value' => UserStatus::Suspended->value, 'dusk' => 'filter-suspended'],
    ];

    $hasFilters = $currentSearch !== null || $currentStatus !== null;
    $authUserId = auth()->id();

    $selectedUserId = session('selected_user_id') ?? request()->integer('selected');
    $selectedUser = $users->firstWhere('id', $selectedUserId) ?? $users->first();
@endphp

<x-layouts.app title="Kelola Status Akun">
    @if (session('success'))
        <x-alert type="success" :message="session('success')" class="mb-6" />
    @endif

    <x-alert type="error" :message="session('error')" class="mb-6" />

    <div class="mb-6">
        <p class="max-w-2xl text-sm text-gray-700">
            Tangguhkan atau aktifkan kembali akun pengguna dengan alasan yang tercatat di log audit.
            Penangguhan langsung mencabut seluruh sesi aktif pengguna.
        </p>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status akun">
            @foreach ($chips as $chip)
                @php
                    $isActiveChip = ($chip['value'] === null && $currentStatus === null)
                        || $currentStatus === $chip['value'];

                    $chipQuery = array_filter([
                        'status' => $chip['value'],
                        'search' => $currentSearch,
                    ], fn ($val) => $val !== null && $val !== '');
                @endphp

                <a
                    href="{{ route('admin.users.index', $chipQuery) }}"
                    dusk="{{ $chip['dusk'] }}"
                    @if ($isActiveChip) aria-current="true" @endif
                    @class([
                        'inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition',
                        'border-brand-600 bg-brand-600 text-white' => $isActiveChip,
                        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => ! $isActiveChip,
                    ])
                >
                    {{ $chip['label'] }}
                </a>
            @endforeach
        </div>

        <form
            method="GET"
            action="{{ route('admin.users.index') }}"
            role="search"
            aria-label="Cari pengguna"
            class="flex w-full flex-wrap items-center gap-2 sm:w-auto"
        >
            @if ($currentStatus !== null)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <label for="search-user-input" class="sr-only">Cari nama atau email</label>
            <input
                id="search-user-input"
                name="search"
                type="search"
                value="{{ $currentSearch }}"
                maxlength="100"
                placeholder="Cari nama atau email"
                dusk="search-input"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-brand-600 sm:w-64"
            >

            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="search-submit"
            >Cari</button>

            @if ($hasFilters)
                <a
                    href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="reset-filter"
                >Reset</a>
            @endif
        </form>
    </div>

    <div class="flex flex-col items-start gap-6 lg:flex-row">
        {{-- Kolom Kiri: Tabel Daftar Pengguna --}}
        <div class="w-full min-w-0 flex-1">
            {{--
                Di layar sempit tabel di-scroll di dalam wadah ini, halaman tidak ikut bergeser.
                Kelas relative membuat span sr-only (position:absolute) di tombol aksi ikut terpotong wadah.
            --}}
            <div
                class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                role="region"
                aria-label="Tabel daftar pengguna"
                tabindex="0"
                dusk="user-table-region"
            >
                <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="user-table">
                    <caption class="sr-only">
                        Daftar pengguna, halaman {{ $users->currentPage() }} dari {{ max($users->lastPage(), 1) }}
                    </caption>

                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nama</th>
                            <th scope="col" class="px-4 py-3">Peran</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse ($users as $item)
                            @php
                                $isRowActive = $item->status === UserStatus::Active;
                                $isSelected = $selectedUser && $selectedUser->id === $item->id;
                                $roleLabel = $item->role === UserRole::Admin ? 'Admin' : 'Warga';
                                $statusLabel = $isRowActive ? 'Aktif' : 'Ditangguhkan';
                                $isSelf = $item->id === $authUserId;
                                $isLastActiveAdmin = $item->isAdmin() && $isRowActive && $activeAdminCount <= 1;
                                $canModify = ! $isSelf && ! $isLastActiveAdmin;
                                $guardMessage = $isSelf
                                    ? 'Anda tidak dapat menangguhkan akun Anda sendiri.'
                                    : ($isLastActiveAdmin ? 'Admin aktif terakhir di sistem tidak dapat ditangguhkan.' : '');
                                $selectQuery = array_filter([
                                    'status' => $currentStatus,
                                    'search' => $currentSearch,
                                    'page' => $users->currentPage() > 1 ? $users->currentPage() : null,
                                    'selected' => $item->id,
                                ], fn ($val) => $val !== null && $val !== '');
                            @endphp
                            <tr
                                data-user-row="{{ $item->id }}"
                                dusk="user-row-{{ $item->id }}"
                                @class([
                                    'transition-colors hover:bg-gray-50',
                                    'bg-brand-50/40' => $isSelected,
                                ])
                            >
                                <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900">
                                    {{ $item->name }}
                                    <span class="block break-all text-xs font-normal text-gray-500">{{ $item->email }}</span>
                                </th>
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $roleLabel }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        @class([
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                            'bg-brand-50 text-brand-800 ring-brand-100' => $isRowActive,
                                            'bg-red-50 text-red-700 ring-red-200' => ! $isRowActive,
                                        ])
                                    >
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('admin.users.index', $selectQuery) }}"
                                        dusk="select-user-{{ $item->id }}"
                                        data-select-user
                                        data-user-id="{{ $item->id }}"
                                        data-user-name="{{ $item->name }}"
                                        data-user-email="{{ $item->email }}"
                                        data-user-role="{{ $roleLabel }}"
                                        data-user-status="{{ $item->status->value }}"
                                        data-user-status-label="{{ $statusLabel }}"
                                        data-suspend-url="{{ route('admin.users.suspend', $item) }}"
                                        data-activate-url="{{ route('admin.users.activate', $item) }}"
                                        data-can-modify="{{ $canModify ? 'true' : 'false' }}"
                                        data-guard-message="{{ $guardMessage }}"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    >
                                        Pilih<span class="sr-only"> {{ $item->name }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-600" dusk="user-empty">
                                    {{ $hasFilters ? 'Tidak ada pengguna yang cocok dengan pencarian atau filter.' : 'Belum ada pengguna.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>

        {{-- Kolom Kanan: Panel Detail & Aksi Akun Terpilih --}}
        @if ($selectedUser)
            @php
                $selActive = $selectedUser->status === UserStatus::Active;
                $selStatusLabel = $selActive ? 'Aktif' : 'Ditangguhkan';
                $selIsSelf = $selectedUser->id === $authUserId;
                $selIsLastAdmin = $selectedUser->isAdmin() && $selActive && $activeAdminCount <= 1;
                $selCanModify = ! $selIsSelf && ! $selIsLastAdmin;
                $selGuardMessage = $selIsSelf
                    ? 'Anda tidak dapat menangguhkan akun Anda sendiri.'
                    : ($selIsLastAdmin ? 'Admin aktif terakhir di sistem tidak dapat ditangguhkan.' : '');
                $selActionUrl = $selActive
                    ? route('admin.users.suspend', $selectedUser)
                    : route('admin.users.activate', $selectedUser);
                $hasReasonError = $errors->has('reason');
            @endphp

            <aside
                id="user-action-panel"
                aria-label="Detail dan aksi akun terpilih"
                class="w-full shrink-0 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:w-96"
            >
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2
                                id="panel-user-name"
                                dusk="panel-user-name"
                                class="text-base font-semibold text-gray-900"
                            >
                                {{ $selectedUser->name }}
                            </h2>
                            <p id="panel-user-email" class="break-all text-xs text-gray-500">
                                {{ $selectedUser->email }}
                            </p>
                        </div>

                        <span
                            id="panel-user-status-badge"
                            dusk="panel-user-status"
                            @class([
                                'inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                'bg-brand-50 text-brand-800 ring-brand-100' => $selActive,
                                'bg-red-50 text-red-700 ring-red-200' => ! $selActive,
                            ])
                        >
                            {{ $selStatusLabel }}
                        </span>
                    </div>

                    <p
                        id="panel-guard-notice"
                        @class([
                            'rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800',
                            'hidden' => $selCanModify,
                        ])
                    >
                        {{ $selGuardMessage }}
                    </p>

                    <form
                        id="user-status-form"
                        method="POST"
                        action="{{ $selActionUrl }}"
                        data-submit-once
                        data-current-status="{{ $selectedUser->status->value }}"
                        data-target-name="{{ $selectedUser->name }}"
                        class="space-y-4"
                        novalidate
                    >
                        @csrf

                        <div class="space-y-1.5">
                            <label
                                for="reason-textarea"
                                class="block text-sm font-medium text-gray-800"
                            >
                                Alasan
                            </label>
                            <textarea
                                id="reason-textarea"
                                name="reason"
                                rows="3"
                                required
                                minlength="5"
                                maxlength="500"
                                placeholder="Wajib diisi (minimal 5 karakter)"
                                dusk="reason-input"
                                @if ($hasReasonError) aria-invalid="true" aria-describedby="reason-error" @endif
                                @disabled(! $selCanModify)
                                @class([
                                    'block w-full rounded-md border bg-white px-3 py-2 text-base shadow-sm placeholder:text-gray-400 disabled:cursor-not-allowed disabled:bg-gray-100',
                                    'border-red-600' => $hasReasonError,
                                    'border-gray-300 focus:border-brand-600' => ! $hasReasonError,
                                ])
                            >{{ old('reason') }}</textarea>

                            <p
                                id="reason-error"
                                dusk="reason-error"
                                role="alert"
                                @class([
                                    'text-sm text-red-700',
                                    'hidden' => ! $hasReasonError,
                                ])
                            >{{ $errors->first('reason') }}</p>
                        </div>

                        <button
                            id="panel-submit-button"
                            type="submit"
                            dusk="panel-action-button"
                            @disabled(! $selCanModify)
                            @class([
                                'inline-flex w-full items-center justify-center rounded-md border px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60',
                                'border-red-300 bg-white text-red-700 hover:bg-red-50' => $selActive,
                                'border-brand-600 bg-brand-600 text-white hover:bg-brand-700' => ! $selActive,
                            ])
                        >
                            {{ $selActive ? 'Tangguhkan akun' : 'Aktifkan kembali' }}
                        </button>
                    </form>

                    <p class="text-xs leading-relaxed text-gray-500">
                        Penangguhan mencabut sesi aktif dan mencegah listing serta checkout baru. Riwayat pesanan tetap tersimpan. Admin aktif terakhir tidak dapat dinonaktifkan.
                    </p>
                </div>
            </aside>
        @endif
    </div>

    {{-- Dialog Konfirmasi Native (<dialog>) seragam dengan <x-confirm-dialog /> --}}
    <dialog
        id="confirm-status-dialog"
        dusk="confirm-status-dialog"
        aria-labelledby="dialog-title"
        aria-describedby="dialog-description"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl bg-white p-0 text-gray-900 shadow-xl backdrop:bg-gray-900/50"
    >
        <div class="p-6">
            <h2
                id="dialog-title"
                class="text-lg font-semibold text-gray-900"
            >
                Tangguhkan akun?
            </h2>

            <p
                id="dialog-description"
                class="mt-2 text-sm wrap-break-word text-gray-700"
            >
                Seluruh sesi aktif pengguna akan langsung dicabut di semua perangkat dan perubahan ini dicatat di log audit.
            </p>

            <div class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-700 ring-1 ring-gray-200">
                <span class="font-semibold text-gray-900">Alasan:</span>
                <span id="dialog-reason-preview" class="wrap-break-word"></span>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    id="dialog-cancel-button"
                    dusk="dialog-cancel-button"
                    autofocus
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                >
                    Batal
                </button>

                <button
                    type="button"
                    id="dialog-confirm-button"
                    dusk="dialog-confirm-button"
                    class="inline-flex items-center justify-center rounded-md border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50"
                >
                    Ya, tangguhkan
                </button>
            </div>
        </div>
    </dialog>
</x-layouts.app>
