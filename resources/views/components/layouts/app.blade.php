
@props(['title' => null])

@php
    $user = auth()->user();

    // Tautan navbar sesuai role. Tambahkan tautan modul baru di sini.
    // Key opsional: 'active' (pola routeIs untuk status aktif, bawaan 'route') dan 'dusk'.
    $links = [];

    if ($user) {
        $links[] = ['label' => 'Beranda', 'route' => 'dashboard'];
        $links[] = ['label' => 'Keranjang', 'route' => 'cart.index'];

        if ($user->isAdmin()) {
            $links[] = ['label' => 'Admin', 'route' => 'admin.dashboard'];
            $links[] = ['label' => __('master-data.categories.nav'), 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'dusk' => 'nav-categories'];
            $links[] = ['label' => __('master-data.materials.nav'), 'route' => 'admin.materials.index', 'active' => 'admin.materials.*', 'dusk' => 'nav-materials'];
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
                    href="{{ $user ? route('dashboard') : route('catalog.index') }}"
                    class="text-lg font-semibold text-brand-700"
                >
                    {{ config('app.name') }}
                </a>

                <ul class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium">
                    @auth
                        @foreach ($links as $link)
                            @php($isCurrent = request()->routeIs($link['active'] ?? $link['route']))
                            <li>
                                <a
                                    href="{{ route($link['route']) }}"
                                    @class([
                                        'rounded-md px-1 py-0.5',
                                        'text-brand-700 underline underline-offset-4' => $isCurrent,
                                        'text-gray-700 hover:text-brand-700' => ! $isCurrent,
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
                                    'rounded-md px-1 py-0.5',
                                    'text-brand-700 underline underline-offset-4' => request()->routeIs('catalog.*'),
                                    'text-gray-700 hover:text-brand-700' => ! request()->routeIs('catalog.*'),
                                ])
                                @if (request()->routeIs('catalog.*')) aria-current="page" @endif
                            >
                                Katalog
                            </a>
                        </li>
                    @endauth
                </ul>

                @auth
                    <div class="ms-auto flex items-center gap-3 text-sm">
                        <span class="max-w-40 truncate text-gray-700" dusk="nav-user-name">{{ $user->name }}</span>

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
                    <div class="ms-auto flex items-center gap-3 text-sm">
                        <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-brand-700">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="rounded-md bg-brand-700 px-3 py-1.5 font-medium text-white hover:opacity-90">
                            Daftar
                        </a>
                    </div>
                @endauth
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
