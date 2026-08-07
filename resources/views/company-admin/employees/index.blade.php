@extends('layouts.app')

@section('title', 'Employees - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Admin',
    'title' => 'Employees',
    'description' => 'Manage team members, workspace access and employee information.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.employees.create').'"><i class="fa-solid fa-user-plus"></i>Add employee</a>'),
])

<section class="content-card">
    <form class="row g-2 mb-3">
        <div class="col-lg-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone or code"></div>
        <div class="col-lg-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['pending','active','suspended','inactive'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-lg-2"><input class="form-control" name="job_title" value="{{ request('job_title') }}" placeholder="Job title"></div>
        <div class="col-lg-2"><input class="form-control" name="department" value="{{ request('department') }}" placeholder="Department"></div>
        <div class="col-lg-2 d-flex gap-2">
            <button class="btn btn-outline-primary flex-fill" type="submit"><i class="fa-solid fa-filter"></i>Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('company-admin.employees.index') }}" aria-label="Clear filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Employee</th><th>Employee Code</th><th>Email</th><th>Projects</th><th>Open Tasks</th><th>Overdue</th><th>Hours</th><th>Status</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td>
                            <a class="fw-semibold" href="{{ route('company-admin.employees.show', $employee) }}">{{ $employee->name }}</a>
                            <small>{{ $employee->job_title ?? $employee->department ?? 'No role details' }}</small>
                        </td>
                        <td>{{ $employee->employee_code ?? '-' }}</td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->projects_count }}</td>
                        <td>{{ $employee->open_tasks_count }}</td>
                        <td>{{ $employee->overdue_tasks_count }}</td>
                        <td>{{ number_format(($employee->work_minutes ?? 0) / 60, 1) }}</td>
                        <td>@include('partials.status-badge', ['status' => $employee->status])</td>
                        <td>{{ $employee->join_date?->format('Y-m-d') ?? $employee->created_at->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.show', $employee) }}" aria-label="View {{ $employee->name }}"><i class="fa-solid fa-eye"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.edit', $employee) }}" aria-label="Edit {{ $employee->name }}"><i class="fa-solid fa-pen"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.permissions.edit', $employee) }}" aria-label="Manage permissions for {{ $employee->name }}"><i class="fa-solid fa-shield-halved"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No employees found', 'message' => 'Employees added to this company will appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $employees->links() }}
</section>
@endsection
