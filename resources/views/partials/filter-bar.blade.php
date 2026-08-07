@php
    $method = $method ?? 'GET';
    $action = $action ?? url()->current();
    $resetUrl = $resetUrl ?? $action;
    $controls = $controls ?? '';
@endphp

<form class="app-filter-bar" method="{{ $method }}" action="{{ $action }}">
    <div class="app-filter-controls">
        {!! $controls !!}
    </div>
    <div class="app-filter-actions">
        @isset($actions)
            {{ $actions }}
        @else
            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filter
            </button>
            <a class="btn btn-outline-secondary" href="{{ $resetUrl }}">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Reset
            </a>
        @endisset
    </div>
</form>
