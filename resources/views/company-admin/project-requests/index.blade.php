@extends('layouts.app')

@section('title', 'Project Requests - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'Project Requests', 'description' => 'Review client requests and convert approved requests into projects.'])
<section class="content-card">
    <form class="row g-2 mb-3"><div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search requests"></div><div class="col-md-4"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['draft','pending','under_review','approved','rejected','payment_requested','payment_confirmed','converted_to_project','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div></form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Request</th><th>Client</th><th>Service</th><th>Budget</th><th>Deadline</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($projectRequests as $requestItem)<tr><td>{{ $requestItem->title }}<small>#{{ $requestItem->id }}</small></td><td>{{ $requestItem->client?->name ?? '-' }}</td><td>{{ $requestItem->service_type ?? '-' }}</td><td>${{ number_format($requestItem->estimated_budget ?? 0, 2) }}</td><td>{{ $requestItem->expected_end_date?->format('Y-m-d') ?? '-' }}</td><td>@include('partials.status-badge', ['status' => $requestItem->status])</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.project-requests.show', $requestItem) }}"><i class="fa-solid fa-eye"></i></a></td></tr>@empty<tr><td colspan="7" class="empty-cell">No project requests found.</td></tr>@endforelse</tbody></table></div>
    {{ $projectRequests->links() }}
</section>
@endsection
