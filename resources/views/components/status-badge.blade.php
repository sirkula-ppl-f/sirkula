@props(['active'])

{{-- Status ditulis sebagai teks, tidak hanya dibedakan lewat warna. --}}
<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
    'bg-brand-50 text-brand-800 ring-brand-100' => $active,
    'bg-gray-100 text-gray-700 ring-gray-300' => ! $active,
]) }}>{{ $active ? 'Aktif' : 'Nonaktif' }}</span>
