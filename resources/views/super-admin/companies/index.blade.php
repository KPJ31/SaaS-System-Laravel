@extends('layouts.app')

@section('title', 'Companies - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Companies',
    'description' => 'Search, filter and manage every company workspace on the platform.',
])

<section class="content-card">
    @include('partials.filter-bar', [
        'action' => route('super-admin.companies.index'),
        'resetUrl' => route('super-admin.companies.index'),
        'controls' => new Illuminate\Support\HtmlString(
            '<div><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="'.e(request('search')).'" placeholder="Company or admin"></div>'.
            '<div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>'.
                collect($statuses)->map(fn ($status) => '<option value="'.e($status).'" '.(request('status') === $status ? 'selected' : '').'>'.e(ucfirst($status)).'</option>')->implode('').
            '</select></div>'.
            '<div><label class="form-label" for="from">From</label><input class="form-control" id="from" type="date" name="from" value="'.e(request('from')).'"></div>'.
            '<div><label class="form-label" for="to">To</label><input class="form-control" id="to" type="date" name="to" value="'.e(request('to')).'"></div>'
        ),
    ])
    <p class="text-muted small mb-3">{{ $companies->total() }} companies found.</p>
    <div class="table-responsive">
        <table class="table align-middle app-table">
            <thead><tr><th>Company</th><th>Admin</th><th>Users</th><th>Projects</th><th>Subscription</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
                @forelse($companies as $company)
                    @php($admin = $company->users->first())
                    <tr>
                        <td>{{ $company->name }}<small>{{ $company->email }}</small></td>
                        <td>{{ $admin?->name ?? 'Not assigned' }}<small>{{ $admin?->email }}</small></td>
                        <td>{{ $company->users_count }}</td>
                        <td>{{ $company->projects_count }}</td>
                        <td>{{ $company->activeSubscription?->plan?->name ?? 'No plan' }}<small>@if($company->activeSubscription) @include('partials.status-badge', ['status' => $company->activeSubscription->status]) @else No subscription @endif</small></td>
                        <td>@include('partials.status-badge', ['status' => $company->status])</td>
                        <td>{{ $company->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.companies.show', $company) }}"><i class="fa-regular fa-eye"></i> View</a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.companies.edit', $company) }}"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-building', 'title' => 'No companies found', 'message' => 'Try changing the search or filters.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $companies->links() }}
</section>
@endsection
