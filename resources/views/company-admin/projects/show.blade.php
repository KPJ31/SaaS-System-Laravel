@extends('layouts.app')

@section('title', $project->name.' - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Project Details', 'title' => $project->name, 'description' => $project->client?->name ?? 'Internal project', 'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.projects.edit', $project).'"><i class="fa-solid fa-pen"></i>Edit</a>')])
<div class="content-grid">
    <section class="content-card"><h2>Overview</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $project->status])</dd><dt>Priority</dt><dd>{{ ucfirst($project->priority ?? 'medium') }}</dd><dt>Budget</dt><dd>${{ number_format($project->budget ?? 0, 2) }}</dd><dt>Progress</dt><dd><div class="progress"><div class="progress-bar" style="width: {{ $project->progress }}%"></div></div>{{ $project->progress }}%</dd><dt>Due</dt><dd>{{ $project->due_date?->format('Y-m-d') ?? '-' }}</dd></dl></section>
    <section class="content-card"><h2>Assign Employee</h2><form class="d-flex gap-2 mt-3" method="POST" action="{{ route('company-admin.projects.assign', $project) }}">@csrf<select class="form-select" name="user_id" required><option value="">Choose employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select><button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus"></i></button></form><div class="activity-list mt-3">@forelse($project->users as $employee)<div><strong>{{ $employee->name }}</strong><span>{{ $employee->job_title }}</span></div>@empty<div>No assigned employees.</div>@endforelse</div></section>
</div>
@endsection
