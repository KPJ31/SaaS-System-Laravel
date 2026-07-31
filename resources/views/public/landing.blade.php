@extends('layouts.auth')

@section('title', 'Elevanix - Smart Software Company Management System')

@section('content')
<div class="public-page">
    <header class="public-header">
        <a href="{{ route('home') }}" class="text-decoration-none">@include('partials.brand-logo')</a>
        <nav>
            <a href="{{ route('company.register') }}">Company Registration</a>
            <a class="btn btn-primary-soft" href="{{ route('login') }}">Sign In</a>
        </nav>
    </header>

    <section class="hero-section">
        <div class="hero-copy">
            <span class="hero-badge">Smart Software Company Management System</span>
            <h1>Run your software company with clarity and control.</h1>
            <p>One intelligent workspace to manage employees, clients, projects, tasks, work tracking, billing and company operations.</p>
            <div class="feature-chips">
                @foreach(['Employees', 'Clients', 'Projects', 'Tasks', 'Time Tracking', 'Payments', 'Reports'] as $chip)
                    <span>{{ $chip }}</span>
                @endforeach
            </div>
        </div>
        <div class="hero-panel" aria-label="Elevanix dashboard preview">
            <div class="mini-top"></div>
            <div class="mini-grid">
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="mini-table">
                <span></span><span></span><span></span>
            </div>
        </div>
    </section>

    <section class="action-grid">
        <article class="action-card">
            <i class="fa-solid fa-building-user"></i>
            <h2>Company Registration</h2>
            <p>Register your software company and submit it for administrator approval.</p>
            <a class="btn btn-primary" href="{{ route('company.register') }}">Register Company</a>
        </article>
        <article class="action-card">
            <i class="fa-solid fa-right-to-bracket"></i>
            <h2>Sign In</h2>
            <p>Access your company workspace using your email address or username.</p>
            <a class="btn btn-outline-primary" href="{{ route('login') }}">Sign In</a>
        </article>
    </section>

    <footer class="public-footer">
        @include('partials.brand-logo', ['variant' => 'icon'])
        <span>&copy; {{ date('Y') }} Elevanix. Smart Software Company Management System.</span>
    </footer>
</div>
@endsection
