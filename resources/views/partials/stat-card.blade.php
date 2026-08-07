@php
    $type = $type ?? ($tone ?? 'primary');
    $subtitle = $subtitle ?? ($description ?? null);
    $trend = $trend ?? null;
@endphp

<article class="stat-card stat-card-{{ $type }}">
    <div>
        <span>{{ $label }}</span>
        <strong>{{ $value }}</strong>
        @if($subtitle)
            <small>{{ $subtitle }}</small>
        @endif
        @if($trend)
            <small class="stat-trend">{{ $trend }}</small>
        @endif
    </div>
    <i class="fa-solid {{ $icon }} tone-{{ $type }}" aria-hidden="true"></i>
</article>
