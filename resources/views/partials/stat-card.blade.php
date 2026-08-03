@php
    $tone = $tone ?? 'primary';
@endphp

<article class="stat-card">
    <div>
        <span>{{ $label }}</span>
        <strong>{{ $value }}</strong>
        @isset($description)
            <small>{{ $description }}</small>
        @endisset
    </div>
    <i class="fa-solid {{ $icon }} tone-{{ $tone }}" aria-hidden="true"></i>
</article>
