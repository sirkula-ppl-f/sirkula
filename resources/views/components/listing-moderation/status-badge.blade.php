@props(['status'])

@use('App\Enums\ListingModerationStatus')

{{-- Status moderasi ditulis sebagai teks (Diizinkan tampil/Dinonaktifkan admin), tidak hanya dibedakan lewat warna. --}}
<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
    'bg-brand-50 text-brand-800 ring-brand-100' => $status === ListingModerationStatus::Visible,
    'bg-red-50 text-red-800 ring-red-200' => $status === ListingModerationStatus::Hidden,
    'bg-gray-100 text-gray-700 ring-gray-300' => ! $status instanceof ListingModerationStatus,
]) }}>{{ $status instanceof ListingModerationStatus ? $status->label() : __('listing-moderation.admin.none') }}</span>
