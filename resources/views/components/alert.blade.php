@props([
    'type' => 'success',
    'message' => session('status'),
])

@if ($message)
    <div
        role="status"
        dusk="alert"
        {{ $attributes->class([
            'rounded-md border px-4 py-3 text-sm',
            'border-brand-100 bg-brand-50 text-brand-800' => $type === 'success',
            'border-red-200 bg-red-50 text-red-800' => $type === 'error',
        ]) }}
    >
        {{ $message }}
    </div>
@endif
