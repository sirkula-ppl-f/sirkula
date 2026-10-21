@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? $hintId : null, $hasError ? $errorId : null])->filter()->implode(' ');

    // Kata sandi tidak pernah diisi ulang dari old().
    $value = $type === 'password' ? null : old($name, $attributes->get('value'));
@endphp

<div class="space-y-1.5">
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-800">{{ $label }}</label>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except(['id', 'value'])->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-base shadow-sm placeholder:text-gray-400 disabled:bg-gray-100',
            'border-red-600' => $hasError,
            'border-gray-300 focus:border-brand-600' => ! $hasError,
        ]) }}
    >

    @if ($hint)
        <p id="{{ $hintId }}" class="text-sm text-gray-600">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $errorId }}" class="text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
