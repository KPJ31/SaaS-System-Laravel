@php
    $normalizedStatus = strtolower((string) $status);
    $label = $label ?? match ($normalizedStatus) {
        'todo' => 'To do',
        'in_progress' => 'In progress',
        'under_review', 'pending_review' => 'Under review',
        'need_clarification' => 'Needs clarification',
        'converted_to_project' => 'Converted to project',
        'not_checked_in' => 'Not checked in',
        'early_departure' => 'Early departure',
        default => str_replace('_', ' ', $normalizedStatus),
    };
    $class = match ($normalizedStatus) {
        'active', 'approved', 'verified', 'received', 'done', 'completed', 'paid', 'trialing', 'present', 'converted_to_project' => 'success',
        'pending', 'under_review', 'pending_review', 'draft', 'unpaid', 'late', 'not_checked_in', 'need_clarification', 'payment_submitted' => 'warning',
        'suspended', 'rejected', 'overdue', 'expired', 'blocked', 'absent', 'failed' => 'danger',
        'submitted', 'running', 'checked_in', 'processing', 'early_departure', 'on_leave', 'holiday', 'weekend', 'assigned' => 'info',
        'in_progress', 'planning', 'half_day', 'open' => 'primary',
        'todo', 'stopped', 'archived', 'cancelled', 'inactive', 'paused', 'on_hold', 'refunded' => 'neutral',
        default => 'info',
    };
@endphp

<span class="status-badge status-{{ $class }}">
    <span aria-hidden="true"></span>
    {{ $label }}
</span>
