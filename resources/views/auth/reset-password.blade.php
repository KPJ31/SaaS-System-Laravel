@extends('layouts.auth')

@section('title', 'Choose New Password - Elevanix')

@section('content')
<div class="auth-split">
    <section class="auth-visual" aria-label="New password guidance">
        <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo tone="light" /></a>
        <span class="portal-label">Secure Password</span>
        <h1>Create a stronger password.</h1>
        <p>Use a password that is unique to Elevanix and difficult for others to guess.</p>
        <div class="auth-features">
            <x-auth.feature-item icon="fa-solid fa-key" title="Password Protection">New passwords are hashed before storage.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-shield-halved" title="Workspace Safety">Access is limited to approved companies and authorized users.</x-auth.feature-item>
        </div>
        <small>&copy; {{ date('Y') }} Elevanix</small>
    </section>

    <section class="auth-form-panel">
        <div class="mobile-auth-brand"><a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo /></a></div>
        <div class="auth-card">
            <span class="form-badge">Password Reset</span>
            <h2>Set a new password</h2>
            <p>Enter your email and choose a new password for your account.</p>

            <form method="POST" action="{{ route('password.store') }}" data-loading-form>
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    @include('partials.input', ['name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'value' => old('email', $request->email), 'icon' => 'fa-regular fa-envelope'])
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New Password <span class="required-mark">*</span></label>
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

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm Password <span class="required-mark">*</span></label>
                    <div class="password-field input-icon">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control" required data-caps-lock>
                        <button type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                    </div>
                </div>

                <button class="btn btn-primary w-100" type="submit" data-loading-text="Resetting...">
                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                    Reset Password
                </button>
            </form>
            <p class="auth-switch"><a href="{{ route('login') }}">Back to sign in</a></p>
        </div>
    </section>
</div>
@endsection
