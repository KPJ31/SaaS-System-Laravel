@php
    $class = match ($status) {
        'active', 'approved', 'verified', 'received', 'done', 'completed', 'paid', 'trialing', 'present' => 'success',
        'pending', 'planning', 'todo', 'draft', 'unpaid', 'paused', 'late', 'not_checked_in' => 'warning',
        'suspended', 'rejected', 'overdue', 'cancelled', 'inactive', 'expired' => 'danger',
        'blocked', 'absent' => 'danger',
        'in_progress', 'submitted', 'under_review', 'assigned', 'half_day', 'early_departure' => 'info',
        'on_leave', 'holiday', 'weekend' => 'info',
        default => 'info',
    };
@endphp

<span class="status-badge status-{{ $class }}">
    <span aria-hidden="true"></span>
    {{ str_replace('_', ' ', $status) }}
</span>
