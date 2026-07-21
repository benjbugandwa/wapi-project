@props([
    'target' => null,
    'label' => __('Traitement en cours...'),
])

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm text-gray-500']) }}
    wire:loading
    @if ($target) wire:target="{{ $target }}" @endif
>
    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
    </svg>
    <span>{{ $label }}</span>
</span>
