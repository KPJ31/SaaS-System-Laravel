@extends('layouts.app')

@section('title', 'Companies - Elevanix')

@section('content')
<div class="page-header">
    <div>
        <span>Super Admin</span>
        <h1>Companies</h1>
    </div>
</div>

<section class="content-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Company</th><th>Users</th><th>Projects</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td>{{ $company->name }}<small>{{ $company->email }}</small></td>
                        <td>{{ $company->users_count }}</td>
                        <td>{{ $company->projects_count }}</td>
                        <td>@include('partials.status-badge', ['status' => $company->status])</td>
                        <td>
                            <form method="POST" action="{{ route('super-admin.companies.status', [$company, $company->status === 'active' ? 'suspended' : 'active']) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary" type="submit">{{ $company->status === 'active' ? 'Suspend' : 'Activate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-cell">No companies yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $companies->links() }}
</section>
@endsection
