@extends('layouts.app')

@section('title', 'Activity Logs - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'Activity Logs', 'description' => 'Company-scoped audit history for important actions.'])
<section class="content-card">
    <form class="row g-2 mb-3"><div class="col-md-6"><label class="visually-hidden" for="action">Filter by action</label><input class="form-control" id="action" name="action" value="{{ request('action') }}" placeholder="Filter by action"></div><div class="col-md-2"><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div></form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Action</th><th>Module</th><th>User</th><th>Description</th><th>Date</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ str_replace('_', ' ', ucfirst($log->action)) }}</td><td>{{ $log->module ?? '-' }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td>{{ Str::limit($log->description, 100) }}</td><td>{{ $log->created_at->format('Y-m-d H:i') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.activity-logs.show', $log) }}" title="View activity log"><i class="fa-solid fa-eye"></i></a></td></tr>@empty<tr><td colspan="6" class="empty-cell">No activity logs found.</td></tr>@endforelse</tbody></table></div>
    {{ $logs->links() }}
</section>
@endsection
