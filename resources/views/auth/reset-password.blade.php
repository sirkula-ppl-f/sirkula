<x-layouts.guest title="Atur Ulang Kata Sandi">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" data-submit-once novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.input
            name="email"
            label="Email"
            type="email"
            autocomplete="email"
            :value="$request->email"
            :readonly="filled($request->email)"
            required
            dusk="email"
        />

        <x-form.input
            name="password"
            label="Kata sandi baru"
            type="password"
            autocomplete="new-password"
            hint="Minimal 8 karakter, berisi huruf dan angka."
            required
            toggle
            autofocus
            dusk="password"
        />

        <x-form.input
            name="password_confirmation"
            label="Konfirmasi kata sandi baru"
            type="password"
            autocomplete="new-password"
            required
            toggle
            dusk="password-confirmation"
        />

        <x-button dusk="reset-password-button">Simpan Kata Sandi Baru</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-700">
        Tautan tidak berfungsi?
        <a href="{{ route('password.request') }}" class="font-semibold text-brand-700 hover:underline" dusk="request-new-link">Minta tautan baru</a>
    </p>
</x-layouts.guest>
