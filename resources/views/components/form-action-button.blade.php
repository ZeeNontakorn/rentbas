@props([
    'type' => 'button',
    'variant' => 'primary',
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center gap-2 rounded-lg px-8 py-2.5 transition shadow-sm font-semibold text-sm';
    $variantClasses = match ($variant) {
        'reset' => 'px-6 border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-none font-medium',
        default => 'bg-orange-500 text-white hover:bg-orange-600',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClasses . ' ' . $variantClasses]) }}>
    @if ($icon === 'check')
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    @endif
    {{ $slot }}
</button>
