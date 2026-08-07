@php
    $eyebrow = $eyebrow ?? null;
    $title = $title ?? '';
    $description = $description ?? null;
    $breadcrumbs = $breadcrumbs ?? [];
@endphp

<div class="page-header">
    <div class="page-header-copy">
        @if($breadcrumbs)
            <nav class="app-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    @foreach($breadcrumbs as $breadcrumb)
                        <li>
                            @if(! empty($breadcrumb['url']) && ! $loop->last)
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                            @else
                                <span @if($loop->last) aria-current="page" @endif>{{ $breadcrumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        @if($eyebrow)
            <span>{{ $eyebrow }}</span>
        @endif
        <div class="page-title-row">
            <h1>{{ $title }}</h1>
            @isset($badge)
                <div class="page-title-badge">{{ $badge }}</div>
            @endisset
        </div>
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
