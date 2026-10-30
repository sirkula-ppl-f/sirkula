<x-layouts.app title="Admin">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <p class="text-gray-700">
            Halo, <span class="font-semibold" dusk="admin-user-name">{{ auth()->user()->name }}</span>. Anda masuk sebagai admin.
        </p>
        <p class="mt-2 text-sm text-gray-600">Halaman ini masih placeholder. Fitur admin akan ditambahkan di PBI berikutnya.</p>

        <div class="mt-5 border-t border-gray-100 pt-4">
            <a
                href="{{ route('admin.users.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline"
                dusk="manage-users-link"
            >
                Kelola Status Akun Pengguna &rarr;
            </a>
        </div>
    </div>
</x-layouts.app>
