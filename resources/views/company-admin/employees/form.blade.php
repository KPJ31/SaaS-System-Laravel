@extends('layouts.app')

@section('title', ($employee->exists ? 'Edit' : 'Add').' Employee - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Employees', 'title' => $employee->exists ? 'Edit Employee' : 'Add Employee'])

<form class="content-card" method="POST" action="{{ $employee->exists ? route('company-admin.employees.update', $employee) : route('company-admin.employees.store') }}" data-loading-form>
    @csrf
    @if($employee->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name <span class="required-mark">*</span></label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $employee->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Email <span class="required-mark">*</span></label><input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $employee->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Username</label><input class="form-control" name="username" value="{{ old('username', $employee->username) }}"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $employee->phone) }}"></div>
        <div class="col-md-4"><label class="form-label">Status <span class="required-mark">*</span></label><select class="form-select" name="status" required>@foreach(['pending','active','suspended','inactive'] as $status)<option value="{{ $status }}" @selected(old('status', $employee->status ?: 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Job Title</label><input class="form-control" name="job_title" value="{{ old('job_title', $employee->job_title) }}"></div>
        <div class="col-md-4"><label class="form-label">Department</label><input class="form-control" name="department" value="{{ old('department', $employee->department) }}"></div>
        <div class="col-md-4"><label class="form-label">Joined Date</label><input class="form-control" type="date" name="join_date" value="{{ old('join_date', optional($employee->join_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">{{ old('address', $employee->address) }}</textarea></div>
        @unless($employee->exists)
            <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password"><small class="helper-text">Leave blank to generate a temporary password.</small></div>
            <div class="col-md-6"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation"></div>
        @endunless
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save employee</button><a class="btn btn-outline-primary" href="{{ route('company-admin.employees.index') }}">Cancel</a></div>
</form>
@endsection
