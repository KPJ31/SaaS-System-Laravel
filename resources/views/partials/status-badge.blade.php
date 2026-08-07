@php
    $class = match ($status) {
        'active', 'approved', 'verified', 'received', 'done', 'completed', 'paid', 'trialing', 'present' => 'success',
        'pending', 'planning', 'todo', 'draft', 'unpaid', 'paused', 'late', 'not_checked_in' => 'warning',
        'suspended', 'rejected', 'overdue', 'expired' => 'danger',
        'blocked', 'absent' => 'danger',
        'submitted', 'under_review', 'running', 'checked_in', 'processing', 'early_departure' => 'info',
        'in_progress', 'assigned', 'half_day' => 'primary',
        'stopped', 'archived', 'cancelled', 'inactive', 'draft' => 'neutral',
        'on_leave', 'holiday', 'weekend' => 'info',
        default => 'info',
    };
@endphp

<span class="status-badge status-{{ $class }}">
    <span aria-hidden="true"></span>
    {{ str_replace('_', ' ', $status) }}
</span>
