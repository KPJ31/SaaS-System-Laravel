@extends('layouts.app')

@section('title', 'Subscription Plans - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Subscription Plans',
    'description' => 'Manage plan pricing, usage limits, trial periods and subscriber access.',
    'actions' => new Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('super-admin.subscription-plans.create').'"><i class="fa-solid fa-plus" aria-hidden="true"></i> New Plan</a>'),
])

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Plan Catalog</h2>
            <p>Active plans can be assigned to approved companies during onboarding.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Price</th>
                    <th>Limits</th>
                    <th>Subscribers</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>
                            {{ $plan->name }}
                            <small>{{ $plan->slug }}</small>
                        </td>
                        <td>
                            ${{ number_format($plan->monthly_price, 2) }} / month
                            <small>{{ $plan->annual_price ? '$'.number_format($plan->annual_price, 2).' / year' : 'No annual price' }}</small>
                        </td>
                        <td>
                            {{ $plan->employee_limit }} employees
                            <small>{{ $plan->client_limit }} clients, {{ $plan->project_limit }} projects</small>
                        </td>
                        <td>{{ $plan->subscriptions_count }}</td>
                        <td>@include('partials.status-badge', ['status' => $plan->status])</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('super-admin.subscription-plans.edit', $plan) }}">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>Edit
                                </a>
                                <form method="POST" action="{{ route('super-admin.subscription-plans.status', [$plan, $plan->status === 'active' ? 'inactive' : 'active']) }}" data-confirm="Update this plan status?">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit">
                                        <i class="fa-solid {{ $plan->status === 'active' ? 'fa-pause' : 'fa-play' }}" aria-hidden="true"></i>
                                        {{ $plan->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-layer-group', 'title' => 'No subscription plans', 'message' => 'Create a plan before approving new company subscriptions.', 'action' => new Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('super-admin.subscription-plans.create').'">Create Plan</a>')])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $plans->links() }}
</section>
@endsection
