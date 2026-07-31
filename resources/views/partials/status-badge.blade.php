@php
    $class = match ($status) {
        'active', 'approved', 'received', 'done' => 'success',
        'pending', 'planning', 'todo' => 'warning',
        'suspended', 'rejected', 'overdue' => 'danger',
        default => 'info',
    };
@endphp

<span class="status-badge status-{{ $class }}">{{ str_replace('_', ' ', $status) }}</span>
