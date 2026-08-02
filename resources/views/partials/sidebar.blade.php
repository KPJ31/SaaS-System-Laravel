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
        ],
        default => [
            ['Dashboard', 'fa-gauge-high', route('employee.dashboard'), request()->routeIs('employee.dashboard'), null],
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
</aside>
