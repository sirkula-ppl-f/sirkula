@php
    $stats = rescue(fn () => [
        'categories_active' => \App\Models\Category::where('is_active', true)->count(),
        'materials_active' => \App\Models\Material::where('is_active', true)->count(),
        'hubs_active' => \App\Models\Hub::where('status', 'ACTIVE')->count(),
        'reference_prices_total' => \App\Models\ReferencePrice::count(),
        'guides_published' => \App\Models\Guide::where('status', 'published')->count(),
        'users_total' => \App\Models\User::count(),
        'users_suspended' => \App\Models\User::where('status', \App\Enums\UserStatus::Suspended)->count(),
    ], [
        'categories_active' => 0,
        'materials_active' => 0,
        'hubs_active' => 0,
        'reference_prices_total' => 0,
        'guides_published' => 0,
        'users_total' => 0,
        'users_suspended' => 0,
    ], false);
@endphp

<x-layouts.app title="Admin">
    {{-- Banner sapaan & pintasan aksi cepat --}}
    <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <div class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-600/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-600" aria-hidden="true"></span>
                    Pusat Kendali Administrasi
                </div>
                <p class="text-base text-gray-800">
                    Halo, <span class="font-semibold text-gray-900" dusk="admin-user-name">{{ auth()->user()->name }}</span>. Anda masuk sebagai admin.
                </p>
                <p class="mt-1 text-sm text-gray-600">
                    Pantau ringkasan operasional, gunakan pintasan cepat, atau berpindah antarmodul dari sidebar kiri.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.reference-prices.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-brand-800"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Terbitkan Harga
                </a>
                <a
                    href="{{ route('admin.guides.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-brand-700"
                >
                    <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Buat Panduan
                </a>
                <a
                    href="{{ route('admin.hubs.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-brand-700"
                >
                    <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Tambah Hub
                </a>
                <a
                    href="{{ route('admin.categories.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-brand-700"
                >
                    <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Tambah Kategori
                </a>
            </div>
        </div>
    </div>

    {{-- Baris ringkasan KPI operasional --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan indikator operasional">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <p class="text-xs font-medium text-gray-500">Data Master Aktif</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">
                {{ $stats['categories_active'] + $stats['materials_active'] }}
            </p>
            <p class="mt-1 text-xs text-gray-600">
                {{ $stats['categories_active'] }} kategori &bull; {{ $stats['materials_active'] }} jenis material
            </p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <p class="text-xs font-medium text-gray-500">Titik Hub Beroperasi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['hubs_active'] }}</p>
            <p class="mt-1 text-xs text-gray-600">Siap menerima pengumpulan warga</p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <p class="text-xs font-medium text-gray-500">Konten & Referensi</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['reference_prices_total'] }}</p>
            <p class="mt-1 text-xs text-gray-600">
                entri harga &bull; {{ $stats['guides_published'] }} panduan terbit
            </p>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            <p class="text-xs font-medium text-gray-500">Status Akun Pengguna</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['users_total'] }}</p>
            <p class="mt-1 text-xs">
                @if ($stats['users_suspended'] > 0)
                    <span class="font-medium text-amber-700">{{ $stats['users_suspended'] }} akun ditangguhkan</span>
                @else
                    <span class="text-gray-600">Seluruh akun berstatus aktif</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Kartu ikhtisar per modul --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a
            href="{{ route('admin.categories.index') }}"
            class="group flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
        >
            <div>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                        </svg>
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $stats['categories_active'] }} aktif
                    </span>
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-900 group-hover:text-brand-700">Kategori</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Kelola kategori produk nasional dan tipe listing yang berlaku.
                </p>
            </div>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                Buka Kategori <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
            </span>
        </a>

        <a
            href="{{ route('admin.materials.index') }}"
            class="group flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
        >
            <div>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $stats['materials_active'] }} aktif
                    </span>
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-900 group-hover:text-brand-700">Material</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Kelola jenis material daur ulang untuk listing dan harga acuan.
                </p>
            </div>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                Buka Material <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
            </span>
        </a>

        <a
            href="{{ route('admin.hubs.index') }}"
            dusk="manage-hubs-link"
            class="group flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
        >
            <div>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                        </svg>
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $stats['hubs_active'] }} beroperasi
                    </span>
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-900 group-hover:text-brand-700">Hub</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Kelola data Hub, wilayah, status, dan penugasan pengelolanya.
                </p>
            </div>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                Buka Hub <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
            </span>
        </a>

        <a
            href="{{ route('admin.reference-prices.index') }}"
            dusk="reference-prices-card"
            class="group flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
        >
            <div>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $stats['reference_prices_total'] }} entri
                    </span>
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-900 group-hover:text-brand-700">{{ __('reference-prices.admin.dashboard.title') }}</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    {{ __('reference-prices.admin.dashboard.description') }}
                </p>
            </div>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                {{ __('reference-prices.admin.dashboard.link') }} <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
            </span>
        </a>

        <a
            href="{{ route('admin.guides.index') }}"
            dusk="manage-guides-link"
            class="group flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
        >
            <div>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $stats['guides_published'] }} terbit
                    </span>
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-900 group-hover:text-brand-700">Panduan dan Edukasi</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Kelola tips, panduan, dan penempatan edukasi untuk pengguna aplikasi.
                </p>
            </div>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                Buka Panduan <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
            </span>
        </a>

        <a
            href="{{ route('admin.users.index') }}"
            dusk="manage-users-link"
            class="group flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-600"
        >
            <div>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                    <div class="flex flex-wrap items-center justify-end gap-1.5">
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                            {{ $stats['users_total'] }} akun
                        </span>
                        @if ($stats['users_suspended'] > 0)
                            <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-amber-600/20">
                                {{ $stats['users_suspended'] }} ditangguhkan
                            </span>
                        @endif
                    </div>
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-900 group-hover:text-brand-700">Kelola Akun</h2>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Tangguhkan atau aktifkan kembali akun pengguna beserta alasannya.
                </p>
            </div>
            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                Buka Kelola Akun <span aria-hidden="true" class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
            </span>
        </a>
    </div>
</x-layouts.app>