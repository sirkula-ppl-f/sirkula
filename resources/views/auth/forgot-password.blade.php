<x-layouts.guest title="Lupa Kata Sandi">
    <p class="mb-5 text-sm text-gray-700">
        Masukkan email akun Anda. Jika email terdaftar, kami akan mengirim tautan untuk mengatur ulang kata sandi.
        Tautan berlaku selama {{ config('auth.passwords.users.expire') }} menit dan hanya bisa dipakai sekali.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" data-submit-once novalidate>
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

        <x-button dusk="send-reset-link-button">Kirim Tautan Reset</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-700">
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline" dusk="back-to-login-link">
            Kembali ke halaman masuk
        </a>
    </p>
</x-layouts.guest>
