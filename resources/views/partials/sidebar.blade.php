@php
    $role = auth()->user()->role;
    $pendingCompanyRequests = $role === 'super_admin'
        ? \App\Models\CompanyRegistrationRequest::where('status', 'pending')->count()
        : 0;
    $items = match ($role) {
        'super_admin' => [
            ['Dashboard', 'fa-gauge-high', route('super-admin.dashboard'), request()->routeIs('super-admin.dashboard'), null],
            ['Companies', 'fa-building', route('super-admin.companies.index'), request()->routeIs('super-admin.companies.*'), null],
            ['Company Registration Requests', 'fa-building-circle-check', route('super-admin.company-requests.index'), request()->routeIs('super-admin.company-requests.*'), $pendingCompanyRequests],
            ['Subscription Plans', 'fa-layer-group', route('super-admin.subscription-plans.index'), request()->routeIs('super-admin.subscription-plans.*'), null],
            ['Company Subscriptions', 'fa-arrows-rotate', route('super-admin.subscriptions.index'), request()->routeIs('super-admin.subscriptions.*'), null],
            ['Platform Users', 'fa-users-gear', route('super-admin.users.index'), request()->routeIs('super-admin.users.*'), null],
            ['Payments and Revenue', 'fa-money-bill-trend-up', route('super-admin.payments.index'), request()->routeIs('super-admin.payments.*'), null],
            ['Reports and Analytics', 'fa-chart-pie', route('super-admin.reports.index'), request()->routeIs('super-admin.reports.*'), null],
            ['Notifications', 'fa-bell', route('super-admin.notifications.index'), request()->routeIs('super-admin.notifications.*'), auth()->user()->unreadNotifications()->count()],
            ['Audit Logs', 'fa-clipboard-list', route('super-admin.audit-logs.index'), request()->routeIs('super-admin.audit-logs.*'), null],
            ['System Settings', 'fa-sliders', route('super-admin.settings.index'), request()->routeIs('super-admin.settings.*'), null],
            ['My Profile', 'fa-user-shield', route('super-admin.profile.show'), request()->routeIs('super-admin.profile.*'), null],
        ],
        'company_admin' => [
            ['Dashboard', 'fa-gauge-high', route('company-admin.dashboard'), request()->routeIs('company-admin.dashboard'), null],
            ['Company Profile', 'fa-building', route('company-admin.company-profile.show'), request()->routeIs('company-admin.company-profile.*'), null],
            ['Employees', 'fa-users', route('company-admin.employees.index'), request()->routeIs('company-admin.employees.*'), \App\Models\User::where('company_id', auth()->user()->company_id)->where('role', 'employee')->where('status', 'pending')->count()],
            ['Clients', 'fa-handshake', route('company-admin.clients.index'), request()->routeIs('company-admin.clients.*'), null],
            ['Project Requests', 'fa-inbox', route('company-admin.project-requests.index'), request()->routeIs('company-admin.project-requests.*'), \App\Models\ProjectRequest::where('company_id', auth()->user()->company_id)->whereIn('status', ['pending', 'under_review'])->count()],
            ['Projects', 'fa-diagram-project', route('company-admin.projects.index'), request()->routeIs('company-admin.projects.*'), null],
            ['Tasks', 'fa-list-check', route('company-admin.tasks.index'), request()->routeIs('company-admin.tasks.*'), \App\Models\Task::where('company_id', auth()->user()->company_id)->whereDate('due_date', '<', today())->whereNotIn('status', ['completed', 'cancelled'])->count()],
            ['Work Sessions', 'fa-clock', route('company-admin.work-sessions.index'), request()->routeIs('company-admin.work-sessions.*'), null],
            ['Leave Requests', 'fa-calendar-check', route('company-admin.leave-requests.index'), request()->routeIs('company-admin.leave-requests.*'), \App\Models\LeaveRequest::where('company_id', auth()->user()->company_id)->where('status', 'pending')->count()],
            ['Documents', 'fa-folder-open', route('company-admin.documents.index'), request()->routeIs('company-admin.documents.*'), null],
            ['Time Reports', 'fa-chart-line', route('company-admin.reports.index', ['report' => 'work-hours']), request()->routeIs('company-admin.reports.*'), null],
            ['Payments', 'fa-credit-card', route('company-admin.payments.index'), request()->routeIs('company-admin.payments.*'), \App\Models\Payment::where('company_id', auth()->user()->company_id)->where('payment_type', 'client_project')->whereIn('status', ['pending', 'requested', 'proof_submitted'])->count()],
            ['Invoices', 'fa-file-invoice-dollar', route('company-admin.invoices.index'), request()->routeIs('company-admin.invoices.*'), null],
            ['Feedback', 'fa-star', route('company-admin.feedback.index'), request()->routeIs('company-admin.feedback.*'), null],
            ['Notifications', 'fa-bell', route('company-admin.notifications.index'), request()->routeIs('company-admin.notifications.*'), auth()->user()->unreadNotifications()->count()],
            ['Reports and Analytics', 'fa-chart-pie', route('company-admin.reports.index'), request()->routeIs('company-admin.reports.*'), null],
            ['Activity Logs', 'fa-clipboard-list', route('company-admin.activity-logs.index'), request()->routeIs('company-admin.activity-logs.*'), null],
            ['Company Settings', 'fa-sliders', route('company-admin.settings.index'), request()->routeIs('company-admin.settings.*'), null],
            ['My Profile', 'fa-user', route('company-admin.profile.show'), request()->routeIs('company-admin.profile.*'), null],
        ],
        default => [
            ['Dashboard', 'fa-gauge-high', route('employee.dashboard'), request()->routeIs('employee.dashboard'), null],
            ['My Projects', 'fa-diagram-project', route('employee.projects.index'), request()->routeIs('employee.projects.*'), null],
            ['My Tasks', 'fa-list-check', route('employee.tasks.index'), request()->routeIs('employee.tasks.*'), \App\Models\Task::where('company_id', auth()->user()->company_id)->where('assignee_id', auth()->id())->whereIn('status', ['todo', 'assigned', 'in_progress', 'paused', 'blocked'])->count()],
            ['Work Timer', 'fa-stopwatch', route('employee.tasks.index'), request()->routeIs('employee.tasks.show'), \App\Models\WorkSession::where('company_id', auth()->user()->company_id)->where('user_id', auth()->id())->whereNull('ended_at')->count()],
            ['Work Sessions', 'fa-clock', route('employee.work-sessions.index'), request()->routeIs('employee.work-sessions.*'), null],
            ['My Documents', 'fa-folder-open', route('employee.documents.index'), request()->routeIs('employee.documents.*'), null],
            ['Leave Requests', 'fa-calendar-check', route('employee.leave-requests.index'), request()->routeIs('employee.leave-requests.*'), \App\Models\LeaveRequest::where('company_id', auth()->user()->company_id)->where('user_id', auth()->id())->where('status', 'pending')->count()],
            ['Performance', 'fa-chart-line', route('employee.performance.index'), request()->routeIs('employee.performance.*'), null],
            ['Notifications', 'fa-bell', route('employee.notifications.index'), request()->routeIs('employee.notifications.*'), auth()->user()->unreadNotifications()->count()],
            ['Activity History', 'fa-clipboard-list', route('employee.activity.index'), request()->routeIs('employee.activity.*'), null],
            ['My Profile', 'fa-user', route('employee.profile.show'), request()->routeIs('employee.profile.*'), null],
            ['Change Password', 'fa-key', route('employee.password.edit'), request()->routeIs('employee.password.*'), null],
        ],
    };
@endphp

<aside class="app-sidebar" data-sidebar id="app-sidebar" aria-label="Workspace navigation">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-logo sidebar-profile-logo text-decoration-none" aria-label="Elevanix dashboard">
            @include('partials.brand-logo', ['variant' => 'icon', 'tone' => 'light'])
            <span class="sidebar-profile-copy">
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ str_replace('_', ' ', auth()->user()->role) }}</small>
            </span>
        </a>
        <button class="icon-btn d-lg-none" type="button" data-sidebar-close aria-label="Close sidebar">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="sidebar-navigation" aria-label="Main menu">
        <div class="menu-label">Workspace</div>
        <div class="sidebar-menu">
            @foreach($items as [$label, $icon, $url, $active, $badge])
                <a href="{{ $url }}" class="{{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
                    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                    <span>{{ $label }}</span>
                    @if($badge)
                        <small class="sidebar-badge">{{ $badge }}</small>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" data-confirm="Sign out of Elevanix?">
            @csrf
            <button class="sidebar-logout" type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</button>
        </form>
    </div>
</aside>
