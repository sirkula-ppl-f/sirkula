@props([
    'name',
    'label',
    'hint' => null,
    'rows' => 4,
])

@php
    $id = $attributes->get('id', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? $hintId : null, $hasError ? $errorId : null])->filter()->implode(' ');
    $value = old($name, $attributes->get('value'));
@endphp

<div class="space-y-1.5">
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-800">{{ $label }}</label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except(['id', 'value'])->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-base shadow-sm placeholder:text-gray-400 disabled:bg-gray-100',
            'border-red-600' => $hasError,
            'border-gray-300 focus:border-brand-600' => ! $hasError,
        ]) }}
    >{{ $value }}</textarea>

    @if ($hint)
        <p id="{{ $hintId }}" class="text-sm text-gray-600">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $errorId }}" class="text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
