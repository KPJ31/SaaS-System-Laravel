@extends('layouts.auth')

@section('title', 'Elevanix - Smart Software Company Management System')

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
                <a href="#modules" data-nav-link>Modules</a>
                <a href="#how-it-works" data-nav-link>How It Works</a>
                <a href="#security" data-nav-link>Security</a>
                <a href="#contact" data-nav-link>Contact</a>
            </nav>

            <div class="landing-actions">
                <a href="{{ route('login') }}" class="btn btn-light">Sign In</a>
                <a href="{{ route('company.register') }}" class="btn btn-primary">Register Company</a>
            </div>
        </div>
    </header>

    <main>
        <section class="landing-hero section-pad">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="hero-badge">Smart Software Company Management System</span>
                        <h1>Run your software company with <span>clarity, control and confidence.</span></h1>
                        <p>Elevanix brings employees, clients, projects, tasks, work tracking, payments, invoices and reporting into one secure management workspace.</p>
                        <div class="hero-buttons">
                            <a class="btn btn-primary btn-lg" href="{{ route('company.register') }}">
                                <i class="fa-solid fa-building-circle-check" aria-hidden="true"></i>
                                Register Your Company
                            </a>
                            <a class="btn btn-outline-primary btn-lg" href="#features">
                                <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                                Explore Features
                            </a>
                        </div>
                        <p class="hero-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> No employee self-registration. Company accounts are reviewed before activation.</p>
                        <div class="feature-chips" aria-label="Quick features">
                            @foreach(['Team Management', 'Project Tracking', 'Work Timer', 'Payments', 'Reports'] as $chip)
                                <span>{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="dashboard-preview" aria-label="Elevanix dashboard preview">
                            <aside class="preview-sidebar">
                                <x-brand-logo variant="icon" tone="light" />
                                <span></span><span></span><span></span><span></span>
                            </aside>
                            <div class="preview-main">
                                <div class="preview-topbar">
                                    <strong>Operations Dashboard</strong>
                                    <span>Company Admin</span>
                                </div>
                                <div class="preview-stats">
                                    <article><i class="fa-solid fa-diagram-project"></i><strong>18</strong><span>Active Projects</span></article>
                                    <article><i class="fa-solid fa-list-check"></i><strong>64</strong><span>Open Tasks</span></article>
                                    <article><i class="fa-solid fa-clock"></i><strong>126h</strong><span>This Week</span></article>
                                </div>
                                <div class="preview-card">
                                    <div><strong>Client Portal Upgrade</strong><span class="status-pill">In Progress</span></div>
                                    <div class="preview-progress"><span style="width: 68%"></span></div>
                                    <small>Design review, backend APIs and QA tasks are moving together.</small>
                                </div>
                                <div class="preview-list">
                                    <span><i class="fa-solid fa-circle-check"></i> Invoice sent to client</span>
                                    <span><i class="fa-solid fa-user-plus"></i> New employee assigned</span>
                                    <span><i class="fa-solid fa-bell"></i> Task deadline reminder</span>
                                </div>
                            </div>
                            <div class="preview-float">
                                <i class="fa-solid fa-envelope-open-text"></i>
                                <span>Approval email ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="section-pad highlights-section">
            <div class="container">
                <div class="row g-3">
                    @foreach([
                        ['fa-solid fa-layer-group', 'Centralized Workspace', 'Keep daily operations connected in one place.'],
                        ['fa-solid fa-user-shield', 'Role-Based Access', 'Separate Super Admin, Company Admin and Employee workspaces.'],
                        ['fa-solid fa-stopwatch', 'Real-Time Work Tracking', 'Connect work sessions to people, projects and tasks.'],
                        ['fa-solid fa-database', 'Secure Company Data', 'Company-owned records stay separated by tenant.'],
                        ['fa-solid fa-bell', 'Automated Notifications', 'Send important registration and workflow updates.'],
                    ] as [$icon, $title, $text])
                        <div class="col-md-6 col-xl">
                            <article class="highlight-card h-100">
                                <i class="{{ $icon }}" aria-hidden="true"></i>
                                <h3>{{ $title }}</h3>
                                <p>{{ $text }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="modules" class="section-pad">
            <div class="container">
                <x-landing.section-heading title="Everything your software company needs in one platform." eyebrow="Core Modules">
                    Manage company operations without jumping between disconnected spreadsheets, timers and billing tools.
                </x-landing.section-heading>
                <div class="row g-4">
                    @foreach([
                        ['fa-solid fa-building', 'Company Management', 'Handle company profiles, approval status and workspace settings.'],
                        ['fa-solid fa-users', 'Employee Management', 'Create team accounts, roles, departments and assignments.'],
                        ['fa-solid fa-address-book', 'Client Management', 'Keep client contacts, projects, payments and invoices connected.'],
                        ['fa-solid fa-inbox', 'Project Requests', 'Capture, review and convert client requests into projects.'],
                        ['fa-solid fa-diagram-project', 'Project Management', 'Track project owners, members, budgets, progress and deadlines.'],
                        ['fa-solid fa-list-check', 'Task Management', 'Assign tasks, monitor status and keep work moving clearly.'],
                        ['fa-solid fa-clock', 'Work Timer and Sessions', 'Record focused time against projects and tasks.'],
                        ['fa-solid fa-file-invoice-dollar', 'Payments and Invoices', 'Manage invoice totals, payments and pending balances.'],
                        ['fa-solid fa-chart-line', 'Reports and Analytics', 'Review operational summaries and performance reports.'],
                        ['fa-solid fa-folder-open', 'Documents and Notifications', 'Share files and notify users about important activity.'],
                    ] as [$icon, $title, $text])
                        <div class="col-md-6 col-lg-4">
                            <x-landing.module-card :icon="$icon" :title="$title">{{ $text }}</x-landing.module-card>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section-pad role-section">
            <div class="container">
                <x-landing.section-heading title="Dedicated workspaces for every role." eyebrow="Role Access">
                    Each user sees the tools and company data they are authorized to use.
                </x-landing.section-heading>
                <div class="row g-4">
                    @foreach([
                        ['Super Admin', 'fa-solid fa-user-tie', 'tone-blue', ['Review company registration requests', 'Approve, suspend or reactivate companies', 'Manage subscription plans and payments', 'View platform reports and audit logs', 'Configure safe system settings']],
                        ['Company Admin', 'fa-solid fa-briefcase', 'tone-blue', ['Manage company profile and settings', 'Create employees and clients', 'Handle project requests', 'Manage company projects, tasks and invoices', 'Review company reports and work sessions']],
                        ['Employee', 'fa-solid fa-user-check', 'tone-cyan', ['View assigned company projects', 'Update assigned tasks', 'Start and stop work timer', 'Review own work sessions', 'Receive company notifications']],
                    ] as [$title, $icon, $tone, $items])
                        <div class="col-lg-4">
                            <article class="role-card h-100 {{ $tone }}">
                                <i class="{{ $icon }}" aria-hidden="true"></i>
                                <h3>{{ $title }}</h3>
                                <ul>
                                    @foreach($items as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="how-it-works" class="section-pad">
            <div class="container">
                <x-landing.section-heading title="From registration to daily operations." eyebrow="How It Works" />
                <div class="steps-row">
                    @foreach([
                        ['Register Company', 'fa-solid fa-building-circle-arrow-right', 'A software company submits company and administrator details.'],
                        ['Super Admin Review', 'fa-solid fa-magnifying-glass-chart', 'The platform administrator reviews the request.'],
                        ['Account Activation', 'fa-solid fa-envelope-circle-check', 'Approved companies receive activation information by email.'],
                        ['Start Managing', 'fa-solid fa-rocket', 'The Company Admin creates employees and begins operations.'],
                    ] as $index => [$title, $icon, $text])
                        <article class="step-card">
                            <span>{{ $index + 1 }}</span>
                            <i class="{{ $icon }}" aria-hidden="true"></i>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section-pad showcase-section">
            <div class="container">
                @foreach([
                    ['Manage projects from request to completion.', ['Capture project requests', 'Approve or reject requests', 'Convert approved requests into projects', 'Assign employees', 'Track tasks, deadlines and progress'], 'project'],
                    ['Track employee work without complexity.', ['Start and stop work timer', 'Connect sessions to projects and tasks', 'Monitor daily and weekly hours', 'Review employee productivity', 'Generate worked-hours reports'], 'timer'],
                    ['Keep payments and reporting organized.', ['Record client payments', 'Create invoices', 'Monitor pending balances', 'View monthly revenue', 'Export and print reports'], 'finance'],
                ] as $index => [$title, $items, $type])
                    <div class="row align-items-center g-4 showcase-row {{ $index % 2 ? 'flex-lg-row-reverse' : '' }}">
                        <div class="col-lg-6">
                            <h2>{{ $title }}</h2>
                            <ul class="check-list">
                                @foreach($items as $item)
                                    <li><i class="fa-solid fa-check" aria-hidden="true"></i>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <div class="showcase-mockup {{ $type }}">
                                <div class="mockup-header"><span></span><strong>{{ ucfirst($type) }} workspace</strong></div>
                                <div class="mockup-grid">
                                    <span></span><span></span><span></span>
                                </div>
                                <div class="mockup-lines">
                                    <span></span><span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="security" class="section-pad">
            <div class="container">
                <div class="security-card">
                    <div>
                        <span class="hero-badge">Security and Management</span>
                        <h2>Built for secure company operations.</h2>
                        <p>Elevanix uses role permissions, company data separation and server-side validation to support safer day-to-day management.</p>
                    </div>
                    <div class="security-grid">
                        @foreach(['Role-based route protection', 'Company data separation', 'Secure authentication', 'Protected documents', 'Activity and audit logging', 'Email notifications', 'Server-side validation', 'Password reset protection'] as $item)
                            <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="section-pad cta-section">
            <div class="container">
                <div class="cta-card">
                    <h2>Ready to organize your software company in one workspace?</h2>
                    <p>Submit your company registration request and begin after administrator approval.</p>
                    <div>
                        <a href="{{ route('company.register') }}" class="btn btn-light">Register Company</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light">Sign In</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="contact" class="landing-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <x-brand-logo tone="light" />
                    <p>Elevanix is a smart software company management system for teams, clients, projects, work tracking and finance operations.</p>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <h3>Platform</h3>
                    <a href="#features">Features</a>
                    <a href="#modules">Modules</a>
                    <a href="#security">Security</a>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <h3>Access</h3>
                    <a href="{{ route('login') }}">Sign In</a>
                    <a href="{{ route('company.register') }}">Register Company</a>
                    <a href="{{ route('password.request') }}">Forgot Password</a>
                </div>
                <div class="col-sm-4 col-lg-4">
                    <h3>Contact</h3>
                    <p>Support: {{ config('mail.from.address', 'noreply@example.com') }}</p>
                    <p>System: ESSCMS</p>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} Elevanix</span>
                <span>Smart Software Company Management System</span>
            </div>
        </div>
    </footer>
</div>
@endsection
