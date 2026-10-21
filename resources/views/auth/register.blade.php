<x-layouts.guest title="Daftar Akun">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5" data-submit-once novalidate>
        @csrf

        <x-form.input
            name="name"
            label="Nama lengkap"
            autocomplete="name"
            maxlength="100"
            required
            autofocus
            dusk="name"
        />

        <x-form.input
            name="email"
            label="Email"
            type="email"
            autocomplete="email"
            required
            dusk="email"
        />

        <x-form.input
            name="password"
            label="Kata sandi"
            type="password"
            autocomplete="new-password"
            hint="Minimal 8 karakter, berisi huruf dan angka."
            required
            toggle
            dusk="password"
        />

        <x-form.input
            name="password_confirmation"
            label="Konfirmasi kata sandi"
            type="password"
            autocomplete="new-password"
            required
            toggle
            dusk="password-confirmation"
        />

        <x-button dusk="register-button">Daftar</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-700">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Masuk</a>
    </p>
</x-layouts.guest>
