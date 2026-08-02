@extends('layouts.app')

@section('title', ($plan->exists ? 'Edit' : 'Create').' Subscription Plan - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => $plan->exists ? 'Edit Subscription Plan' : 'Create Subscription Plan',
    'description' => 'Define pricing, limits and display settings used when companies are activated.',
    'actions' => new Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('super-admin.subscription-plans.index').'"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Plans</a>'),
])

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Plan Information</h2>
            <p>Keep plan names clear and limits realistic for company onboarding.</p>
        </div>
    </div>
    <form method="POST" action="{{ $plan->exists ? route('super-admin.subscription-plans.update', $plan) : route('super-admin.subscription-plans.store') }}" data-loading-form>
        @csrf
        @if($plan->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">@include('partials.input', ['name' => 'name', 'label' => 'Plan Name', 'value' => old('name', $plan->name)])</div>
            <div class="col-md-6">@include('partials.input', ['name' => 'slug', 'label' => 'Slug', 'value' => old('slug', $plan->slug), 'required' => false])</div>
            <div class="col-md-6">@include('partials.input', ['name' => 'monthly_price', 'label' => 'Monthly Price', 'type' => 'number', 'value' => old('monthly_price', $plan->monthly_price ?? 0), 'help' => 'Enter the monthly price in USD.'])</div>
            <div class="col-md-6">@include('partials.input', ['name' => 'annual_price', 'label' => 'Annual Price', 'type' => 'number', 'value' => old('annual_price', $plan->annual_price), 'required' => false, 'help' => 'Leave blank when the plan does not offer annual billing.'])</div>
            <div class="col-md-3">@include('partials.input', ['name' => 'employee_limit', 'label' => 'Employee Limit', 'type' => 'number', 'value' => old('employee_limit', $plan->employee_limit ?? 5)])</div>
            <div class="col-md-3">@include('partials.input', ['name' => 'client_limit', 'label' => 'Client Limit', 'type' => 'number', 'value' => old('client_limit', $plan->client_limit ?? 10)])</div>
            <div class="col-md-3">@include('partials.input', ['name' => 'project_limit', 'label' => 'Project Limit', 'type' => 'number', 'value' => old('project_limit', $plan->project_limit ?? 10)])</div>
            <div class="col-md-3">@include('partials.input', ['name' => 'storage_limit_mb', 'label' => 'Storage Limit MB', 'type' => 'number', 'value' => old('storage_limit_mb', $plan->storage_limit_mb ?? 1024)])</div>
            <div class="col-md-4">@include('partials.input', ['name' => 'trial_days', 'label' => 'Trial Days', 'type' => 'number', 'value' => old('trial_days', $plan->trial_days ?? 0)])</div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $plan->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $plan->status) === 'inactive')>Inactive</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">@include('partials.input', ['name' => 'display_order', 'label' => 'Display Order', 'type' => 'number', 'value' => old('display_order', $plan->display_order ?? 0)])</div>
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror">{{ old('description', $plan->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="features" class="form-label">Features</label>
                <textarea id="features" name="features" class="form-control @error('features') is-invalid @enderror" rows="5">{{ old('features', implode(PHP_EOL, $plan->features ?? [])) }}</textarea>
                <p class="helper-text">Enter one feature per line.</p>
                @error('features')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-primary" type="submit" data-loading-text="{{ $plan->exists ? 'Updating...' : 'Creating...' }}">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                {{ $plan->exists ? 'Update Plan' : 'Create Plan' }}
            </button>
        </div>
    </form>
</section>
@endsection
