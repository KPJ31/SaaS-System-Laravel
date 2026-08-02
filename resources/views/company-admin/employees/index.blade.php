@extends('layouts.app')

@section('title', 'Employees - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Admin',
    'title' => 'Employees',
    'description' => 'Search, filter and manage employees who belong to your company.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.employees.create').'"><i class="fa-solid fa-user-plus"></i>Add employee</a>'),
])

<section class="content-card">
    <form class="row g-2 mb-3">
        <div class="col-md-5"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search employees"></div>
        <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['pending','active','suspended','inactive'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><input class="form-control" name="job_title" value="{{ request('job_title') }}" placeholder="Job title"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit"><i class="fa-solid fa-filter"></i></button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Job Title</th><th>Projects</th><th>Tasks</th><th>Hours</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employee->name }}<small>{{ $employee->department ?? 'No department' }}</small></td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->job_title ?? '-' }}</td>
                        <td>{{ $employee->projects_count }}</td>
                        <td>{{ $employee->assigned_tasks_count }}</td>
                        <td>{{ number_format(($employee->work_minutes ?? 0) / 60, 1) }}</td>
                        <td>@include('partials.status-badge', ['status' => $employee->status])</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.show', $employee) }}"><i class="fa-solid fa-eye"></i></a><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.edit', $employee) }}"><i class="fa-solid fa-pen"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-users', 'title' => 'No employees found', 'message' => 'Employees added to this company will appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $employees->links() }}
</section>
@endsection
