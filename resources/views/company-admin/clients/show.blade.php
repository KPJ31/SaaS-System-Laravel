@extends('layouts.app')

@section('title', $client->name.' - Elevanix')

@section('content')
@php
    $canEditClient = auth()->user()->role === 'company_admin' || auth()->user()->can('clients.edit');
@endphp
@include('partials.page-header', [
    'eyebrow' => 'Client Details',
    'title' => $client->name,
    'description' => $client->company_name,
    'actions' => $canEditClient && auth()->user()->role === 'company_admin' ? new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.clients.edit', $client).'"><i class="fa-solid fa-pen"></i>Edit</a>') : null,
])
<div class="content-grid">
    <section class="content-card"><h2>Basic Information</h2><dl class="detail-list mt-3"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $client->status])</dd><dt>Email</dt><dd>{{ $client->email ?? '-' }}</dd><dt>Phone</dt><dd>{{ $client->phone ?? '-' }}</dd><dt>Address</dt><dd>{{ $client->address ?? '-' }}</dd><dt>Notes</dt><dd>{{ $client->notes ?? '-' }}</dd></dl></section>
    <section class="content-card"><h2>Summary</h2><dl class="detail-list mt-3"><dt>Projects</dt><dd>{{ $client->projects->count() }}</dd><dt>Requests</dt><dd>{{ $client->projectRequests->count() }}</dd><dt>Payments</dt><dd>{{ $client->payments->count() }}</dd><dt>Invoices</dt><dd>{{ $client->invoices->count() }}</dd></dl></section>
</div>
@endsection
