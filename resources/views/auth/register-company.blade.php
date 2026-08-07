@extends('layouts.auth')

@section('title', 'Register Company | Elevanix')

@section('content')
<div class="auth-split register-split">
    <section class="auth-visual" aria-label="Elevanix registration workflow">
        <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo tone="light" /></a>
        <span class="portal-label">Company Registration</span>
        <h1>Request an Elevanix workspace for your company.</h1>
        <p>Submit your company and administrator details. After platform approval, your Company Admin account is activated using the password you create here.</p>
        <div class="auth-features">
            <x-auth.feature-item icon="fa-solid fa-building-circle-check" title="Request First">Registration creates a pending company request, not an instant workspace.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-user-shield" title="Admin Account">The administrator becomes the Company Admin after approval.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-envelope-circle-check" title="Email Updates">Approval or rejection information is sent by email.</x-auth.feature-item>
        </div>
        <small>&copy; {{ date('Y') }} Elevanix</small>
    </section>

    <section class="auth-form-panel register-panel">
        <div class="mobile-auth-brand">
            <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo /></a>
        </div>

        <div class="register-card">
            <span class="form-badge">Workspace Request</span>
            <h1>Register Your Company</h1>
            <p>Submit your company details to request an Elevanix workspace. Employees are created later by the Company Admin.</p>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    Please review the highlighted fields before submitting your registration.
                </div>
            @endif

            <form method="POST" action="{{ route('company.register.store') }}" enctype="multipart/form-data" class="register-form" data-loading-form>
                @csrf

                <section class="registration-section" aria-labelledby="company-section-title">
                    <div class="content-card-header">
                        <div>
                            <h2 id="company-section-title">Company Information</h2>
                            <p>Tell us which software company will use Elevanix.</p>
                        </div>
                    </div>
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
                                    <strong>Choose a company logo</strong>
                                    <small>JPG, JPEG, PNG or WEBP. Maximum 2 MB.</small>
                                </div>
                                <img src="" alt="Selected company logo preview" data-logo-preview hidden>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2 d-none" data-logo-remove>Remove selected logo</button>
                            @error('logo')<div id="logo-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="registration-section" aria-labelledby="admin-section-title">
                    <div class="content-card-header">
                        <div>
                            <h2 id="admin-section-title">Administrator Information</h2>
                            <p>This account becomes the Company Admin after approval.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">@include('partials.input', ['name' => 'admin_name', 'label' => 'Company Admin Name', 'icon' => 'fa-regular fa-user'])</div>
                        <div class="col-md-6">@include('partials.input', ['name' => 'admin_email', 'label' => 'Company Admin Email', 'type' => 'email', 'icon' => 'fa-regular fa-envelope'])</div>
                        <div class="col-12">@include('partials.input', ['name' => 'username', 'label' => 'Username', 'icon' => 'fa-solid fa-at', 'help' => 'Use letters, numbers, dashes or underscores.'])</div>
                    </div>
                </section>

                <section class="registration-section" aria-labelledby="security-section-title">
                    <div class="content-card-header">
                        <div>
                            <h2 id="security-section-title">Security and Confirmation</h2>
                            <p>Create the password used by the Company Admin after approval.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
                            <div class="password-field input-icon">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                <input id="password" name="password" type="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" required data-password-strength data-caps-lock aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                                <button type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="password-strength" data-password-strength-output aria-live="polite">
                                <span></span><small>Use at least 8 characters.</small>
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

                <div class="form-actions register-submit-actions">
                    <a class="btn btn-outline-secondary" href="{{ route('home') }}">Back Home</a>
                    <button class="btn btn-primary" type="submit" data-loading-text="Submitting...">
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
