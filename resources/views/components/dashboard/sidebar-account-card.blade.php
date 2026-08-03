@props([
    'image' => null,
    'initials' => 'U',
    'primaryText' => 'User',
    'secondaryText' => '',
    'tertiaryText' => null,
    'link' => null,
    'title' => null,
])

@php
    $cardTitle = $title ?? $primaryText;
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($initials ?: $primaryText ?: 'U', 0, 1));
    $content = trim($primaryText.' '.$secondaryText);
@endphp

@if($link)
    <a {{ $attributes->merge(['class' => 'sidebar-account-card text-decoration-none']) }} href="{{ $link }}" title="{{ $cardTitle }}" aria-label="{{ $content }}">
        @if($image)
            <img class="sidebar-account-avatar" src="{{ asset('storage/'.$image) }}" alt="{{ $primaryText }}">
        @else
            <span class="sidebar-account-avatar" aria-hidden="true">{{ $initial }}</span>
        @endif
        <span class="sidebar-account-details">
            <strong class="sidebar-account-name text-truncate" title="{{ $primaryText }}">{{ $primaryText }}</strong>
            <small class="sidebar-account-role text-truncate" title="{{ $secondaryText }}">{{ $secondaryText }}</small>
            @if($tertiaryText)
                <small class="sidebar-account-meta text-truncate" title="{{ $tertiaryText }}">{{ $tertiaryText }}</small>
            @endif
        </span>
    </a>
@else
    <div {{ $attributes->merge(['class' => 'sidebar-account-card']) }} title="{{ $cardTitle }}">
        @if($image)
            <img class="sidebar-account-avatar" src="{{ asset('storage/'.$image) }}" alt="{{ $primaryText }}">
        @else
            <span class="sidebar-account-avatar" aria-hidden="true">{{ $initial }}</span>
        @endif
        <span class="sidebar-account-details">
            <strong class="sidebar-account-name text-truncate" title="{{ $primaryText }}">{{ $primaryText }}</strong>
            <small class="sidebar-account-role text-truncate" title="{{ $secondaryText }}">{{ $secondaryText }}</small>
            @if($tertiaryText)
                <small class="sidebar-account-meta text-truncate" title="{{ $tertiaryText }}">{{ $tertiaryText }}</small>
            @endif
        </span>
    </div>
@endif
