@php
    $normalizedPriority = strtolower((string) ($priority ?? 'medium'));
    $class = match ($normalizedPriority) {
        'low' => 'neutral',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
        default => 'neutral',
    };
@endphp

<span class="priority-badge priority-{{ $class }}">
    {{ ucfirst($normalizedPriority) }}
</span>
