@extends('layouts.app')

@section('title', 'Project Requests - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Admin',
    'title' => 'Project Requests',
    'description' => 'Review incoming client requirements and convert approved requests into managed projects.',
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Pending Review', 'value' => $summary['pending'], 'icon' => 'fa-hourglass-half', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Under Review', 'value' => $summary['under_review'], 'icon' => 'fa-magnifying-glass-chart', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Approved', 'value' => $summary['approved'], 'icon' => 'fa-circle-check', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Converted', 'value' => $summary['converted'], 'icon' => 'fa-diagram-project', 'tone' => 'primary'])
</div>

<section class="content-card">
    <form class="row g-2 mb-3">
        <div class="col-lg-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search request, service or client"></div>
        <div class="col-lg-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['draft','pending','under_review','approved','rejected','payment_requested','payment_confirmed','converted_to_project','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-lg-2"><select class="form-select" name="client_id"><option value="">All clients</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-lg-2"><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Submitted from"></div>
        <div class="col-lg-2"><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Submitted to"></div>
        <div class="col-lg-1 d-flex gap-2"><button class="btn btn-outline-primary flex-fill" type="submit" aria-label="Filter project requests"><i class="fa-solid fa-filter"></i></button><a class="btn btn-outline-secondary" href="{{ route('company-admin.project-requests.index') }}" aria-label="Clear filters"><i class="fa-solid fa-rotate-left"></i></a></div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Request</th><th>Client</th><th>Submitted</th><th>Deadline</th><th>Status</th><th>Project</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($projectRequests as $requestItem)
                    <tr>
                        <td><a class="fw-semibold" href="{{ route('company-admin.project-requests.show', $requestItem) }}">{{ $requestItem->title }}</a><small>{{ $requestItem->service_type ?? 'General request' }}</small></td>
                        <td>{{ $requestItem->client?->name ?? '-' }}<small>{{ $requestItem->client?->company_name }}</small></td>
                        <td>{{ $requestItem->created_at->format('Y-m-d') }}</td>
                        <td>{{ $requestItem->expected_end_date?->format('Y-m-d') ?? '-' }}</td>
                        <td>@include('partials.status-badge', ['status' => $requestItem->status])</td>
                        <td>
                            @if($requestItem->convertedProject)
                                <a href="{{ route('company-admin.projects.show', $requestItem->convertedProject) }}">{{ $requestItem->convertedProject->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.project-requests.show', $requestItem) }}" aria-label="Review {{ $requestItem->title }}"><i class="fa-solid fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-inbox', 'title' => 'No project requests found', 'message' => 'Incoming project requests for this company will appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $projectRequests->links() }}
</section>
@endsection
