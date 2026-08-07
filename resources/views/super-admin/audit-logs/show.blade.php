@extends('layouts.app')

@section('title', 'Audit Log Details - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Audit Logs', 'title' => str_replace('_', ' ', $log->action), 'description' => $log->description])
<div class="row g-3"><div class="col-lg-6"><section class="content-card"><h2>Details</h2><dl class="detail-list mt-3"><dt>Module</dt><dd>{{ $log->module ?? '-' }}</dd><dt>User</dt><dd>{{ $log->user?->name ?? 'System' }}</dd><dt>Company</dt><dd>{{ $log->company?->name ?? '-' }}</dd><dt>IP Address</dt><dd>{{ $log->ip_address ?? '-' }}</dd><dt>User Agent</dt><dd>{{ $log->user_agent ?? '-' }}</dd><dt>Created</dt><dd>{{ $log->created_at->format('M d, Y h:i A') }}</dd></dl></section></div><div class="col-lg-6"><section class="content-card"><h2>Values</h2><h3 class="h6 mt-3">Old Values</h3>@include('partials.audit-json', ['value' => $log->old_values])<h3 class="h6 mt-3">New Values</h3>@include('partials.audit-json', ['value' => $log->new_values])<h3 class="h6 mt-3">Metadata</h3>@include('partials.audit-json', ['value' => $log->metadata])</section></div></div>
@endsection
