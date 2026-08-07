@extends('layouts.app')

@section('title', ($employee->exists ? 'Edit' : 'Add').' Employee - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Employees', 'url' => route('company-admin.employees.index')],
        ['label' => $employee->exists ? 'Edit Employee' : 'Add Employee'],
    ],
    'eyebrow' => 'Employees',
    'title' => $employee->exists ? 'Edit Employee' : 'Add Employee',
    'description' => $employee->exists
        ? 'Update account and employment details for this team member.'
        : 'Create a team member account for your company workspace.',
])

<form class="content-card app-card form-page-container" method="POST" action="{{ $employee->exists ? route('company-admin.employees.update', $employee) : route('company-admin.employees.store') }}" data-loading-form>
    @csrf
    @if($employee->exists)
        @method('PUT')
    @endif

    <div class="content-card-header">
        <div>
            <h2>Account Information</h2>
            <p>Use a valid email address so account messages can reach the employee.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Name <span class="required-mark">*</span></label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $employee->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email <span class="required-mark">*</span></label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" value="{{ old('username', $employee->username) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="employee_code">Employee Code</label>
            <input class="form-control @error('employee_code') is-invalid @enderror" id="employee_code" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}">
            @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="status">Status <span class="required-mark">*</span></label>
            <select class="form-select" id="status" name="status" required>
                @foreach(['pending','active','suspended','inactive'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $employee->status ?: 'active') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="content-card-header mt-4">
        <div>
            <h2>Employment Details</h2>
            <p>Keep role, department, and joining information clear for reports.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="job_title">Job Title</label>
            <input class="form-control" id="job_title" name="job_title" value="{{ old('job_title', $employee->job_title) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="department">Department</label>
            <input class="form-control" id="department" name="department" value="{{ old('department', $employee->department) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="join_date">Joined Date</label>
            <input class="form-control" id="join_date" type="date" name="join_date" value="{{ old('join_date', optional($employee->join_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $employee->address) }}</textarea>
        </div>
    </div>

    @unless($employee->exists)
        <div class="content-card-header mt-4">
            <div>
                <h2>Password</h2>
                <p>Leave the password blank to generate a temporary one.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" type="password" name="password">
                <small class="helper-text">Leave blank to generate a temporary password.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <input class="form-control" id="password_confirmation" type="password" name="password_confirmation">
            </div>
        </div>
    @endunless

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="{{ route('company-admin.employees.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit" data-loading-text="Saving employee...">
            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
            Save employee
        </button>
    </div>
</form>
@endsection
