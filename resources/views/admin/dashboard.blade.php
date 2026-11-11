<x-layouts.app title="Admin">
    <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <p class="text-gray-700">
            Halo, <span class="font-semibold" dusk="admin-user-name">{{ auth()->user()->name }}</span>. Anda masuk sebagai admin.
        </p>
        <p class="mt-2 text-sm text-gray-600">
            Pilih menu pengelolaan di bawah ini atau melalui bilah navigasi di atas.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <a
            href="{{ route('admin.categories.index') }}"
            class="flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-600"
        >
            <div>
                <h2 class="text-base font-semibold text-gray-900">Kategori</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Kelola kategori produk nasional dan tipe listing yang berlaku.
                </p>
            </div>
            <span class="mt-4 text-sm font-semibold text-brand-700">Buka Kategori &rarr;</span>
        </a>

        <a
            href="{{ route('admin.materials.index') }}"
            class="flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-600"
        >
            <div>
                <h2 class="text-base font-semibold text-gray-900">Material</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Kelola jenis material daur ulang untuk listing dan harga acuan.
                </p>
            </div>
            <span class="mt-4 text-sm font-semibold text-brand-700">Buka Material &rarr;</span>
        </a>

        <a
            href="{{ route('admin.users.index') }}"
            dusk="manage-users-link"
            class="flex flex-col justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-brand-600"
        >
            <div>
                <h2 class="text-base font-semibold text-gray-900">Kelola Akun</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Tangguhkan atau aktifkan kembali akun pengguna beserta alasannya.
                </p>
            </div>
            <span class="mt-4 text-sm font-semibold text-brand-700">Buka Kelola Akun &rarr;</span>
        </a>
    </div>
</x-layouts.app>
