<x-layouts.guest title="Masuk">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-submit-once novalidate>
        @csrf

        <x-form.input
            name="email"
            label="Email"
            type="email"
            autocomplete="email"
            required
            autofocus
            dusk="email"
        />

        <div class="space-y-2">
            <x-form.input
                name="password"
                label="Kata sandi"
                type="password"
                autocomplete="current-password"
                required
                toggle
                dusk="password"
            />

            <p class="text-end text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:underline" dusk="forgot-password-link">
                    Lupa kata sandi?
                </a>
            </p>
        </div>

        <x-button dusk="login-button">Masuk</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-700">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline" dusk="register-link">Daftar</a>
    </p>
</x-layouts.guest>
