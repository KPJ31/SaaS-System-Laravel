@extends('layouts.app')

@section('title', 'Plan Change Requests - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Super Admin', 'title' => 'Subscription Plan Change Requests', 'description' => 'Review upgrade, downgrade, payment, approval, and rejection workflow.'])

<section class="content-card">
    <form class="row g-3 mb-3" method="GET">
        <div class="col-md-3"><select class="form-select" name="company_id"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="requested_plan_id"><option value="">Requested plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(request('requested_plan_id') == $plan->id)>{{ $plan->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="change_type"><option value="">All types</option>@foreach($changeTypes as $type)<option value="{{ $type }}" @selected(request('change_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="payment_status"><option value="">Payment status</option>@foreach($paymentStatuses as $status)<option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter"></i></button></div>
    </form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Company</th><th>Plans</th><th>Type</th><th>Billing</th><th>Amount</th><th>Payment</th><th>Status</th><th>Requested</th><th></th></tr></thead><tbody>
        @forelse($requests as $requestItem)
            <tr><td>{{ $requestItem->company?->name }}</td><td>{{ $requestItem->currentPlan?->name }}<small>To {{ $requestItem->requestedPlan?->name }}</small></td><td>{{ str_replace('_', ' ', $requestItem->change_type) }}</td><td>{{ ucfirst($requestItem->billing_cycle) }}</td><td>${{ number_format($requestItem->payable_amount, 2) }}</td><td>{{ $requestItem->payment?->status ? str_replace('_', ' ', $requestItem->payment->status) : '-' }}</td><td>@include('partials.status-badge', ['status' => $requestItem->status])</td><td>{{ $requestItem->created_at->format('M d, Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.subscription-change-requests.show', $requestItem) }}">View</a></td></tr>
        @empty
            <tr><td colspan="9" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-arrows-rotate', 'title' => 'No plan-change requests', 'message' => 'Company Admin requests will appear here.'])</td></tr>
        @endforelse
    </tbody></table></div>{{ $requests->links() }}
</section>
@endsection
