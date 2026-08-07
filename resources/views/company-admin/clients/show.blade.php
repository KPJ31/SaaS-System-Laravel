@extends('layouts.app')

@section('title', $client->name.' - Elevanix')

@section('content')
@php
    $canEditClient = auth()->user()->role === 'company_admin' || auth()->user()->can('clients.edit');
    $currency = auth()->user()->company?->setting?->currency ?? 'USD';
@endphp
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Clients', 'url' => route(auth()->user()->role === 'employee' ? 'employee.clients.index' : 'company-admin.clients.index')],
        ['label' => $client->name],
    ],
    'eyebrow' => 'Client Overview',
    'title' => $client->name,
    'description' => $client->company_name ?? 'Client record',
    'actions' => $canEditClient && auth()->user()->role === 'company_admin' ? new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.clients.edit', $client).'"><i class="fa-solid fa-pen"></i>Edit</a>') : null,
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Projects', 'value' => $client->projects_count, 'icon' => 'fa-diagram-project', 'tone' => 'blue'])
    @include('partials.stat-card', ['label' => 'Active Projects', 'value' => $client->active_projects_count, 'icon' => 'fa-bolt', 'tone' => 'green'])
    @include('partials.stat-card', ['label' => 'Project Requests', 'value' => $client->project_requests_count, 'icon' => 'fa-inbox', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Unpaid Invoices', 'value' => $client->unpaid_invoices_count, 'icon' => 'fa-file-invoice-dollar', 'tone' => 'danger'])
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Client Information</h2><p>Primary contact and organization information.</p></div></div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $client->status])</dd>
            <dt>Organization</dt><dd>{{ $client->company_name ?? '-' }}</dd>
            <dt>Email</dt><dd>{{ $client->email ?? '-' }}</dd>
            <dt>Phone</dt><dd>{{ $client->phone ?? '-' }}</dd>
            <dt>Address</dt><dd>{{ $client->address ?? '-' }}</dd>
            <dt>Notes</dt><dd>{{ $client->notes ?? '-' }}</dd>
            <dt>Created</dt><dd>{{ $client->created_at->format('Y-m-d') }}</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Client Actions</h2><p>Useful workflows connected to this client.</p></div></div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a class="btn btn-outline-primary" href="{{ route('company-admin.projects.index', ['client_id' => $client->id]) }}"><i class="fa-solid fa-diagram-project"></i>Projects</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.project-requests.index', ['client_id' => $client->id]) }}"><i class="fa-solid fa-inbox"></i>Requests</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.invoices.index', ['client_id' => $client->id]) }}"><i class="fa-solid fa-file-invoice-dollar"></i>Invoices</a>
            <a class="btn btn-outline-primary" href="{{ route('company-admin.payments.index', ['client_id' => $client->id]) }}"><i class="fa-solid fa-money-check-dollar"></i>Payments</a>
            @if(auth()->user()->role === 'company_admin')
                @foreach(['active','inactive','blocked'] as $status)
                    <form method="POST" action="{{ route('company-admin.clients.status', [$client, $status]) }}" data-confirm="Change client status?">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">{{ ucfirst($status) }}</button>
                    </form>
                @endforeach
            @endif
        </div>
    </section>
</div>

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Projects</h2><p>Recent projects for this client.</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Tasks</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('company-admin.projects.show', $project) }}">{{ $project->name }}</a></td>
                            <td>@include('partials.status-badge', ['status' => $project->status])</td>
                            <td>{{ $project->progress }}%</td>
                            <td>{{ $project->tasks_count }}</td>
                            <td>{{ $project->due_date?->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-diagram-project', 'title' => 'No projects', 'message' => 'Client projects will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Project Requests</h2><p>Recent client project requests.</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Request</th><th>Status</th><th>Requested</th></tr></thead>
                <tbody>
                    @forelse($projectRequests as $requestItem)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('company-admin.project-requests.show', $requestItem) }}">{{ $requestItem->title }}</a><small>{{ $requestItem->service_type ?? 'General' }}</small></td>
                            <td>@include('partials.status-badge', ['status' => $requestItem->status])</td>
                            <td>{{ $requestItem->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-inbox', 'title' => 'No project requests', 'message' => 'Client requests will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header"><div><h2>Invoices</h2><p>Recent invoice records for this client.</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Invoice</th><th>Total</th><th>Paid</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('company-admin.invoices.show', $invoice) }}">Invoice #{{ $invoice->id }}</a><small>{{ $invoice->issue_date?->format('Y-m-d') ?? '-' }}</small></td>
                            <td>{{ $currency }} {{ number_format($invoice->total ?? 0, 2) }}</td>
                            <td>{{ $currency }} {{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td>@include('partials.status-badge', ['status' => $invoice->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-file-invoice-dollar', 'title' => 'No invoices', 'message' => 'Invoices for this client will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card">
        <div class="content-card-header"><div><h2>Payments</h2><p>Recent payment records connected to this client.</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('company-admin.payments.show', $payment) }}">{{ $payment->transaction_reference ?? 'Payment #'.$payment->id }}</a><small>{{ $payment->created_at->format('Y-m-d') }}</small></td>
                            <td>{{ $currency }} {{ number_format($payment->amount ?? 0, 2) }}</td>
                            <td>{{ str_replace('_', ' ', $payment->method ?? '-') }}</td>
                            <td>@include('partials.status-badge', ['status' => $payment->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-money-check-dollar', 'title' => 'No payments', 'message' => 'Payments for this client will appear here.'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
