<x-layouts.app title="Beranda">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <p class="text-gray-700">
            Selamat datang, <span class="font-semibold" dusk="dashboard-user-name">{{ auth()->user()->name }}</span>.
        </p>
        <p class="mt-2 text-sm text-gray-600">Halaman ini masih placeholder. Fitur warga akan ditambahkan di PBI berikutnya.</p>
    </div>
</x-layouts.app>
