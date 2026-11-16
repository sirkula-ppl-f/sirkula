@php
    $badge = [
        'published' => 'bg-emerald-100 text-emerald-800',
        'draft'     => 'bg-amber-100 text-amber-800',
        'archived'  => 'bg-gray-200 text-gray-700',
    ];
@endphp
<x-layouts.app>
    <div>
        <div class="mb-4">
            <a class="inline-flex items-center text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors group" href="{{ route('admin.dashboard') }}">
                <span class="mr-1.5 transition-transform group-hover:-translate-x-1">←</span>
                {{ __('guides.back_dashboard') }}
            </a>
        </div>

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('guides.title') }}</h1>
                <p class="mt-1 text-gray-500">{{ __('guides.subtitle') }}</p>
            </div>
            <a href="{{ route('admin.guides.create') }}" dusk="create-guide-button"
               class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-150 ease-in-out shadow-sm">
                {{ __('guides.create_button') }}
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative shadow-sm" role="alert" dusk="alert">
                <span class="block sm:inline">{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <section class="lg:col-span-8 overflow-x-auto relative bg-white rounded-2xl border border-gray-200/80 shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">{{ __('guides.columns.title') }}</th>
                            <th class="py-3 font-medium">{{ __('guides.columns.status') }}</th>
                            <th class="py-3 font-medium">{{ __('guides.columns.views') }}</th>
                            <th class="py-3 font-medium">{{ __('guides.columns.interactions') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse ($guides as $guide)
                        @php
                            $labels = collect($guide->placement_labels)->sort(function ($a, $b) {
                                // Memastikan "Form listing" selalu berada di urutan pertama jika ada
                                if (str_contains($a, 'Form listing')) return -1;
                                if (str_contains($b, 'Form listing')) return 1;
                                return strcmp($a, $b);
                            })->all();
                        @endphp
                        <tr dusk="guide-row-{{ $guide->slug }}">
                            <td class="px-5 py-4">
                                {{ $guide->title }}
                                <span class="block text-xs text-gray-500">{{ implode(', ', $labels) }}</span>
                            </td>
                            <td>
                                <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $badge[$guide->status] ?? $badge['draft'] }}">
                                    {{ $guide->status_label }}
                                </span>
                            </td>
                            <td>{{ $guide->views_count ? number_format($guide->views_count, 0, ',', '.') : '-' }}</td>
                            <td>{{ $guide->interactions_count ? number_format($guide->interactions_count, 0, ',', '.') : '-' }}</td>
                            <td class="pr-5">
                                <a href="{{ route('admin.guides.edit', $guide) }}" dusk="edit-guide-{{ $guide->slug }}" class="font-semibold text-brand-600 hover:text-brand-700 transition-colors">{{ __('guides.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('guides.empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>

            <aside class="lg:col-span-4 bg-white rounded-2xl border border-gray-200/80 p-8 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900">{{ __('guides.preview.title') }}</h2>
                @if ($preview)
                    <p class="mt-3 text-xs text-gray-500">{{ __('guides.preview.shown_in', ['places' => mb_strtolower(implode(', ', $preview->placement_labels))]) }}</p>
                    <div class="mt-3 rounded-xl bg-brand-50 p-4 text-sm">
                        <p class="font-semibold text-brand-700">{{ __('guides.preview.heading', ['title' => $preview->title]) }}</p>
                        <p class="mt-1 text-gray-800">{{ $preview->body }}</p>
                    </div>
                @endif
                <p class="mt-4 text-xs text-gray-500 font-normal">{{ __('guides.preview.privacy') }}</p>
            </aside>
        </div>
    </div>
</x-layouts.app>