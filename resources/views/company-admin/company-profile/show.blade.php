@extends('layouts.app')

@section('title', 'Company Profile - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Profile',
    'title' => $company->name,
    'description' => 'Manage the public company details your team uses across Elevanix.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="'.route('company-admin.company-profile.edit').'"><i class="fa-solid fa-pen"></i>Edit profile</a><a class="btn btn-outline-primary" href="'.route('company-admin.subscription.index').'"><i class="fa-solid fa-credit-card"></i>Subscription Information</a>'),
])

<section class="content-card">
    <dl class="detail-list">
        <dt>Email</dt><dd>{{ $company->email }}</dd>
        <dt>Phone</dt><dd>{{ $company->phone ?? 'Not set' }}</dd>
        <dt>Website</dt><dd>{{ $company->website ?? 'Not set' }}</dd>
        <dt>Business Type</dt><dd>{{ $company->business_type ?? 'Not set' }}</dd>
        <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $company->status])</dd>
        <dt>Subscription</dt><dd>{{ $company->activeSubscription?->plan?->name ?? 'No active subscription' }}</dd>
        <dt>Subscription Ends</dt><dd>{{ $company->activeSubscription?->ends_at?->format('Y-m-d') ?? $company->activeSubscription?->renews_at?->format('Y-m-d') ?? 'Not set' }}</dd>
        <dt>Registered</dt><dd>{{ $company->created_at->format('Y-m-d') }}</dd>
        <dt>Timezone</dt><dd>{{ $company->timezone }}</dd>
        <dt>Date Format</dt><dd>{{ $company->date_format }}</dd>
        <dt>Address</dt><dd>{{ $company->address ?? 'Not set' }}</dd>
        <dt>Description</dt><dd>{{ $company->description ?? 'Not set' }}</dd>
    </dl>
</section>
@endsection
