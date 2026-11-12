@props(['title' => null])

@php
    $user = auth()->user();

    // Tautan navbar sesuai role. Tambahkan tautan modul baru di sini.
    // Key opsional: 'active' (pola routeIs untuk status aktif, bawaan 'route') dan 'dusk'.
    $links = [];

    if ($user) {
        $links[] = ['label' => 'Beranda', 'route' => 'dashboard'];
        $links[] = ['label' => 'Keranjang', 'route' => 'cart.index'];
        $links[] = ['label' => 'Profil', 'route' => 'profil.edit', 'active' => 'profil.*', 'dusk' => 'nav-profil'];

        if ($user->isAdmin()) {
            $links[] = ['label' => 'Admin', 'route' => 'admin.dashboard'];
            $links[] = ['label' => __('master-data.categories.nav'), 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'dusk' => 'nav-categories'];
            $links[] = ['label' => __('master-data.materials.nav'), 'route' => 'admin.materials.index', 'active' => 'admin.materials.*', 'dusk' => 'nav-materials'];
            $links[] = ['label' => __('reference-prices.nav.admin'), 'route' => 'admin.reference-prices.index', 'active' => 'admin.reference-prices.*', 'dusk' => 'nav-admin-reference-prices'];
            $links[] = ['label' => 'Kelola Akun', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'dusk' => 'nav-users'];
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
                                >
                                    Katalog
                                </a>
                            </li>
                        @endauth
                    </ul>

                    @auth
                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm md:ms-auto md:mt-0 md:justify-start md:border-t-0 md:pt-0">
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

        <main id="konten" class="mx-auto max-w-6xl px-4 py-8">
            <x-alert class="mb-6" />

            @if ($title)
                <h1 class="mb-6 text-2xl font-semibold">{{ $title }}</h1>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>