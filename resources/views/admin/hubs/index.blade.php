
<x-layouts.app title="Manajemen Hub">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Manajemen Hub
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    Kelola hub komunitas Sirkula.
                </p>
            </div>

            <a href="{{ route('admin.hubs.create') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + Tambah Hub
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <form method="GET" action="{{ route('admin.hubs.index') }}"
                  class="mb-6 flex flex-wrap gap-3">

                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari nama Hub..."
                       class="flex-1 rounded-lg border border-gray-300 px-4 py-2">

                <select name="status"
                        class="rounded-lg border border-gray-300 px-4 py-2">
                    <option value="">Semua Status</option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>
                        Aktif
                    </option>
                    <option value="ARCHIVED" @selected(request('status') === 'ARCHIVED')>
                        Diarsipkan
                    </option>
                </select>

                <button type="submit"
                        class="rounded-lg bg-gray-800 px-4 py-2 text-white">
                    Cari
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-gray-50 text-gray-700">
                        <tr>
                            <th class="p-3">Nama Hub</th>
                            <th class="p-3">Tipe</th>
                            <th class="p-3">Wilayah</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse ($hubs as $hub)
                            <tr>
                                <td class="p-3 font-medium">
                                    {{ $hub->name }}
                                </td>
                                <td class="p-3">
                                    {{ str_replace('_', ' ', $hub->type) }}
                                </td>
                                <td class="p-3">
                                    {{ $hub->region?->name ?? '-' }}
                                </td>
                                <td class="p-3">
                                    {{ $hub->status === 'ACTIVE' ? 'Aktif' : 'Diarsipkan' }}
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-3">
                                        <a href="{{ route('admin.hubs.show', $hub) }}"
                                           class="text-blue-600 hover:underline">
                                            Detail
                                        </a>

                                        <a href="{{ route('admin.hubs.edit', $hub) }}"
                                           class="text-amber-600 hover:underline">
                                            Edit
                                        </a>

                                        @if ($hub->status === 'ACTIVE')
                                            <form method="POST"
                                                  action="{{ route('admin.hubs.archive', $hub) }}"
                                                  onsubmit="return confirm('Arsipkan Hub ini?')">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                        class="text-red-600 hover:underline">
                                                    Arsipkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">
                                    Belum ada data Hub.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $hubs->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
