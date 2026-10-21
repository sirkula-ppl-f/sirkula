@props(['variant' => 'primary'])

<button {{ $attributes->merge(['type' => 'submit'])->class([
    'inline-flex w-full items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60',
    'bg-brand-600 text-white hover:bg-brand-700' => $variant === 'primary',
    'border border-gray-300 bg-white text-gray-800 hover:bg-gray-50' => $variant === 'secondary',
]) }}>
    {{ $slot }}
</button>
