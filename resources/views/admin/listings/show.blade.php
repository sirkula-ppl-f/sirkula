@use('App\Enums\ListingModerationStatus')
@use('App\Enums\ListingType')
@use('App\Http\Requests\Admin\ModerateListingRequest')
@use('App\Support\IndonesianFormat')
@use('App\Support\ListingStatusLabel')
@use('Illuminate\Support\Facades\Storage')
@use('Illuminate\Support\Str')

@php
    $none = __('listing-moderation.admin.none');
    $fields = __('listing-moderation.admin.show.fields');
    $statusText = __('listing-moderation.admin.show.status');
    $historyColumns = __('listing-moderation.admin.history.columns');
    $title = (string) $listing->title;
    $isHidden = $listing->moderation_status === ListingModerationStatus::Hidden;
    $primaryImage = $listing->images->firstWhere('is_primary', true) ?? $listing->images->first();
    $form = $isHidden ? 'restore' : 'deactivate';
    $sectionClass = 'rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200';
@endphp

<x-layouts.app :title="__('listing-moderation.admin.show.title')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold wrap-break-word text-gray-900" dusk="listing-title">{{ $title }}</h2>
                <p class="text-sm text-gray-600">{{ __('listing-moderation.admin.listing_id', ['id' => $listing->id]) }}</p>
            </div>

            <a
                href="{{ route('admin.listings.index') }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                dusk="listing-back"
            >{{ __('listing-moderation.admin.show.back') }}</a>
        </div>

        <x-alert type="error" :message="session('error')" />

        @if (session('visibility_note'))
            <div role="status" class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm wrap-break-word text-amber-900" dusk="listing-visibility-note">
                {{ session('visibility_note') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Informasi listing --}}
            <section aria-labelledby="listing-info-title" class="{{ $sectionClass }} lg:col-span-2" dusk="listing-info">
                <h3 id="listing-info-title" class="mb-4 text-base font-semibold text-gray-900">{{ __('listing-moderation.admin.show.info_title') }}</h3>

                <div class="flex flex-col gap-6 sm:flex-row">
                    <div class="sm:w-40 sm:shrink-0">
                        @if ($primaryImage)
                            <img
                                src="{{ Storage::url($primaryImage->path_foto) }}"
                                alt="{{ __('listing-moderation.admin.show.photo_alt', ['title' => $title]) }}"
                                class="aspect-4/3 w-full rounded-lg object-cover ring-1 ring-gray-200"
                                dusk="listing-photo"
                            >
                        @else
                            <div class="flex aspect-4/3 w-full items-center justify-center rounded-lg bg-gray-100 px-2 text-center text-xs text-gray-500">
                                {{ __('listing-moderation.admin.show.no_photo') }}
                            </div>
                        @endif
                    </div>

                    <dl class="grid min-w-0 flex-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">{{ $fields['type'] }}</dt>
                            <dd class="text-gray-900">{{ ListingType::tryFrom((string) $listing->listing_type)?->label() ?? ($listing->listing_type ?: $none) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['category'] }}</dt>
                            <dd class="text-gray-900">{{ $listing->category?->name ?? $none }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['material'] }}</dt>
                            <dd class="text-gray-900">{{ $listing->material?->name ?? $none }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['condition'] }}</dt>
                            <dd class="text-gray-900">{{ $listing->item_condition ?: $none }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-gray-500">{{ $fields['seller'] }}</dt>
                            <dd class="wrap-break-word text-gray-900" dusk="listing-seller">
                                {{ $listing->seller?->name ?? $none }}
                                @if ($listing->seller)
                                    <span class="block text-xs text-gray-600">{{ $listing->seller->email }}</span>
                                    @unless ($listing->seller->isActive())
                                        <span class="block text-xs font-medium text-amber-800">{{ __('listing-moderation.admin.show.seller_suspended') }}</span>
                                    @endunless
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['hub'] }}</dt>
                            <dd class="text-gray-900">
                                {{ $listing->hub?->name ?? $none }}
                                @if ($listing->hub && $listing->hub->status !== 'ACTIVE')
                                    <span class="block text-xs font-medium text-amber-800">{{ __('listing-moderation.admin.show.hub_inactive') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['region'] }}</dt>
                            <dd class="text-gray-900">{{ $listing->region?->name ?? $none }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['price'] }}</dt>
                            <dd class="text-gray-900" dusk="listing-price">{{ __('listing-moderation.admin.show.price_value', ['price' => IndonesianFormat::rupiah((int) $listing->price), 'unit' => ListingStatusLabel::saleUnit($listing->sale_unit)]) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['stock'] }}</dt>
                            <dd class="text-gray-900" dusk="listing-stock">{{ (int) $listing->stock }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ $fields['created_at'] }}</dt>
                            <dd class="text-gray-900">{{ $listing->created_at ? IndonesianFormat::dateTimeWib($listing->created_at) : $none }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Status, alasan tidak tayang, dan pesanan --}}
            <section aria-labelledby="listing-status-title" class="{{ $sectionClass }}" dusk="listing-status">
                <h3 id="listing-status-title" class="mb-4 text-base font-semibold text-gray-900">{{ __('listing-moderation.admin.show.status_title') }}</h3>

                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ $statusText['review'] }}</dt>
                        <dd class="text-gray-900" dusk="listing-review-status">{{ ListingStatusLabel::review($listing->review_status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ $statusText['publish'] }}</dt>
                        <dd class="text-gray-900" dusk="listing-publish-status">{{ ListingStatusLabel::publish($listing->publish_status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ $statusText['moderation'] }}</dt>
                        <dd dusk="listing-moderation-status"><x-listing-moderation.status-badge :status="$listing->moderation_status" /></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ $statusText['catalog'] }}</dt>
                        <dd class="font-semibold text-gray-900" dusk="listing-catalog-status">{{ $blockers === [] ? __('listing-moderation.catalog.yes') : __('listing-moderation.catalog.no') }}</dd>
                    </div>
                </dl>

                @if ($blockers !== [])
                    <div class="mt-4 rounded-md bg-gray-50 p-3 text-sm text-gray-800" dusk="listing-blockers">
                        <p id="listing-blockers-title" class="font-medium">{{ __('listing-moderation.admin.show.blockers_title') }}</p>
                        <ul class="mt-1 list-inside list-disc" aria-labelledby="listing-blockers-title">
                            @foreach ($blockers as $blocker)
                                <li>{{ __($blocker) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 border-t border-gray-100 pt-4" dusk="listing-orders">
                    <h4 class="text-sm font-semibold text-gray-900">{{ __('listing-moderation.admin.show.orders_title') }}</h4>
                    <p class="mt-1 text-sm text-gray-800">{{ __('listing-moderation.admin.show.orders_count', ['count' => $orderCount]) }}</p>
                    <p class="mt-1 text-xs text-gray-600">{{ __('listing-moderation.admin.show.orders_note') }}</p>
                </div>
            </section>
        </div>

        {{-- Aksi moderasi: Nonaktifkan bila VISIBLE, Pulihkan bila HIDDEN. Tanpa JavaScript form tetap terkirim. --}}
        <section aria-labelledby="listing-action-title" class="{{ $sectionClass }}">
            <h3 id="listing-action-title" class="text-base font-semibold text-gray-900">{{ __("listing-moderation.admin.form.{$form}_title") }}</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-700">{{ __("listing-moderation.admin.form.{$form}_intro") }}</p>

            <form
                method="POST"
                action="{{ route($isHidden ? 'admin.listings.restore' : 'admin.listings.deactivate', $listing) }}"
                class="mt-4 max-w-2xl space-y-4"
                data-submit-once
                data-confirm="{{ __("listing-moderation.admin.confirm.{$form}_message", ['title' => $title]) }}"
                data-confirm-title="{{ __("listing-moderation.admin.confirm.{$form}_title") }}"
                data-confirm-label="{{ __("listing-moderation.admin.confirm.{$form}_label") }}"
                @unless ($isHidden) data-confirm-variant="danger" @endunless
                dusk="listing-{{ $form }}-form"
            >
                @csrf
                @method('PATCH')

                <x-form.textarea
                    name="reason"
                    :label="__('listing-moderation.admin.form.reason')"
                    :hint="__('listing-moderation.admin.form.reason_hint', ['min' => ModerateListingRequest::REASON_MIN, 'max' => ModerateListingRequest::REASON_MAX])"
                    rows="4"
                    maxlength="{{ ModerateListingRequest::REASON_MAX }}"
                    aria-required="true"
                    :autofocus="$errors->has('reason')"
                    dusk="listing-reason"
                />

                <x-button class="sm:w-auto" dusk="listing-{{ $form }}-button">{{ __("listing-moderation.admin.form.{$form}_button") }}</x-button>
            </form>
        </section>

        {{-- Riwayat moderasi listing ini --}}
        <section aria-labelledby="listing-history-title" class="space-y-3">
            <h3 id="listing-history-title" class="text-base font-semibold text-gray-900">{{ __('listing-moderation.admin.history.title') }}</h3>

            <div
                class="relative overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                role="region"
                aria-label="{{ __('listing-moderation.admin.history.table_label') }}"
                tabindex="0"
                dusk="listing-history-region"
            >
                <table class="min-w-full divide-y divide-gray-200 text-sm" dusk="listing-history-table">
                    <caption class="sr-only">{{ __('listing-moderation.admin.history.table_caption', ['title' => $title]) }}</caption>

                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ $historyColumns['time'] }}</th>
                            <th scope="col" class="px-4 py-3">{{ $historyColumns['action'] }}</th>
                            <th scope="col" class="px-4 py-3">{{ $historyColumns['reason'] }}</th>
                            <th scope="col" class="px-4 py-3">{{ $historyColumns['admin'] }}</th>
                            <th scope="col" class="px-4 py-3">{{ $historyColumns['request_id'] }}</th>
                            <th scope="col" class="px-4 py-3">{{ $historyColumns['review_snapshot'] }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse ($history as $moderation)
                            <tr dusk="listing-history-row-{{ $moderation->id }}">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ IndonesianFormat::dateTimeWib($moderation->created_at) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $moderation->action->label() }}</td>
                                <td class="min-w-56 max-w-md whitespace-pre-line px-4 py-3 wrap-break-word text-gray-800">{{ $moderation->reason }}</td>
                                <td class="px-4 py-3 text-gray-800">{{ $moderation->admin?->name ?? $none }}</td>
                                <td class="px-4 py-3">
                                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-800" title="{{ $moderation->request_id }}">{{ Str::limit($moderation->request_id, 8, '…') }}</code>
                                </td>
                                <td class="px-4 py-3 text-gray-800">{{ ListingStatusLabel::review($moderation->review_status_snapshot) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-600" dusk="listing-history-empty">{{ __('listing-moderation.admin.history.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <x-confirm-dialog />
</x-layouts.app>
