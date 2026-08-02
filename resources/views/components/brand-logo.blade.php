@props([
    'variant' => 'full',
    'tone' => 'dark',
])

@php
    $isIcon = $variant === 'icon';
    $textClass = $tone === 'light' ? 'text-white' : 'text-slate';
@endphp

<span {{ $attributes->merge(['class' => 'brand-logo '.($isIcon ? 'brand-logo-icon-only' : '')]) }}>
    <span class="brand-mark" aria-hidden="true">
        <span></span><span></span><span></span>
    </span>
    @unless($isIcon)
        <span class="brand-word {{ $textClass }}">
            <strong>Elevanix</strong>
            <small>Smart Software Management</small>
        </span>
    @endunless
</span>
