@extends('layouts.auth')

@section('title', 'Sign In - Elevanix')

@section('content')
<div class="auth-split">
    <section class="auth-visual">
        @include('partials.brand-logo', ['tone' => 'light'])
        <span class="portal-label">Company Portal</span>
        <h1>Manage your company with clarity.</h1>
        <p>Bring projects, employees, clients, time tracking, invoices and reports into one focused workspace.</p>
        <div class="auth-features">
            @foreach(['Projects and Operations', 'Employees and Clients', 'Time Tracking and Productivity', 'Payments, Invoices and Reports'] as $feature)
                <div><i class="fa-solid fa-check"></i>{{ $feature }}</div>
            @endforeach
        </div>
        <small>&copy; {{ date('Y') }} Elevanix</small>
    </section>
    <section class="auth-form-panel">
        <div class="auth-card">
            <span class="form-badge">ESSCMS Portal</span>
            <h2>Welcome back</h2>
            <p>Sign in using your email address or username.</p>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label">Email or Username</label>
                    <input id="login" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" required autofocus>
                    @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                        <button type="button" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <span class="small text-muted">Forgot password</span>
                </div>
                <button class="btn btn-primary w-100" type="submit">Sign In</button>
            </form>
            <p class="auth-switch">Need access? <a href="{{ route('company.register') }}">Register your company</a></p>
        </div>
    </section>
</div>
@endsection
