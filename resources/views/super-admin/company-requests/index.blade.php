@extends('layouts.app')

@section('title', 'Company Requests - Elevanix')

@section('content')
<div class="page-header">
    <div>
        <span>Super Admin</span>
        <h1>Company Registration Requests</h1>
    </div>
</div>

<section class="content-card">
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
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.company-requests.show', $request) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-cell">No company registration requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</section>
@endsection
