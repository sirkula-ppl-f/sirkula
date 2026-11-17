@props(['title' => null])

@php
    $user = auth()->user();

    // Tautan navbar sesuai role. Dipisahkan antara navigasi utama (warga/publik)
    // dan navigasi pengelolaan (admin) agar hierarki visual jelas dan tidak menumpuk.
    // Key opsional: 'active' (pola routeIs untuk status aktif, bawaan 'route') dan 'dusk'.
    $userLinks = [];
    $adminLinks = [];

    if ($user) {
        $userLinks[] = ['label' => 'Beranda', 'route' => 'dashboard'];
        $userLinks[] = ['label' => 'Keranjang', 'route' => 'cart.index'];
        $userLinks[] = ['label' => 'Profil', 'route' => 'profil.edit', 'active' => 'profil.*', 'dusk' => 'nav-profil'];
        $userLinks[] = ['label' => __('reference-prices.nav.public'), 'route' => 'reference-prices.index', 'active' => 'reference-prices.*', 'dusk' => 'nav-reference-prices'];

        if ($user->isAdmin()) {
            $adminLinks[] = ['label' => 'Admin', 'route' => 'admin.dashboard'];
            $adminLinks[] = ['label' => __('master-data.categories.nav'), 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'dusk' => 'nav-categories'];
            $adminLinks[] = ['label' => __('master-data.materials.nav'), 'route' => 'admin.materials.index', 'active' => 'admin.materials.*', 'dusk' => 'nav-materials'];
            $adminLinks[] = ['label' => 'Hub', 'route' => 'admin.hubs.index', 'active' => 'admin.hubs.*', 'dusk' => 'nav-hubs'];
            $adminLinks[] = ['label' => __('reference-prices.nav.admin'), 'route' => 'admin.reference-prices.index', 'active' => 'admin.reference-prices.*', 'dusk' => 'nav-admin-reference-prices'];
            $adminLinks[] = ['label' => 'Panduan', 'route' => 'admin.guides.index', 'active' => 'admin.guides.*', 'dusk' => 'nav-guides'];
            $adminLinks[] = ['label' => 'Kelola Akun', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'dusk' => 'nav-users'];
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
                    class="w-full max-md:border-t max-md:border-gray-100 max-md:pt-2 md:flex md:w-auto md:flex-1 md:flex-wrap md:items-center md:justify-between md:gap-x-6 md:gap-y-2.5"
                >
                    <ul class="flex flex-col gap-y-1 text-sm font-medium md:order-1 md:flex-row md:flex-wrap md:items-center md:gap-x-4 md:gap-y-2">
                        @auth
                            @foreach ($userLinks as $link)
                                @php($isCurrent = request()->routeIs($link['active'] ?? $link['route']))
                                <li>
                                    <a
                                        href="{{ route($link['route']) }}"
                                        @class([
                                            'block rounded-md px-2 py-2 md:px-2 md:py-1',
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
                        @if ($user->isAdmin() && count($adminLinks) > 0)
                            <div class="mt-2 border-t border-gray-100 pt-2 md:order-3 md:mt-0 md:flex md:w-full md:items-center md:gap-x-3 md:pt-2.5">
                                <span class="block px-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 md:inline-flex md:items-center md:rounded md:bg-gray-100 md:px-2 md:py-0.5 md:text-gray-600">
                                    Panel Admin
                                </span>
                                <ul class="flex flex-col gap-y-1 text-sm font-medium md:flex-row md:flex-wrap md:items-center md:gap-x-1.5 md:gap-y-1">
                                    @foreach ($adminLinks as $link)
                                        @php($isCurrent = request()->routeIs($link['active'] ?? $link['route']))
                                        <li>
                                            <a
                                                href="{{ route($link['route']) }}"
                                                @class([
                                                    'block rounded-md px-2 py-2 transition md:px-2.5 md:py-1',
                                                    'bg-brand-50 font-semibold text-brand-700 ring-1 ring-brand-600/20' => $isCurrent,
                                                    'text-gray-600 hover:bg-gray-100 hover:text-brand-700' => ! $isCurrent,
                                                ])
                                                @if ($isCurrent) aria-current="page" @endif
                                                @isset($link['dusk']) dusk="{{ $link['dusk'] }}" @endisset
                                            >
                                                {{ $link['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm md:order-2 md:ms-auto md:mt-0 md:justify-start md:border-t-0 md:pt-0">
                            <div class="flex items-center gap-2 min-w-0">
                                @if ($user->isAdmin())
                                    <span class="hidden rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-600/20 sm:inline-block">
                                        Admin
                                    </span>
                                @endif
                                <span class="max-w-40 truncate font-medium text-gray-700" dusk="nav-user-name">{{ $user->name }}</span>
                            </div>

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
                        <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3 text-sm md:order-2 md:ms-auto md:mt-0 md:border-t-0 md:pt-0">
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

        <main id="konten" class="mx-auto max-w-6xl px-4 py-8">
            <x-alert class="mb-6" />

            @if ($title)
                <h1 class="mb-6 text-2xl font-semibold">{{ $title }}</h1>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
