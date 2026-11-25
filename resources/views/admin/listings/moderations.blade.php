@use('App\Enums\ListingModerationAction')
@use('App\Models\Listing')
@use('App\Support\IndonesianFormat')
@use('Illuminate\Support\Str')

@php
    $hasFilters = collect($filters)->contains(fn ($value) => $value !== null);
    $text = __('listing-moderation.admin.moderations');
    $none = __('listing-moderation.admin.none');
    $actionOptions = collect(ListingModerationAction::cases())->mapWithKeys(fn (ListingModerationAction $action) => [$action->value => $action->label()])->all();
@endphp

<x-layouts.app :title="$text['title']">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-2xl text-sm text-gray-700">{{ $text['intro'] }}</p>

        <a
            href="{{ route('admin.listings.index') }}"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
            dusk="moderation-back"
        >{{ $text['back'] }}</a>
    </div>

    <form
        method="GET"
        action="{{ route('admin.listings.moderations') }}"
        role="search"
        aria-label="{{ $text['filter_label'] }}"
        class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 sm:items-end xl:grid-cols-[12rem_1fr_auto_auto_auto]"
        dusk="moderation-filter-form"
    >
        <x-form.select name="aksi" :label="$text['filter']['action']" :placeholder="$text['filter']['all_actions']" :options="$actionOptions" :value="$filters['aksi']?->value" dusk="moderation-action-filter" />

        <x-form.input name="cari" type="search" :label="$text['filter']['search']" :value="$filters['cari']" maxlength="100" dusk="moderation-search" />

        <x-form.input name="dari" type="date" :label="$text['filter']['from']" :value="$filters['dari']" dusk="moderation-from-filter" />

        <x-form.input name="sampai" type="date" :label="$text['filter']['until']" :value="$filters['sampai']" dusk="moderation-until-filter" />

        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                dusk="moderation-filter-button"
            >{{ $text['filter']['apply'] }}</button>

            @if ($hasFilters)
                <a
                    href="{{ route('admin.listings.moderations') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    dusk="moderation-filter-reset"
                >{{ $text['filter']['reset'] }}</a>
            @endif
        </div>
    </form>

    {{-- Di layar sempit tabel di-scroll di dalam wadah ini, halaman tidak ikut bergeser. --}}
    <div
        class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        role="region"
        aria-label="{{ $text['table_label'] }}"
        tabindex="0"
        dusk="moderation-table-region"
    >
        <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="moderation-table">
            <caption class="sr-only">
                {{ __('listing-moderation.admin.moderations.table_caption', ['page' => $moderations->currentPage(), 'last' => max($moderations->lastPage(), 1)]) }}
            </caption>

            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                <tr>
                    <th scope="col" class="px-4 py-3">{{ $text['columns']['time'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $text['columns']['listing'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $text['columns']['action'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $text['columns']['reason'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $text['columns']['admin'] }}</th>
                    <th scope="col" class="px-4 py-3">{{ $text['columns']['request_id'] }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse ($moderations as $moderation)
                    @php($listing = $moderation->listing)
                    <tr dusk="moderation-row-{{ $moderation->id }}">
                        <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ IndonesianFormat::dateTimeWib($moderation->created_at) }}</td>
                        <th scope="row" class="min-w-48 max-w-xs px-4 py-3 text-left font-medium text-gray-900">
                            {{-- Listing draf tidak punya halaman detail admin. --}}
                            @if ($listing && $listing->review_status !== Listing::REVIEW_DRAFT)
                                <a
                                    href="{{ route('admin.listings.show', $listing) }}"
                                    class="wrap-break-word text-brand-700 underline-offset-4 hover:underline"
                                    dusk="moderation-listing-link-{{ $moderation->id }}"
                                >{{ $listing->title }}</a>
                            @else
                                <span class="wrap-break-word">{{ $listing?->title ?? $none }}</span>
                            @endif
                            <span class="block text-xs font-normal text-gray-500">{{ __('listing-moderation.admin.listing_id', ['id' => $moderation->listing_id]) }}</span>
                        </th>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ $moderation->action->label() }}</td>
                        <td class="min-w-56 max-w-sm px-4 py-3 wrap-break-word text-gray-800">{{ Str::limit($moderation->reason, 100) }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $moderation->admin?->name ?? $none }}</td>
                        <td class="px-4 py-3">
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-800" title="{{ $moderation->request_id }}">{{ Str::limit($moderation->request_id, 8, '…') }}</code>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-600" dusk="moderation-empty">
                            @if ($hasFilters)
                                {{ $text['empty_filtered'] }}
                                <a href="{{ route('admin.listings.moderations') }}" class="font-semibold text-brand-700 underline underline-offset-4" dusk="moderation-empty-reset">{{ $text['empty_reset'] }}</a>
                            @else
                                {{ $text['empty'] }}
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $moderations->links() }}
    </div>
</x-layouts.app>
