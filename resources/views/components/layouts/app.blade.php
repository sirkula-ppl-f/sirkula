@props(['title' => null])

@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
    $isAdminPanel = $isAdmin && request()->routeIs('admin.*');

    // Tautan umum untuk tampilan warga. Menu transaksi pribadi warga (mis. Keranjang) disembunyikan bagi Admin.
    // Key opsional: 'active' (pola routeIs untuk status aktif, bawaan 'route'), 'dusk', dan 'icon'.
    $links = [];
    $adminSections = [];

    if ($user) {
        $links[] = ['label' => 'Beranda', 'route' => 'dashboard'];
        $links[] = ['label' => 'Katalog', 'route' => 'catalog.index', 'active' => 'catalog.*', 'dusk' => 'nav-catalog'];

        if (! $isAdmin) {
            $links[] = ['label' => 'Keranjang', 'route' => 'cart.index', 'active' => 'cart.*', 'dusk' => 'nav-cart'];
        }

        $links[] = ['label' => 'Profil', 'route' => 'profil.edit', 'active' => 'profil.*', 'dusk' => 'nav-profil'];
        $links[] = ['label' => __('reference-prices.nav.public'), 'route' => 'reference-prices.index', 'active' => 'reference-prices.*', 'dusk' => 'nav-reference-prices'];

        if ($isAdmin) {
            $adminSections = [
                [
                    'heading' => 'Utama',
                    'items' => [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
                    ],
                ],
                [
                    'heading' => 'Data Master',
                    'items' => [
                        ['label' => __('master-data.categories.nav'), 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'dusk' => 'nav-categories', 'icon' => 'tag'],
                        ['label' => __('master-data.materials.nav'), 'route' => 'admin.materials.index', 'active' => 'admin.materials.*', 'dusk' => 'nav-materials', 'icon' => 'cube'],
                        ['label' => 'Hub', 'route' => 'admin.hubs.index', 'active' => 'admin.hubs.*', 'dusk' => 'nav-hubs', 'icon' => 'building'],
                    ],
                ],
                [
                    'heading' => 'Konten & Referensi',
                    'items' => [
                        ['label' => __('reference-prices.nav.admin'), 'route' => 'admin.reference-prices.index', 'active' => 'admin.reference-prices.*', 'dusk' => 'nav-admin-reference-prices', 'icon' => 'chart'],
                        ['label' => 'Panduan & Edukasi', 'route' => 'admin.guides.index', 'active' => 'admin.guides.*', 'dusk' => 'nav-guides', 'icon' => 'book'],
                    ],
                ],
                [
                    'heading' => 'Pengguna',
                    'items' => [
                        ['label' => 'Kelola Akun', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'dusk' => 'nav-users', 'icon' => 'users'],
                    ],
                ],
            ];
        }
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:shadow">
            Lewati ke konten utama
        </a>

        @if ($isAdminPanel)
            {{-- Layout Sidebar Penuh untuk Admin saat berada di halaman /admin* --}}
            <header
                dusk="admin-sidebar"
                class="border-b border-gray-200 bg-white md:fixed md:inset-y-0 md:left-0 md:z-40 md:flex md:w-64 md:flex-col md:border-b-0 md:border-r md:border-gray-200/90 md:bg-white"
            >
                <nav
                    aria-label="Navigasi utama"
                    class="flex flex-wrap items-center justify-between px-4 py-3 md:h-full md:flex-col md:flex-nowrap md:items-stretch md:justify-start md:overflow-y-auto md:px-4 md:py-5"
                >
                    {{-- Judul / Brand Sirkula di puncak Sidebar --}}
                    <div class="flex items-center justify-between md:px-1">
                        <a
                            href="{{ url('/') }}"
                            dusk="nav-brand"
                            class="inline-flex items-center gap-2.5 text-xl font-bold tracking-tight text-gray-900"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white shadow-2xs">
                                S
                            </span>
                            <span>{{ config('app.name') }}</span>
                        </a>
                    </div>

                    {{-- Tombol buka/tutup menu seluler (muncul saat JS aktif di layar < md) --}}
                    <button
                        type="button"
                        hidden
                        data-nav-toggle
                        aria-controls="nav-menu-panel"
                        aria-expanded="false"
                        dusk="nav-toggle"
                        class="ms-auto inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50 md:hidden"
                    >
                        <span class="sr-only" data-nav-toggle-label>Buka menu navigasi</span>
                        <svg data-nav-icon-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg data-nav-icon-close class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div
                        id="nav-menu-panel"
                        dusk="nav-menu-panel"
                        class="w-full max-md:mt-3 max-md:border-t max-md:border-gray-100 max-md:pt-3 md:mt-4 md:flex md:flex-1 md:flex-col"
                    >
                        {{-- Kartu Workspace / Konteks Role dengan pintasan Beralih ke tampilan warga --}}
                        <div class="mb-5 rounded-2xl border border-gray-200/90 bg-gray-50/70 p-1.5 shadow-2xs">
                            <div class="flex items-center justify-between px-2.5 py-2">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-900 text-xs font-bold text-white">
                                        A
                                    </span>
                                    <span class="truncate text-sm font-semibold text-gray-900">Panel Admin</span>
                                </div>
                                <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-[10px] font-semibold text-brand-700 ring-1 ring-brand-600/20">
                                    Aktif
                                </span>
                            </div>

                            <div class="mx-2 my-1 border-t border-gray-200/80"></div>

                            <a
                                href="{{ route('dashboard') }}"
                                dusk="switch-to-warga"
                                class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                    <span class="truncate">Beralih ke tampilan warga</span>
                                </span>
                                <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>

                        {{-- Grup Menu Pengelolaan Admin --}}
                        <div class="space-y-5">
                            @foreach ($adminSections as $section)
                                <div>
                                    <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                        {{ $section['heading'] }}
                                    </p>
                                    <ul class="space-y-0.5 text-sm font-medium">
                                        @foreach ($section['items'] as $link)
                                            @php($isCurrent = request()->routeIs($link['active'] ?? $link['route']))
                                            <li>
                                                <a
                                                    href="{{ route($link['route']) }}"
                                                    @class([
                                                        'flex items-center gap-3 rounded-xl px-3 py-2 transition',
                                                        'bg-gray-100 font-semibold text-gray-900' => $isCurrent,
                                                        'text-gray-600 hover:bg-gray-100/70 hover:text-gray-900' => ! $isCurrent,
                                                    ])
                                                    @if ($isCurrent) aria-current="page" @endif
                                                    @isset($link['dusk']) dusk="{{ $link['dusk'] }}" @endisset
                                                >
                                                    @switch($link['icon'] ?? null)
                                                        @case('home')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                                            </svg>
                                                            @break
                                                        @case('tag')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                                            </svg>
                                                            @break
                                                        @case('cube')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                                            </svg>
                                                            @break
                                                        @case('building')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                                            </svg>
                                                            @break
                                                        @case('chart')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                                            </svg>
                                                            @break
                                                        @case('book')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                                            </svg>
                                                            @break
                                                        @case('users')
                                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                                            </svg>
                                                            @break
                                                    @endswitch
                                                    <span class="truncate">{{ $link['label'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>

                        {{-- Bagian bawah Sidebar: Profil Akun & Tombol Keluar --}}
                        <div class="mt-6 flex items-center justify-between gap-2 border-t border-gray-200/80 pt-4 text-sm md:mt-auto">
                            <div class="flex min-w-0 items-center gap-2.5 px-1">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                </span>
                                <span class="max-w-28 truncate font-medium text-gray-700" dusk="nav-user-name">{{ $user->name }}</span>
                            </div>

                            {{-- Logout hanya lewat POST + CSRF --}}
                            <form method="POST" action="{{ route('logout') }}" data-submit-once>
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-60"
                                    dusk="logout-button"
                                >
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </nav>
            </header>
        @else
            {{-- Navbar Horizontal Standar untuk Warga dan Tamu --}}
            <header class="border-b border-gray-200 bg-white">
                <nav aria-label="Navigasi utama" class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">
                    <a
                        href="{{ url('/') }}"
                        dusk="nav-brand"
                        class="text-lg font-semibold text-brand-700"
                    >
                        {{ config('app.name') }}
                    </a>

                    {{-- Tombol buka/tutup menu seluler (muncul saat JS aktif di layar < md) --}}
                    <button
                        type="button"
                        hidden
                        data-nav-toggle
                        aria-controls="nav-menu-panel"
                        aria-expanded="false"
                        dusk="nav-toggle"
                        class="ms-auto inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50 md:hidden"
                    >
                        <span class="sr-only" data-nav-toggle-label>Buka menu navigasi</span>
                        <svg data-nav-icon-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg data-nav-icon-close class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div
                        id="nav-menu-panel"
                        dusk="nav-menu-panel"
                        class="w-full max-md:border-t max-md:border-gray-100 max-md:pt-2 md:flex md:w-auto md:flex-1 md:items-center md:justify-between md:gap-x-6"
                    >
                        <ul class="flex flex-col gap-y-1 text-sm font-medium md:flex-row md:flex-wrap md:items-center md:gap-x-4 md:gap-y-2">
                            @auth
                                @foreach ($links as $link)
                                    @php($isCurrent = request()->routeIs($link['active'] ?? $link['route']))
                                    <li>
                                        <a
                                            href="{{ route($link['route']) }}"
                                            @class([
                                                'block rounded-md px-2 py-2 md:px-1 md:py-0.5',
                                                'bg-brand-50 text-brand-700 md:bg-transparent md:underline md:underline-offset-4' => $isCurrent,
                                                'text-gray-700 hover:bg-gray-50 hover:text-brand-700 md:hover:bg-transparent' => ! $isCurrent,
                                            ])
                                            @if ($isCurrent) aria-current="page" @endif
                                            @isset($link['dusk']) dusk="{{ $link['dusk'] }}" @endisset
                                        >
                                            {{ $link['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li>
                                    <a
                                        href="{{ route('catalog.index') }}"
                                        @class([
                                            'block rounded-md px-2 py-2 md:px-1 md:py-0.5',
                                            'bg-brand-50 text-brand-700 md:bg-transparent md:underline md:underline-offset-4' => request()->routeIs('catalog.*'),
                                            'text-gray-700 hover:bg-gray-50 hover:text-brand-700 md:hover:bg-transparent' => ! request()->routeIs('catalog.*'),
                                        ])
                                        @if (request()->routeIs('catalog.*')) aria-current="page" @endif
                                        dusk="nav-catalog"
                                    >
                                        Katalog
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="{{ route('reference-prices.index') }}"
                                        @class([
                                            'block rounded-md px-2 py-2 md:px-1 md:py-0.5',
                                            'bg-brand-50 text-brand-700 md:bg-transparent md:underline md:underline-offset-4' => request()->routeIs('reference-prices.*'),
                                            'text-gray-700 hover:bg-gray-50 hover:text-brand-700 md:hover:bg-transparent' => ! request()->routeIs('reference-prices.*'),
                                        ])
                                        @if (request()->routeIs('reference-prices.*')) aria-current="page" @endif
                                        dusk="nav-reference-prices"
                                    >
                                        {{ __('reference-prices.nav.public') }}
                                    </a>
                                </li>
                            @endauth
                        </ul>

                        @auth
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm md:ms-auto md:mt-0 md:justify-start md:border-t-0 md:pt-0">
                                @if ($isAdmin)
                                    <a
                                        href="{{ route('admin.dashboard') }}"
                                        dusk="switch-to-admin"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-800 shadow-2xs transition hover:bg-gray-100"
                                    >
                                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-gray-900 text-[9px] font-bold text-white">
                                            A
                                        </span>
                                        <span>Panel Admin</span>
                                        <svg class="h-3.5 w-3.5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                        </svg>
                                    </a>
                                @endif

                                <span class="max-w-40 truncate text-gray-700" dusk="nav-user-name">{{ $user->name }}</span>

                                {{-- Logout hanya lewat POST + CSRF --}}
                                <form method="POST" action="{{ route('logout') }}" data-submit-once>
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-md border border-gray-300 px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                                        dusk="logout-button"
                                    >
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3 text-sm md:ms-auto md:mt-0 md:border-t-0 md:pt-0">
                                <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-brand-700">
                                    Masuk
                                </a>
                                <a href="{{ route('register') }}" class="rounded-md bg-brand-700 px-3 py-1.5 font-medium text-white hover:opacity-90">
                                    Daftar
                                </a>
                            </div>
                        @endauth
                    </div>
                </nav>
            </header>
        @endif

        <div @class(['md:pl-64' => $isAdminPanel])>
            <main id="konten" class="mx-auto max-w-6xl px-4 py-8">
                <x-alert class="mb-6" />

                @if ($title)
                    <h1 class="mb-6 text-2xl font-semibold">{{ $title }}</h1>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
