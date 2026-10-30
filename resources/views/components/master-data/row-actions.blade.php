@props([
    'name',
    'duskKey',
    'active',
    'inUse',
    'editUrl',
    'deactivateUrl',
    'activateUrl',
    'deleteUrl',
    'entity' => null,
])

@php
    $buttonClass = 'inline-flex items-center rounded-md border px-3 py-1.5 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-60';

    // Teks dialog konfirmasi: master-data.{entity}.confirm.* bila ada, selain itu teks umum master-data.confirm.*.
    $confirmText = function (string $key) use ($entity, $name): string {
        $specific = "master-data.{$entity}.confirm.{$key}";

        return __($entity && trans()->has($specific) ? $specific : "master-data.confirm.{$key}", ['name' => $name]);
    };
@endphp

{{--
    Aksi per baris data master. Semua aksi berupa form biasa, jadi tetap jalan tanpa JavaScript.
    Nonaktifkan dan Hapus memakai data-confirm, dibaca <x-confirm-dialog /> bila ada di halaman.
--}}
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ $editUrl }}" class="{{ $buttonClass }} border-gray-300 bg-white text-gray-800 hover:bg-gray-50" dusk="edit-{{ $duskKey }}">
        Ubah<span class="sr-only"> {{ $name }}</span>
    </a>

    @if ($active)
        <form
            method="POST"
            action="{{ $deactivateUrl }}"
            data-submit-once
            data-confirm="{{ $confirmText('deactivate') }}"
            data-confirm-title="{{ $confirmText('deactivate_title') }}"
            data-confirm-label="{{ $confirmText('deactivate_label') }}"
        >
            @csrf
            @method('PATCH')
            <button type="submit" class="{{ $buttonClass }} border-gray-300 bg-white text-gray-800 hover:bg-gray-50" dusk="deactivate-{{ $duskKey }}">
                Nonaktifkan<span class="sr-only"> {{ $name }}</span>
            </button>
        </form>
    @else
        <form method="POST" action="{{ $activateUrl }}" data-submit-once>
            @csrf
            @method('PATCH')
            <button type="submit" class="{{ $buttonClass }} border-brand-600 bg-white text-brand-700 hover:bg-brand-50" dusk="activate-{{ $duskKey }}">
                Aktifkan<span class="sr-only"> {{ $name }}</span>
            </button>
        </form>
    @endif

    {{-- Data yang sudah dipakai hanya dapat dinonaktifkan. --}}
    @unless ($inUse)
        <form
            method="POST"
            action="{{ $deleteUrl }}"
            data-submit-once
            data-confirm="{{ $confirmText('delete') }}"
            data-confirm-title="{{ $confirmText('delete_title') }}"
            data-confirm-label="{{ $confirmText('delete_label') }}"
            data-confirm-variant="danger"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="{{ $buttonClass }} border-red-300 bg-white text-red-700 hover:bg-red-50" dusk="delete-{{ $duskKey }}">
                Hapus<span class="sr-only"> {{ $name }}</span>
            </button>
        </form>
    @endunless
</div>
