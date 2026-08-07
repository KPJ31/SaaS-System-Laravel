@extends('layouts.auth')

@section('title', 'Elevanix | Smart Software Company Management')

@section('content')
<div class="landing-page" id="top">
    <header class="landing-navbar" data-landing-nav>
        <div class="container landing-nav-inner">
            <a href="{{ route('home') }}" class="text-decoration-none" aria-label="Elevanix home">
                <x-brand-logo />
            </a>

            <button class="landing-menu-toggle" type="button" data-landing-menu-toggle aria-controls="landing-menu" aria-expanded="false" aria-label="Open navigation">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <nav id="landing-menu" class="landing-menu" data-landing-menu aria-label="Main navigation">
                <a href="#top" class="is-active" data-nav-link aria-current="page">Home</a>
                <a href="#features" data-nav-link>Features</a>
                <a href="#workflow" data-nav-link>How It Works</a>
                <a href="#roles" data-nav-link>Workspaces</a>
            </nav>

            <div class="landing-actions">
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Sign In</a>
                <a href="{{ route('company.register') }}" class="btn btn-primary">Register Company</a>
            </div>
        </div>
    </header>

    <main>
        <section class="landing-hero section-pad">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="hero-badge">Smart Software Company Management Platform</span>
                        <h1>Run your software company from one connected workspace.</h1>
                        <p>Elevanix centralizes teams, clients, projects, tasks, attendance, leave, work tracking, payments, invoices and reports in a secure SaaS platform.</p>
                        <div class="hero-buttons">
                            <a class="btn btn-primary btn-lg" href="{{ route('company.register') }}">
                                <i class="fa-solid fa-building-circle-check" aria-hidden="true"></i>
                                Register Your Company
                            </a>
                            <a class="btn btn-outline-primary btn-lg" href="{{ route('login') }}">
                                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                                Sign In
                            </a>
                        </div>
                        <p class="hero-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Company workspaces are activated after platform administrator approval.</p>
                        <div class="feature-chips" aria-label="Elevanix capabilities">
                            @foreach(['Projects', 'Employees', 'Clients', 'Work Tracking', 'Reports'] as $chip)
                                <span>{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="dashboard-preview auth-preview" aria-label="Elevanix workspace preview">
                            <aside class="preview-sidebar">
                                <x-brand-logo variant="icon" tone="light" />
                                <span></span><span></span><span></span><span></span>
                            </aside>
                            <div class="preview-main">
                                <div class="preview-topbar">
                                    <strong>Company Operations</strong>
                                    <span>Today</span>
                                </div>
                                <div class="preview-stats">
                                    <article><i class="fa-solid fa-users"></i><strong>Teams</strong><span>Employees and roles</span></article>
                                    <article><i class="fa-solid fa-diagram-project"></i><strong>Projects</strong><span>Requests to delivery</span></article>
                                    <article><i class="fa-solid fa-chart-line"></i><strong>Reports</strong><span>Work and finance</span></article>
                                </div>
                                <div class="preview-card">
                                    <div><strong>Workspace Activation</strong><span class="status-pill">Review</span></div>
                                    <div class="preview-progress"><span style="width: 66%"></span></div>
                                    <small>Register, receive approval, then manage daily company operations.</small>
                                </div>
                                <div class="preview-list">
                                    <span><i class="fa-solid fa-circle-check"></i> Project request reviewed</span>
                                    <span><i class="fa-solid fa-clock"></i> Work session recorded</span>
                                    <span><i class="fa-solid fa-file-invoice-dollar"></i> Invoice ready for payment</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="section-pad">
            <div class="container">
                <x-landing.section-heading title="Core capabilities for software company operations." eyebrow="Platform Features">
                    Focused tools for the workflows Elevanix already supports.
                </x-landing.section-heading>
                <div class="row g-4">
                    @foreach([
                        ['fa-solid fa-diagram-project', 'Project Management', 'Plan projects, assign teams, monitor progress and deadlines.'],
                        ['fa-solid fa-users-gear', 'Team Management', 'Create employee accounts, manage status, roles and permissions.'],
                        ['fa-solid fa-address-book', 'Client Management', 'Keep client records connected with projects, invoices and payments.'],
                        ['fa-solid fa-stopwatch', 'Task & Work Tracking', 'Track task progress and work sessions against real delivery work.'],
                        ['fa-solid fa-file-invoice-dollar', 'Finance & Invoicing', 'Manage client payments, invoices, balances and payment status.'],
                        ['fa-solid fa-chart-column', 'Reports & Analytics', 'Review company activity, work hours, revenue and operational reports.'],
                    ] as [$icon, $title, $text])
                        <div class="col-md-6 col-lg-4">
                            <x-landing.module-card :icon="$icon" :title="$title">{{ $text }}</x-landing.module-card>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="workflow" class="section-pad role-section">
            <div class="container">
                <x-landing.section-heading title="A clear path from registration to daily work." eyebrow="Workflow" />
                <div class="steps-row workflow-steps">
                    @foreach([
                        ['Company Registration', 'Submit company and administrator details.'],
                        ['Admin Approval', 'A Super Admin reviews the request.'],
                        ['Workspace Activated', 'Approval creates the company, admin account and subscription.'],
                        ['Operations Begin', 'Teams, clients, projects, tasks and finance work are managed inside Elevanix.'],
                    ] as $index => [$title, $text])
                        <article class="step-card">
                            <span>{{ $index + 1 }}</span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="roles" class="section-pad">
            <div class="container">
                <x-landing.section-heading title="Role-aware workspaces without separate login portals." eyebrow="Workspace Access">
                    Users sign in once, and Elevanix routes them to the right workspace.
                </x-landing.section-heading>
                <div class="row g-4">
                    @foreach([
                        ['Super Admin', 'fa-solid fa-user-tie', 'Platform owner tools for companies, subscriptions, revenue, reports and audit logs.'],
                        ['Company Admin', 'fa-solid fa-briefcase', 'Company operations tools for employees, clients, projects, tasks, finance and reports.'],
                        ['Employee', 'fa-solid fa-user-check', 'Personal work tools for assigned projects, tasks, attendance, leave and notifications.'],
                    ] as [$title, $icon, $text])
                        <div class="col-lg-4">
                            <article class="role-card h-100">
                                <i class="{{ $icon }}" aria-hidden="true"></i>
                                <h3>{{ $title }}</h3>
                                <p>{{ $text }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section-pad cta-section">
            <div class="container">
                <div class="cta-card">
                    <h2>Ready to manage your software company in one workspace?</h2>
                    <p>Submit your company registration request. Your workspace becomes available after platform approval.</p>
                    <div class="hero-buttons justify-content-center">
                        <a href="{{ route('company.register') }}" class="btn btn-light">Register Company</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light">Sign In</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <x-brand-logo tone="light" />
                    <p>Elevanix is a smart software company management platform for teams, clients, projects, work tracking, finance and operational reporting.</p>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <h3>Product</h3>
                    <a href="#features">Features</a>
                    <a href="#workflow">How It Works</a>
                    <a href="#roles">Workspaces</a>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <h3>Access</h3>
                    <a href="{{ route('login') }}">Sign In</a>
                    <a href="{{ route('company.register') }}">Register Company</a>
                    <a href="{{ route('password.request') }}">Forgot Password</a>
                </div>
                <div class="col-sm-4 col-lg-3">
                    <h3>Legal</h3>
                    <a href="{{ route('privacy') }}">Privacy Policy</a>
                    <a href="{{ route('terms') }}">Terms and Conditions</a>
                    <a href="{{ route('contact') }}">Contact</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} Elevanix</span>
                <span>Smart Software Company Management</span>
            </div>
        </div>
    </footer>
</div>
@endsection
