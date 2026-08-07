@extends('layouts.app')

@section('title', 'Company Requests - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Company Requests',
    'description' => 'Review new company registration requests and administrator details.',
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Pending', 'value' => $summary['pending'], 'icon' => 'fa-clock', 'type' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Approved', 'value' => $summary['approved'], 'icon' => 'fa-circle-check', 'type' => 'green'])
    @include('partials.stat-card', ['label' => 'Rejected', 'value' => $summary['rejected'], 'icon' => 'fa-circle-xmark'])
</div>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Registration Queue</h2>
            <p>{{ $requests->total() }} requests match the current filters.</p>
        </div>
    </div>

    @include('partials.filter-bar', [
        'action' => route('super-admin.company-requests.index'),
        'resetUrl' => route('super-admin.company-requests.index'),
        'controls' => new Illuminate\Support\HtmlString(
            '<div><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="'.e(request('search')).'" placeholder="Company, applicant or email"></div>'.
            '<div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>'.
                collect($statuses)->map(fn ($status) => '<option value="'.e($status).'" '.(request('status') === $status ? 'selected' : '').'>'.e(ucfirst($status)).'</option>')->implode('').
            '</select></div>'.
            '<div><label class="form-label" for="from">From</label><input class="form-control" id="from" type="date" name="from" value="'.e(request('from')).'"></div>'.
            '<div><label class="form-label" for="to">To</label><input class="form-control" id="to" type="date" name="to" value="'.e(request('to')).'"></div>'
        ),
    ])

    <div class="table-responsive mt-3">
        <table class="table align-middle app-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Applicant</th>
                    <th>Contact</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td>{{ $request->company_name }}<small>{{ $request->company_email }}</small></td>
                        <td>{{ $request->admin_name }}<small>{{ $request->admin_email }}</small></td>
                        <td>{{ $request->company_phone }}<small>{{ $request->website ?: 'No website' }}</small></td>
                        <td>{{ $request->created_at->format('M d, Y') }}<small>{{ $request->created_at->diffForHumans() }}</small></td>
                        <td>@include('partials.status-badge', ['status' => $request->status])</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.company-requests.show', $request) }}" aria-label="Review {{ $request->company_name }}">
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i> Review
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-cell">
                            @include('partials.empty-state', ['icon' => 'fa-building-circle-check', 'title' => request()->query() ? 'No requests match your filters' : 'No company requests', 'message' => request()->query() ? 'Try changing the search, status or date range.' : 'Submitted company registrations will appear in this queue.'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</section>
@endsection
