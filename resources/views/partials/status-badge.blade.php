@php
    $class = match ($status) {
        'active', 'approved', 'received', 'done', 'completed', 'paid', 'trialing' => 'success',
        'pending', 'planning', 'todo', 'in_progress', 'draft', 'unpaid' => 'warning',
        'suspended', 'rejected', 'overdue', 'cancelled', 'inactive', 'expired' => 'danger',
        default => 'info',
    };
@endphp

<span class="status-badge status-{{ $class }}">
    <span aria-hidden="true"></span>
    {{ str_replace('_', ' ', $status) }}
</span>
