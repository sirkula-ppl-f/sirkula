@props(['status'])

@use('App\Enums\ReferencePriceStatus')

{{-- Status harga acuan ditulis sebagai teks (Berlaku/Terjadwal/Riwayat), tidak hanya dibedakan lewat warna. --}}
<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
    'bg-brand-50 text-brand-800 ring-brand-100' => $status === ReferencePriceStatus::Current,
    'bg-amber-50 text-amber-800 ring-amber-200' => $status === ReferencePriceStatus::Scheduled,
    'bg-gray-100 text-gray-700 ring-gray-300' => $status === ReferencePriceStatus::History,
]) }}>{{ $status->label() }}</span>
