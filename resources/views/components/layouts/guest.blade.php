@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:shadow">
            Lewati ke konten utama
        </a>

        <main id="konten" class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="{{ url('/') }}" class="mb-6 text-2xl font-semibold text-brand-700">
                {{ config('app.name') }}
            </a>

            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                @if ($title)
                    <h1 class="mb-6 text-xl font-semibold">{{ $title }}</h1>
                @endif

                <x-alert class="mb-6" />

                {{ $slot }}
            </div>
        </main>
    </body>
</html>
