@extends('layouts.app')

@section('title', 'Company Requests - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Company Registration Requests',
    'description' => 'Review incoming software company registration requests and their administrator details.',
])

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Registration Queue</h2>
            <p>Requests are sorted by newest submission first.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Company</th><th>Admin</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td>{{ $request->company_name }}<small>{{ $request->company_email }}</small></td>
                        <td>{{ $request->admin_name }}<small>{{ $request->admin_email }}</small></td>
                        <td>{{ $request->created_at->format('M d, Y') }}</td>
                        <td>@include('partials.status-badge', ['status' => $request->status])</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.company-requests.show', $request) }}" aria-label="Review {{ $request->company_name }}">
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i>Review
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-building-circle-check', 'title' => 'No company requests', 'message' => 'Submitted registrations will appear in this queue.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</section>
@endsection
