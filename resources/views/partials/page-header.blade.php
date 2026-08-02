@php
    $eyebrow = $eyebrow ?? null;
    $title = $title ?? '';
    $description = $description ?? null;
@endphp

<div class="page-header">
    <div class="page-header-copy">
        @if($eyebrow)
            <span>{{ $eyebrow }}</span>
        @endif
        <h1>{{ $title }}</h1>
        @if($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="page-header-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
