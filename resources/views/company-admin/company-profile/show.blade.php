@extends('layouts.app')

@section('title', 'Company Profile - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Company Profile'],
    ],
    'eyebrow' => 'Company Profile',
    'title' => $company->name,
    'description' => 'Manage your organization\'s company information and workspace identity.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.company-profile.edit').'"><i class="fa-solid fa-pen"></i>Edit profile</a><a class="btn btn-outline-primary" href="'.route('company-admin.settings.index').'"><i class="fa-solid fa-gear"></i>Settings</a>'),
])

<div class="content-grid mb-3">
    <section class="content-card">
        <div class="content-card-header">
            <div><h2>Workspace Identity</h2><p>Company details used across the employee and client workspace.</p></div>
        </div>
        <div class="d-flex align-items-center gap-3 mb-3">
            @if($company->logo_path)
                <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }} logo" style="width:72px;height:72px;border-radius:8px;object-fit:cover;">
            @else
                <div class="text-primary bg-light border" style="width:72px;height:72px;border-radius:8px;display:grid;place-items:center;">
                    <i class="fa-solid fa-building fa-xl"></i>
                </div>
            @endif
            <div>
                <h2 class="mb-1">{{ $company->name }}</h2>
                <p class="mb-0 text-muted">{{ $company->business_type ?? 'Business type not set' }}</p>
            </div>
        </div>
        <dl class="detail-list">
            <dt>Email</dt><dd>{{ $company->email }}</dd>
            <dt>Phone</dt><dd>{{ $company->phone ?? 'Not set' }}</dd>
            <dt>Website</dt><dd>{{ $company->website ?? 'Not set' }}</dd>
            <dt>Address</dt><dd>{{ $company->address ?? 'Not set' }}</dd>
            <dt>Description</dt><dd>{{ $company->description ?? 'Not set' }}</dd>
        </dl>
    </section>

    <section class="content-card">
        <div class="content-card-header">
            <div><h2>Platform Context</h2><p>Read-only platform and subscription information.</p></div>
        </div>
        <dl class="detail-list mt-3">
            <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $company->status])</dd>
            <dt>Subscription</dt><dd>{{ $company->activeSubscription?->plan?->name ?? 'No active subscription' }}</dd>
            <dt>Subscription Ends</dt><dd>{{ $company->activeSubscription?->ends_at?->format('Y-m-d') ?? $company->activeSubscription?->renews_at?->format('Y-m-d') ?? 'Not set' }}</dd>
            <dt>Registered</dt><dd>{{ $company->created_at->format('Y-m-d') }}</dd>
            <dt>Timezone</dt><dd>{{ $company->timezone }}</dd>
            <dt>Date Format</dt><dd>{{ $company->date_format }}</dd>
        </dl>
    </section>
</div>
@endsection
