@extends('layouts.app')

@section('title', 'Companies - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Companies',
    'description' => 'Search, filter and manage every company workspace on the platform.',
])

<section class="content-card">
    <form class="row g-3 mb-3" method="GET">
        <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name or email"></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" type="date" name="from" value="{{ request('from') }}"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="to" value="{{ request('to') }}"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i> Filter</button></div>
    </form>
    <p class="text-muted small mb-3">{{ $companies->total() }} companies found.</p>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Company</th><th>Admin</th><th>Plan</th><th>Registered</th><th>Subscription</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($companies as $company)
                    @php($admin = $company->users->firstWhere('role', 'company_admin'))
                    <tr>
                        <td>{{ $company->name }}<small>{{ $company->email }}</small></td>
                        <td>{{ $admin?->name ?? 'Not assigned' }}<small>{{ $admin?->email }}</small></td>
                        <td>{{ $company->activeSubscription?->plan?->name ?? 'No plan' }}</td>
                        <td>{{ $company->created_at->format('M d, Y') }}</td>
                        <td>@if($company->activeSubscription) @include('partials.status-badge', ['status' => $company->activeSubscription->status]) @else <span class="text-muted">None</span> @endif</td>
                        <td>@include('partials.status-badge', ['status' => $company->status])</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.companies.show', $company) }}"><i class="fa-regular fa-eye"></i> View</a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.companies.edit', $company) }}"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-building', 'title' => 'No companies found', 'message' => 'Try changing the search or filters.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $companies->links() }}
</section>
@endsection
