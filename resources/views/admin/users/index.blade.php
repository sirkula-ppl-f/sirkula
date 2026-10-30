@php
    use App\Enums\UserRole;
    use App\Enums\UserStatus;

    $currentStatus = request('status');
    $currentSearch = request('search');
    $authUserId = auth()->id();

    $selectedUserId = session('selected_user_id') ?? request()->integer('selected');
    $selectedUser = $users->firstWhere('id', $selectedUserId) ?? $users->first();

    $chips = [
        ['label' => 'Semua', 'value' => null, 'dusk' => 'filter-all'],
        ['label' => 'Aktif', 'value' => UserStatus::Active->value, 'dusk' => 'filter-active'],
        ['label' => 'Ditangguhkan', 'value' => UserStatus::Suspended->value, 'dusk' => 'filter-suspended'],
    ];
@endphp

<x-layouts.app>
    <div class="space-y-6">
        {{-- Tautan Kembali --}}
        <div>
            <a
                href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#178c5c] hover:underline"
                dusk="back-to-admin-dashboard"
            >
                &larr; Kembali ke Dashboard Admin
            </a>
        </div>

        {{-- Judul & Subjudul --}}
        <div class="space-y-1.5">
            <h1 class="text-2xl font-bold text-[#171c1a] sm:text-[30px] sm:leading-tight">
                Kelola Status Akun
            </h1>
            <p class="text-[15px] text-[#6b7570]">
                Tangguhkan atau aktifkan kembali akun pengguna dengan alasan
            </p>
        </div>

        {{-- Flash Messages (khusus halaman ini) --}}
        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        {{-- Baris Filter Chip & Pencarian --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5" role="group" aria-label="Filter status akun">
                @foreach ($chips as $chip)
                    @php
                        $isActiveChip = ($chip['value'] === null && ! filled($currentStatus))
                            || $currentStatus === $chip['value'];

                        $chipQuery = array_filter([
                            'status' => $chip['value'],
                            'search' => $currentSearch,
                        ], fn ($val) => filled($val));
                    @endphp

                    <a
                        href="{{ route('admin.users.index', $chipQuery) }}"
                        dusk="{{ $chip['dusk'] }}"
                        @if ($isActiveChip) aria-current="true" @endif
                        @class([
                            'inline-flex items-center rounded-full border px-4 py-2 text-[13px] font-semibold transition',
                            'border-[#178c5c] bg-[#178c5c] text-white' => $isActiveChip,
                            'border-[#e3e8e5] bg-white text-[#171c1a] hover:border-[#178c5c]/50 hover:bg-[#f7faf7]' => ! $isActiveChip,
                        ])
                    >
                        {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>

            <form
                method="GET"
                action="{{ route('admin.users.index') }}"
                class="flex w-full items-center gap-2 sm:w-auto"
                role="search"
            >
                @if (filled($currentStatus))
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif

                <label for="search-user-input" class="sr-only">Cari nama atau email</label>
                <div class="relative flex-1 sm:w-64">
                    <input
                        id="search-user-input"
                        type="search"
                        name="search"
                        value="{{ $currentSearch }}"
                        placeholder="Cari nama atau email"
                        dusk="search-input"
                        class="w-full rounded-full border border-[#e3e8e5] bg-white px-4 py-2 text-[13px] font-medium text-[#171c1a] placeholder:font-semibold placeholder:text-[#171c1a]/70 focus:border-[#178c5c]"
                    >
                </div>

                <button
                    type="submit"
                    dusk="search-submit"
                    class="rounded-full border border-[#e3e8e5] bg-white px-4 py-2 text-[13px] font-semibold text-[#171c1a] hover:bg-gray-50"
                >
                    Cari
                </button>

                @if (filled($currentSearch) || filled($currentStatus))
                    <a
                        href="{{ route('admin.users.index') }}"
                        dusk="reset-filter"
                        class="rounded-full px-3 py-2 text-[13px] font-medium text-[#6b7570] hover:text-[#171c1a] hover:underline"
                    >
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Konten Utama: Tabel (kiri) + Panel Detail/Aksi (kanan) --}}
        <div class="flex flex-col items-start gap-6 lg:flex-row">
            {{-- Tabel Daftar Pengguna --}}
            <div class="w-full min-w-0 flex-1 overflow-hidden rounded-[12px] border border-[#e3e8e5] bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-[13px]">
                        <thead>
                            <tr class="border-b border-[#e3e8e5] bg-[#f2f7f5] font-semibold text-[#6b7570]">
                                <th scope="col" class="px-4 py-3">Nama</th>
                                <th scope="col" class="px-4 py-3">Email</th>
                                <th scope="col" class="px-4 py-3">Peran</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e3e8e5]">
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
                                @endphp
                                <tr
                                    data-user-row="{{ $item->id }}"
                                    @class([
                                        'transition-colors hover:bg-[#f7faf7]',
                                        'bg-[#f7faf7]' => $isSelected,
                                    ])
                                >
                                    <td class="px-4 py-3 font-normal text-[#171c1a]">
                                        <div class="font-medium sm:font-normal">{{ $item->name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-[#171c1a]">
                                        <span class="break-all">{{ $item->email }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-[#171c1a]">
                                        {{ $roleLabel }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            @class([
                                                'inline-flex items-center rounded-[6px] px-2.5 py-1 text-[12px] font-semibold leading-none',
                                                'bg-[#e0f5e8] text-[#127345]' => $isRowActive,
                                                'bg-[#ffe5e5] text-[#b22626]' => ! $isRowActive,
                                            ])
                                        >
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
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
                                            class="font-semibold text-[#178c5c] hover:underline"
                                        >
                                            Pilih
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-[#6b7570]">
                                        Tidak ada pengguna yang cocok dengan pencarian atau filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-[#e3e8e5] px-4 py-3">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

            {{-- Panel Samping Detail & Aksi (mengikuti mockup Figma 360px) --}}
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
                @endphp

                <aside
                    id="user-action-panel"
                    aria-label="Detail dan aksi akun terpilih"
                    class="w-full shrink-0 rounded-[12px] border border-[#e3e8e5] bg-white px-5 py-[18px] lg:w-[360px]"
                >
                    <div class="flex flex-col items-start gap-3.5">
                        <div class="space-y-0.5">
                            <h2
                                id="panel-user-name"
                                dusk="panel-user-name"
                                class="text-[16px] font-semibold text-[#171c1a]"
                            >
                                {{ $selectedUser->name }}
                            </h2>
                            <p id="panel-user-email" class="text-[12px] text-[#6b7570]">
                                {{ $selectedUser->email }}
                            </p>
                        </div>

                        <span
                            id="panel-user-status-badge"
                            dusk="panel-user-status"
                            @class([
                                'inline-flex items-center rounded-[6px] px-2.5 py-1 text-[12px] font-semibold leading-none',
                                'bg-[#e0f5e8] text-[#127345]' => $selActive,
                                'bg-[#ffe5e5] text-[#b22626]' => ! $selActive,
                            ])
                        >
                            {{ $selStatusLabel }}
                        </span>

                        <p
                            id="panel-guard-notice"
                            @class([
                                'w-full rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[12px] text-amber-800',
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
                            class="w-full space-y-3.5"
                            novalidate
                        >
                            @csrf

                            <div class="flex flex-col items-start gap-1.5">
                                <label
                                    for="reason-textarea"
                                    class="text-[13px] font-semibold text-[#171c1a]"
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
                                    placeholder="Wajib diisi"
                                    dusk="reason-input"
                                    @disabled(! $selCanModify)
                                    class="w-full rounded-[8px] border border-[#e3e8e5] bg-white px-3.5 py-3 text-[14px] text-[#171c1a] placeholder:text-[#6b7570] focus:border-[#178c5c] disabled:cursor-not-allowed disabled:bg-gray-100"
                                >{{ old('reason') }}</textarea>
                                <p
                                    id="reason-client-error"
                                    class="hidden text-[12px] text-[#b22626]"
                                    role="alert"
                                ></p>
                            </div>

                            <button
                                id="panel-submit-button"
                                type="submit"
                                dusk="panel-action-button"
                                @disabled(! $selCanModify)
                                @class([
                                    'flex w-full items-center justify-center rounded-[8px] px-5 py-3 text-[14px] font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50',
                                    'bg-[#c33] hover:bg-[#b22626]' => $selActive,
                                    'bg-[#178c5c] hover:bg-[#127345]' => ! $selActive,
                                ])
                            >
                                {{ $selActive ? 'Tangguhkan akun' : 'Aktifkan kembali' }}
                            </button>
                        </form>

                        <p class="text-[12px] leading-relaxed text-[#6b7570]">
                            Penangguhan mencabut sesi aktif dan mencegah listing serta checkout baru. Riwayat pesanan tetap tersimpan. Admin aktif terakhir tidak dapat dinonaktifkan.
                        </p>
                    </div>
                </aside>
            @endif
        </div>
    </div>

    {{-- Dialog Konfirmasi Native (<dialog>) mengikuti desain Dialog Konfirmasi di Figma (node 10:170) --}}
    <dialog
        id="confirm-status-dialog"
        dusk="confirm-status-dialog"
        aria-labelledby="dialog-title"
        aria-describedby="dialog-description"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-[12px] border border-[#c33] bg-white p-0 shadow-xl backdrop:bg-black/40"
    >
        <div class="flex flex-col items-start gap-2.5 px-5 py-[18px]">
            <h3
                id="dialog-title"
                class="text-[14px] font-bold text-[#c33]"
            >
                Tangguhkan akun ini?
            </h3>

            <p
                id="dialog-description"
                class="text-[12px] leading-relaxed text-[#6b7570]"
            >
                Seluruh sesi aktif pengguna akan langsung dicabut di semua perangkat dan perubahan ini dicatat di log audit.
            </p>

            <div class="w-full rounded-[8px] bg-[#f7faf7] px-3 py-2 text-[12px] text-[#171c1a]">
                <span class="font-semibold">Alasan:</span>
                <span id="dialog-reason-preview" class="break-words text-[#6b7570]"></span>
            </div>

            <div class="mt-1 flex w-full items-center gap-2">
                <button
                    type="button"
                    id="dialog-cancel-button"
                    dusk="dialog-cancel-button"
                    class="flex flex-1 items-center justify-center rounded-[8px] border border-[#e3e8e5] bg-white px-5 py-3 text-[14px] font-semibold text-[#171c1a] hover:bg-gray-50"
                >
                    Kembali
                </button>

                <button
                    type="button"
                    id="dialog-confirm-button"
                    dusk="dialog-confirm-button"
                    class="flex flex-1 items-center justify-center rounded-[8px] bg-[#c33] px-5 py-3 text-[14px] font-semibold text-white hover:bg-[#b22626]"
                >
                    Ya, tangguhkan
                </button>
            </div>
        </div>
    </dialog>
</x-layouts.app>
