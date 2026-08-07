@extends('layouts.app')

@section('title', 'Activity Log - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Activity Log', 'title' => str_replace('_', ' ', ucfirst($auditLog->action)), 'description' => $auditLog->description])
<div class="row g-3">
    <div class="col-lg-6"><section class="content-card"><h2>Details</h2><dl class="detail-list mt-3"><dt>Module</dt><dd>{{ $auditLog->module ?? '-' }}</dd><dt>User</dt><dd>{{ $auditLog->user?->name ?? 'System' }}</dd><dt>Description</dt><dd>{{ $auditLog->description ?? '-' }}</dd><dt>Date</dt><dd>{{ $auditLog->created_at->format('Y-m-d H:i') }}</dd></dl></section></div>
    <div class="col-lg-6"><section class="content-card"><h2>Metadata</h2>@include('partials.audit-json', ['value' => $auditLog->metadata])</section></div>
</div>
@endsection
