@php
    $variant = $variant ?? 'full';
    $tone = $tone ?? 'dark';
    $isIcon = $variant === 'icon';
    $textClass = $tone === 'light' ? 'text-white' : 'text-slate';
@endphp

<span class="brand-logo {{ $isIcon ? 'brand-logo-icon-only' : '' }}">
    <span class="brand-mark" aria-hidden="true">
        <span></span><span></span><span></span>
    </span>
    @unless($isIcon)
        <span class="brand-word {{ $textClass }}">
            <strong>Elevanix</strong>
            <small>ESSCMS</small>
        </span>
    @endunless
</span>
