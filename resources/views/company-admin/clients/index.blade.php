@extends('layouts.app')

@section('title', 'Clients - Elevanix')

@section('content')
@php
    $clientRoutePrefix = auth()->user()->role === 'employee' ? 'employee.clients' : 'company-admin.clients';
    $canCreateClients = auth()->user()->role === 'company_admin' || auth()->user()->can('clients.create');
    $canEditClients = auth()->user()->role === 'company_admin' || auth()->user()->can('clients.edit');
@endphp
@include('partials.page-header', [
    'eyebrow' => auth()->user()->role === 'employee' ? 'Employee Access' : 'Company Admin',
    'title' => 'Clients',
    'description' => 'Manage client information and related business activity.',
    'actions' => $canCreateClients && auth()->user()->role === 'company_admin' ? new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.clients.create').'"><i class="fa-solid fa-plus"></i>Add client</a>') : null,
])
<section class="content-card">
    <form class="row g-2 mb-3">
        <div class="col-lg-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name, organization, email or phone"></div>
        <div class="col-lg-3"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['active','inactive','blocked'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-lg-3 d-flex gap-2"><button class="btn btn-outline-primary flex-fill" type="submit"><i class="fa-solid fa-filter"></i>Filter</button><a class="btn btn-outline-secondary" href="{{ route($clientRoutePrefix.'.index') }}" aria-label="Clear filters"><i class="fa-solid fa-rotate-left"></i></a></div>
    </form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Client</th><th>Contact</th><th>Projects</th><th>Active</th><th>Requests</th><th>Unpaid Invoices</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($clients as $client)<tr><td><a class="fw-semibold" href="{{ route($clientRoutePrefix.'.show', $client) }}">{{ $client->name }}</a><small>{{ $client->company_name ?? 'No organization' }}</small></td><td>{{ $client->email ?? '-' }}<small>{{ $client->phone ?? '-' }}</small></td><td>{{ $client->projects_count }}</td><td>{{ $client->active_projects_count }}</td><td>{{ $client->project_requests_count }}</td><td>{{ $client->unpaid_invoices_count }}</td><td>@include('partials.status-badge', ['status' => $client->status])</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route($clientRoutePrefix.'.show', $client) }}" aria-label="View {{ $client->name }}"><i class="fa-solid fa-eye"></i></a>@if($canEditClients && auth()->user()->role === 'company_admin')<a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.clients.edit', $client) }}" aria-label="Edit {{ $client->name }}"><i class="fa-solid fa-pen"></i></a>@endif</td></tr>@empty<tr><td colspan="8" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-handshake', 'title' => 'No clients found', 'message' => 'Client records for this company will appear here.'])</td></tr>@endforelse</tbody></table></div>
    {{ $clients->links() }}
</section>
@endsection
