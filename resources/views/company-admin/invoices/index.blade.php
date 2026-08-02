@extends('layouts.app')

@section('title', 'Invoices - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'Invoices', 'description' => 'Create printable invoices and track payment status.', 'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.invoices.create').'"><i class="fa-solid fa-plus"></i>Create invoice</a>')])
<section class="content-card">
    <form class="row g-2 mb-3"><div class="col-md-4"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['draft','sent','partially_paid','paid','overdue','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div></form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Invoice</th><th>Client</th><th>Issue Date</th><th>Due Date</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td>{{ $invoice->invoice_number }}</td><td>{{ $invoice->client?->name }}</td><td>{{ $invoice->issue_date?->format('Y-m-d') }}</td><td>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td><td>${{ number_format($invoice->total, 2) }}</td><td>@include('partials.status-badge', ['status' => $invoice->status])</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.invoices.show', $invoice) }}"><i class="fa-solid fa-eye"></i></a><a class="btn btn-sm btn-outline-primary" href="{{ route('company-admin.invoices.print', $invoice) }}"><i class="fa-solid fa-print"></i></a></td></tr>@empty<tr><td colspan="7" class="empty-cell">No invoices found.</td></tr>@endforelse</tbody></table></div>
    {{ $invoices->links() }}
</section>
@endsection
