@extends('layouts.auth')

@section('title', 'Register Company - Elevanix')

@section('content')
<div class="register-page">
    <div class="register-shell">
        <div class="register-heading">
            <a href="{{ route('home') }}" class="text-decoration-none">@include('partials.brand-logo')</a>
            <span class="form-badge">Company Registration</span>
            <h1>Submit your software company for approval.</h1>
            <p>Your account becomes active after Super Admin review.</p>
        </div>
        <form method="POST" action="{{ route('company.register.store') }}" enctype="multipart/form-data" class="register-form">
            @csrf
            <h2>Company Information</h2>
            <div class="row g-3">
                <div class="col-md-6">@include('partials.input', ['name' => 'company_name', 'label' => 'Company Name'])</div>
                <div class="col-md-6">@include('partials.input', ['name' => 'company_email', 'label' => 'Company Email', 'type' => 'email'])</div>
                <div class="col-md-6">@include('partials.input', ['name' => 'company_phone', 'label' => 'Company Phone'])</div>
                <div class="col-md-6">@include('partials.input', ['name' => 'website', 'label' => 'Website', 'type' => 'url', 'required' => false])</div>
                <div class="col-12">
                    <label for="company_address" class="form-label">Company Address</label>
                    <textarea id="company_address" name="company_address" class="form-control @error('company_address') is-invalid @enderror" required>{{ old('company_address') }}</textarea>
                    @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">@include('partials.input', ['name' => 'logo', 'label' => 'Company Logo', 'type' => 'file', 'required' => false])</div>
            </div>

            <h2>Company Administrator Information</h2>
            <div class="row g-3">
                <div class="col-md-4">@include('partials.input', ['name' => 'admin_name', 'label' => 'Company Admin Name'])</div>
                <div class="col-md-4">@include('partials.input', ['name' => 'admin_email', 'label' => 'Company Admin Email', 'type' => 'email'])</div>
                <div class="col-md-4">@include('partials.input', ['name' => 'username', 'label' => 'Username'])</div>
            </div>

            <h2>Security Information</h2>
            <div class="row g-3">
                <div class="col-md-6">@include('partials.input', ['name' => 'password', 'label' => 'Password', 'type' => 'password'])</div>
                <div class="col-md-6">@include('partials.input', ['name' => 'password_confirmation', 'label' => 'Confirm Password', 'type' => 'password'])</div>
            </div>
            <p class="helper-text">Use at least 8 characters. A longer password is better.</p>
            <div class="form-check my-3">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" value="1" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">I agree to the terms and conditions.</label>
                @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <a href="{{ route('login') }}">Already approved? Sign in</a>
                <button class="btn btn-primary" type="submit">Submit Registration</button>
            </div>
        </form>
    </div>
</div>
@endsection
