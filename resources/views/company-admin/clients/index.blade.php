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
    'description' => 'Client records for your company.',
    'actions' => $canCreateClients && auth()->user()->role === 'company_admin' ? new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.clients.create').'"><i class="fa-solid fa-plus"></i>Add client</a>') : null,
])
<section class="content-card">
    <form class="row g-2 mb-3"><div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search clients"></div><div class="col-md-4"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['active','inactive','blocked'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div></form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Organization</th><th>Email</th><th>Projects</th><th>Requests</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($clients as $client)<tr><td>{{ $client->name }}<small>{{ $client->phone }}</small></td><td>{{ $client->company_name ?? '-' }}</td><td>{{ $client->email ?? '-' }}</td><td>{{ $client->projects_count }}</td><td>{{ $client->project_requests_count }}</td><td>@include('partials.status-badge', ['status' => $client->status])</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route($clientRoutePrefix.'.show', $client) }}"><i class="fa-solid fa-eye"></i></a>@if($canEditClients && auth()->user()->role === 'company_admin')<a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.clients.edit', $client) }}"><i class="fa-solid fa-pen"></i></a>@endif</td></tr>@empty<tr><td colspan="7" class="empty-cell">No clients found.</td></tr>@endforelse</tbody></table></div>
    {{ $clients->links() }}
</section>
@endsection
