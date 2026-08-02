@php
    $class = match ($status) {
        'active', 'approved', 'received', 'done', 'completed', 'paid', 'trialing' => 'success',
        'pending', 'planning', 'todo', 'draft', 'unpaid', 'paused' => 'warning',
        'suspended', 'rejected', 'overdue', 'cancelled', 'inactive', 'expired' => 'danger',
        'blocked' => 'danger',
        'in_progress', 'submitted', 'under_review', 'assigned' => 'info',
        default => 'info',
    };
@endphp

<span class="status-badge status-{{ $class }}">
    <span aria-hidden="true"></span>
    {{ str_replace('_', ' ', $status) }}
</span>
