@extends('layouts.app')

@section('title', 'Leave Requests - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Employee',
    'title' => 'Leave Requests',
    'description' => 'Create and track leave approvals.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('employee.leave-requests.create').'"><i class="fa-solid fa-calendar-plus"></i>New Request</a>'),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Pending', 'value' => $summary['pending'], 'icon' => 'fa-hourglass-half', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Approved', 'value' => $summary['approved'], 'icon' => 'fa-circle-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Approved Days', 'value' => $summary['used_days'], 'icon' => 'fa-calendar-days', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Upcoming', 'value' => $summary['upcoming'], 'icon' => 'fa-calendar-check'])
</div>

<section class="content-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th>Status</th><th>Reviewed</th><th>Review Note</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($leaves as $leave)
                    <tr>
                        <td>{{ ucfirst($leave->leave_type) }}</td>
                        <td>{{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}</td>
                        <td>{{ $leave->total_days }}</td>
                        <td>{{ $leave->reason }}</td>
                        <td>@include('partials.status-badge', ['status' => $leave->status])</td>
                        <td>{{ $leave->reviewed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $leave->review_note ?? '-' }}</td>
                        <td class="text-end">
                            @if($leave->status === 'pending')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.leave-requests.edit', $leave) }}"><i class="fa-solid fa-pen"></i>Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('employee.leave-requests.cancel', $leave) }}" data-confirm="Cancel this leave request?">@csrf<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-xmark"></i>Cancel</button></form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-calendar-check', 'title' => 'No leave requests', 'message' => 'Your leave requests appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $leaves->links() }}
</section>
@endsection
