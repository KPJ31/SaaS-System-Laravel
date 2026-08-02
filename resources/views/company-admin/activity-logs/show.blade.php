@extends('layouts.app')

@section('title', 'Activity Log - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Activity Log', 'title' => $auditLog->action])
<section class="content-card"><dl class="detail-list"><dt>User</dt><dd>{{ $auditLog->user?->name ?? 'System' }}</dd><dt>Description</dt><dd>{{ $auditLog->description ?? '-' }}</dd><dt>Date</dt><dd>{{ $auditLog->created_at->format('Y-m-d H:i') }}</dd><dt>Metadata</dt><dd><pre class="mb-0">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT) }}</pre></dd></dl></section>
@endsection
