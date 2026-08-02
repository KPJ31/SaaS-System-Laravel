@extends('layouts.auth')

@section('title', 'Register Company - Elevanix')

@php
    $errorFields = $errors->keys();
    $initialStep = 1;
    if (collect($errorFields)->intersect(['admin_name', 'admin_email', 'username'])->isNotEmpty()) {
        $initialStep = 2;
    }
    if (collect($errorFields)->intersect(['password', 'password_confirmation', 'terms'])->isNotEmpty()) {
        $initialStep = 3;
    }
@endphp

@section('content')
<div class="auth-split register-split">
    <section class="auth-visual" aria-label="Elevanix registration benefits">
        <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo tone="light" /></a>
        <span class="portal-label">Company Registration</span>
        <h1>Register your software company.</h1>
        <p>Submit your company details for Super Admin review. Your account will be activated after approval.</p>
        <div class="auth-features">
            <x-auth.feature-item icon="fa-solid fa-building-user" title="Software Companies Only">Public registration is for company accounts.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-user-lock" title="Administrator Account">The first approved account becomes the Company Admin.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-envelope-circle-check" title="Email Updates">Review results are sent without exposing passwords.</x-auth.feature-item>
        </div>
        <small>&copy; {{ date('Y') }} Elevanix</small>
    </section>

    <section class="auth-form-panel register-panel">
        <div class="mobile-auth-brand">
            <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo /></a>
        </div>
        <div class="register-card">
            <span class="form-badge">ESSCMS Registration</span>
            <h1>Register your software company</h1>
            <p>Employees are created by the Company Admin after the company account is approved.</p>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    Please review the highlighted fields before submitting your registration.
                </div>
            @endif

            <div class="step-progress" data-step-progress aria-label="Registration progress">
                <button type="button" class="is-active" data-step-tab="1"><span>1</span>Company</button>
                <button type="button" data-step-tab="2"><span>2</span>Administrator</button>
                <button type="button" data-step-tab="3"><span>3</span>Security</button>
            </div>

            <form method="POST" action="{{ route('company.register.store') }}" enctype="multipart/form-data" class="register-form" data-register-form data-initial-step="{{ $initialStep }}" data-loading-form>
                @csrf

                <section class="form-step is-active" data-step="1" aria-labelledby="step-company-title">
                    <h2 id="step-company-title">Company Information</h2>
                    <p class="step-text">Tell us about the software company that will use Elevanix.</p>
                    <div class="row g-3">
                        <div class="col-md-6">@include('partials.input', ['name' => 'company_name', 'label' => 'Company Name', 'icon' => 'fa-solid fa-building'])</div>
                        <div class="col-md-6">@include('partials.input', ['name' => 'company_email', 'label' => 'Company Email', 'type' => 'email', 'icon' => 'fa-regular fa-envelope'])</div>
                        <div class="col-md-6">@include('partials.input', ['name' => 'company_phone', 'label' => 'Company Phone', 'icon' => 'fa-solid fa-phone', 'help' => 'Use a clear phone format, for example +1 555 0100.'])</div>
                        <div class="col-md-6">@include('partials.input', ['name' => 'website', 'label' => 'Website', 'type' => 'url', 'required' => false, 'icon' => 'fa-solid fa-globe', 'help' => 'Include https:// when available.'])</div>
                        <div class="col-12">
                            <label for="company_address" class="form-label">Company Address <span class="required-mark">*</span></label>
                            <textarea id="company_address" name="company_address" class="form-control @error('company_address') is-invalid @enderror" required rows="3" aria-invalid="{{ $errors->has('company_address') ? 'true' : 'false' }}">{{ old('company_address') }}</textarea>
                            @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="logo" class="form-label">Company Logo</label>
                            <div class="logo-upload @error('logo') is-invalid @enderror" data-logo-drop>
                                <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-logo-input aria-invalid="{{ $errors->has('logo') ? 'true' : 'false' }}" @if($errors->has('logo')) aria-describedby="logo-error" @endif>
                                <div>
                                    <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                                    <strong>Choose or drag a company logo</strong>
                                    <small>JPG, JPEG, PNG or WEBP. Maximum 2 MB.</small>
                                </div>
                                <img src="" alt="Selected company logo preview" data-logo-preview hidden>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" data-logo-remove>Remove selected logo</button>
                            @error('logo')<div id="logo-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="form-step" data-step="2" aria-labelledby="step-admin-title">
                    <h2 id="step-admin-title">Company Administrator</h2>
                    <p class="step-text">This person will manage the company workspace after approval.</p>
                    <div class="row g-3">
                        <div class="col-md-6">@include('partials.input', ['name' => 'admin_name', 'label' => 'Company Admin Name', 'icon' => 'fa-regular fa-user'])</div>
                        <div class="col-md-6">@include('partials.input', ['name' => 'admin_email', 'label' => 'Company Admin Email', 'type' => 'email', 'icon' => 'fa-regular fa-envelope'])</div>
                        <div class="col-12">@include('partials.input', ['name' => 'username', 'label' => 'Username', 'icon' => 'fa-solid fa-at', 'help' => 'Use letters, numbers, dashes or underscores.'])</div>
                    </div>
                </section>

                <section class="form-step" data-step="3" aria-labelledby="step-security-title">
                    <h2 id="step-security-title">Security and Confirmation</h2>
                    <p class="step-text">Use a strong password. It will not be sent by email.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
                            <div class="password-field input-icon">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                <input id="password" name="password" type="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" required data-password-strength data-caps-lock aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                                <button type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="password-strength" data-password-strength-output aria-live="polite">
                                <span></span><small>Use at least 8 characters with a mix of letters, numbers and symbols.</small>
                            </div>
                            <div class="caps-warning" data-caps-warning aria-live="polite">Caps Lock is on.</div>
                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="required-mark">*</span></label>
                            <div class="password-field input-icon">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control" required data-caps-lock>
                                <button type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="form-check terms-check">
                        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" value="1" id="terms" name="terms" required @checked(old('terms')) aria-invalid="{{ $errors->has('terms') ? 'true' : 'false' }}">
                        <label class="form-check-label" for="terms">I confirm this is a software company registration request and agree to the terms.</label>
                        @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </section>

                <div class="register-actions">
                    <button class="btn btn-outline-primary" type="button" data-step-prev>
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        Previous
                    </button>
                    <button class="btn btn-primary" type="button" data-step-next>
                        Next
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-primary" type="submit" data-step-submit data-loading-text="Submitting...">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        Submit Registration
                    </button>
                </div>
            </form>

            <p class="auth-switch">Already approved? <a href="{{ route('login') }}">Sign in</a></p>
        </div>
    </section>
</div>
@endsection
