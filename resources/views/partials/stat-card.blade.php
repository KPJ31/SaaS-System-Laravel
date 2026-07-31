@php
    $tone = $tone ?? 'purple';
@endphp

<article class="stat-card">
    <div>
        <span>{{ $label }}</span>
        <strong>{{ $value }}</strong>
    </div>
    <i class="fa-solid {{ $icon }} tone-{{ $tone }}"></i>
</article>
