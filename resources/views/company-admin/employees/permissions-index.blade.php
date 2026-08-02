@extends('layouts.app')

@section('title', 'Employee Permission Summary - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Admin',
    'title' => 'Employee Permission Summary',
    'description' => 'Review extra employee permissions and manage access quickly.',
])

<section class="content-card">
    <form class="row g-2 mb-3">
        <div class="col-md-3"><input class="form-control" name="employee" value="{{ request('employee') }}" placeholder="Employee name"></div>
        <div class="col-md-3"><input class="form-control" name="department" value="{{ request('department') }}" placeholder="Department"></div>
        <div class="col-md-3">
            <select class="form-select" name="permission">
                <option value="">Any permission</option>
                @foreach($permissionLabels as $permission => $label)
                    <option value="{{ $permission }}" @selected(request('permission') === $permission)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="extra_permissions">
                <option value="">All access states</option>
                <option value="yes" @selected(request('extra_permissions') === 'yes')>Has permissions</option>
                <option value="no" @selected(request('extra_permissions') === 'no')>No extra permissions</option>
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit"><i class="fa-solid fa-filter"></i></button></div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Employee</th><th>Job Title</th><th>Total</th><th>Modules</th><th>Last Update</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($employees as $employee)
                    @php
                        $modules = $employee->permissions->pluck('module')->unique()->values();
                        $latest = $latestUpdates->get($employee->id);
                    @endphp
                    <tr>
                        <td>{{ $employee->name }}<small>{{ $employee->email }}</small></td>
                        <td>{{ $employee->job_title ?? '-' }}</td>
                        <td>{{ $employee->permissions->count() }}</td>
                        <td>{{ $modules->isEmpty() ? '-' : $modules->map(fn ($module) => str_replace('-', ' ', $module))->implode(', ') }}</td>
                        <td>{{ $latest?->created_at?->format('Y-m-d H:i') ?? '-' }}<small>{{ $latest?->user?->name }}</small></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.employees.permissions.edit', $employee) }}"><i class="fa-solid fa-shield-halved"></i>Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $employees->links() }}
</section>
@endsection
