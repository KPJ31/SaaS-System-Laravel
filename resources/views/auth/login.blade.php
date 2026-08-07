@extends('layouts.auth')

@section('title', 'Sign In | Elevanix')

@section('content')
<div class="auth-split">
    <section class="auth-visual" aria-label="Elevanix workspace benefits">
        <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo tone="light" /></a>
        <span class="portal-label">Elevanix Workspace</span>
        <h1>Run your software company from one connected workspace.</h1>
        <p>One secure workspace for employees, clients, projects, tasks, work sessions, payments, invoices and operational reports.</p>
        <div class="auth-features">
            <x-auth.feature-item icon="fa-solid fa-diagram-project" title="Projects and Operations">Manage requests, projects, tasks and deadlines.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-users-gear" title="Employees and Work Tracking">Organize team accounts, assignments and work sessions.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-file-invoice-dollar" title="Clients and Finance">Keep client records, payments and invoices connected.</x-auth.feature-item>
            <x-auth.feature-item icon="fa-solid fa-chart-line" title="Reports and Activity">Review company performance, notifications and audit history.</x-auth.feature-item>
        </div>
        <small>&copy; {{ date('Y') }} Elevanix</small>
    </section>

    <section class="auth-form-panel">
        <div class="mobile-auth-brand">
            <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo /></a>
        </div>
        <div class="auth-card">
            <span class="form-badge">Secure Workspace Access</span>
            <h2>Welcome back</h2>
            <p>Sign in to continue to your Elevanix workspace.</p>

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" data-loading-form>
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label">Email or username</label>
                    <div class="input-icon">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input id="login" name="login" value="{{ old('login') }}" autocomplete="username" class="form-control @error('login') is-invalid @enderror" required autofocus aria-invalid="{{ $errors->has('login') ? 'true' : 'false' }}" @if($errors->has('login')) aria-describedby="login-error" @endif>
                    </div>
                    @error('login')<div id="login-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-field input-icon">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input id="password" name="password" type="password" autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" required data-caps-lock aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if($errors->has('password')) aria-describedby="password-error" @endif>
                        <button type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <div class="caps-warning" data-caps-warning aria-live="polite">Caps Lock is on.</div>
                    @error('password')<div id="password-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="auth-row mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="remember" id="remember" @checked(old('remember'))>
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>

                <button class="btn btn-primary w-100" type="submit" data-loading-text="Signing in...">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                    Sign In
                </button>
            </form>

            <p class="auth-switch">Need access? <a href="{{ route('company.register') }}">Register your company</a></p>
            <p class="auth-note">Access is limited to approved companies and authorized users.</p>
        </div>
    </section>
</div>
@endsection
