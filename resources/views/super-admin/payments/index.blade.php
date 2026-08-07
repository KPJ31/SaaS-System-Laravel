@extends('layouts.app')

@section('title', 'Subscription Payments - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Subscription Payments',
    'description' => 'Verify SaaS subscription payments without exposing client project payments.',
])

<div class="stat-grid mb-3">
    @include('partials.stat-card', ['label' => 'Total Paid', 'value' => $currency.' '.number_format($totalRevenue, 2), 'icon' => 'fa-sack-dollar', 'type' => 'green'])
    @include('partials.stat-card', ['label' => 'Paid This Month', 'value' => $currency.' '.number_format($monthlyRevenue, 2), 'icon' => 'fa-chart-line'])
    @include('partials.stat-card', ['label' => 'Pending Amount', 'value' => $currency.' '.number_format($pendingRevenue, 2), 'icon' => 'fa-clock', 'type' => 'yellow'])
</div>

<section class="content-card">
    @include('partials.filter-bar', [
        'action' => route('super-admin.payments.index'),
        'resetUrl' => route('super-admin.payments.index'),
        'controls' => new Illuminate\Support\HtmlString(
            '<div><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="'.e(request('search')).'" placeholder="Reference or company"></div>'.
            '<div><label class="form-label" for="company">Company</label><select class="form-select" id="company" name="company"><option value="">All companies</option>'.
                $companies->map(fn ($company) => '<option value="'.$company->id.'" '.(request('company') == $company->id ? 'selected' : '').'>'.e($company->name).'</option>')->implode('').
            '</select></div>'.
            '<div><label class="form-label" for="plan">Plan</label><select class="form-select" id="plan" name="plan"><option value="">All plans</option>'.
                $plans->map(fn ($plan) => '<option value="'.$plan->id.'" '.(request('plan') == $plan->id ? 'selected' : '').'>'.e($plan->name).'</option>')->implode('').
            '</select></div>'.
            '<div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>'.
                collect($statuses)->map(fn ($status) => '<option value="'.e($status).'" '.(request('status') === $status ? 'selected' : '').'>'.e(str_replace('_', ' ', ucfirst($status))).'</option>')->implode('').
            '</select></div>'
        ),
    ])

    <div class="table-responsive mt-3">
        <table class="table align-middle app-table">
            <thead><tr><th>Reference</th><th>Company</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Verified By</th><th></th></tr></thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->transaction_reference ?? 'Payment #'.$payment->id }}</td>
                        <td>{{ $payment->company?->name ?? '-' }}</td>
                        <td>{{ $payment->subscriptionPlan?->name ?? '-' }}</td>
                        <td>{{ $currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ str_replace('_', ' ', $payment->method ?? '-') }}</td>
                        <td>@include('partials.status-badge', ['status' => $payment->status])</td>
                        <td>{{ $payment->paid_at?->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}</td>
                        <td>{{ $payment->verifier?->name ?? '-' }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.payments.show', $payment) }}"><i class="fa-regular fa-eye"></i> View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-money-bill', 'title' => 'No payments found', 'message' => 'Subscription payments matching your filters will appear here.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</section>
@endsection
