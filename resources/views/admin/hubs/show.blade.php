
<x-layouts.app title="Detail Hub">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $hub->name }}
                </h1>
                <p class="text-sm text-gray-500">Detail Hub Komunitas</p>
            </div>

            <a href="{{ route('admin.hubs.index') }}"
               class="text-blue-600 hover:underline">
                Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">Nama Hub</dt>
                    <dd class="font-semibold">{{ $hub->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Tipe</dt>
                    <dd>{{ str_replace('_', ' ', $hub->type) }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Wilayah</dt>
                    <dd>{{ $hub->region?->name ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd>{{ $hub->status === 'ACTIVE' ? 'Aktif' : 'Diarsipkan' }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm text-gray-500">Alamat Publik</dt>
                    <dd class="whitespace-pre-line">{{ $hub->public_address }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm text-gray-500">Deskripsi</dt>
                    <dd class="whitespace-pre-line">{{ $hub->description ?: '-' }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <a href="{{ route('admin.hubs.edit', $hub) }}"
                   class="inline-block rounded-lg bg-blue-600 px-4 py-2 text-white">
                    Edit Hub
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
