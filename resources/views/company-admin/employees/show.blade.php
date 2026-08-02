@extends('layouts.app')

@section('title', $employee->name.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Employee Details',
    'title' => $employee->name,
    'description' => $employee->email,
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.employees.permissions.edit', $employee).'"><i class="fa-solid fa-shield-halved"></i>Manage Permissions</a><a class="btn btn-primary" href="'.route('company-admin.employees.edit', $employee).'"><i class="fa-solid fa-pen"></i>Edit</a>'),
])
<div class="content-grid">
    <section class="content-card"><h2>Profile</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $employee->status])</dd><dt>Job Title</dt><dd>{{ $employee->job_title ?? '-' }}</dd><dt>Department</dt><dd>{{ $employee->department ?? '-' }}</dd><dt>Phone</dt><dd>{{ $employee->phone ?? '-' }}</dd><dt>Joined</dt><dd>{{ $employee->join_date?->format('Y-m-d') ?? '-' }}</dd><dt>Work Hours</dt><dd>{{ number_format($employee->workSessions->sum('duration_minutes') / 60, 1) }}</dd></dl></section>
    <section class="content-card"><h2>Actions</h2><div class="d-flex flex-wrap gap-2 mt-3">@foreach(['active','suspended','inactive'] as $status)<form method="POST" action="{{ route('company-admin.employees.status', [$employee, $status]) }}" data-confirm="Change employee status?">@csrf<button class="btn btn-outline-primary" type="submit">{{ ucfirst($status) }}</button></form>@endforeach<form method="POST" action="{{ route('company-admin.employees.password-reset', $employee) }}">@csrf<button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-key"></i>Password reset</button></form></div></section>
</div>
@endsection
