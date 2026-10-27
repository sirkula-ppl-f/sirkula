@props([
    'name',
    'duskKey',
    'active',
    'inUse',
    'editUrl',
    'deactivateUrl',
    'activateUrl',
    'deleteUrl',
])

@php
    $buttonClass = 'inline-flex items-center rounded-md border px-3 py-1.5 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-60';
@endphp

{{-- Aksi per baris data master. Semua aksi berupa form biasa, jadi tetap jalan tanpa JavaScript. --}}
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ $editUrl }}" class="{{ $buttonClass }} border-gray-300 bg-white text-gray-800 hover:bg-gray-50" dusk="edit-{{ $duskKey }}">
        Ubah<span class="sr-only"> {{ $name }}</span>
    </a>

    @if ($active)
        <form method="POST" action="{{ $deactivateUrl }}" data-submit-once>
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
        <form method="POST" action="{{ $deleteUrl }}" data-submit-once>
            @csrf
            @method('DELETE')
            <button type="submit" class="{{ $buttonClass }} border-red-300 bg-white text-red-700 hover:bg-red-50" dusk="delete-{{ $duskKey }}">
                Hapus<span class="sr-only"> {{ $name }}</span>
            </button>
        </form>
    @endunless
</div>
