@php
    $topbarUser = auth()->user();
    $topbarPrefix = $topbarUser->role === 'super_admin' ? 'super-admin' : ($topbarUser->role === 'company_admin' ? 'company-admin' : 'employee');
    $topbarRole = $topbarUser->role === 'super_admin' ? 'Super Admin' : ($topbarUser->role === 'company_admin' ? 'Company Admin' : 'Employee');
    $topbarCompany = $topbarUser->company?->name;
    $passwordUrl = $topbarUser->role === 'employee' && Route::has('employee.password.edit')
        ? route('employee.password.edit')
        : (Route::has($topbarPrefix.'.profile.show') ? route($topbarPrefix.'.profile.show').'#change-password' : null);
    $unreadCount = $topbarUser->unreadNotifications()->count();
@endphp
<nav class="top-navbar" aria-label="Top navigation">
    <button class="icon-btn d-lg-none" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Open sidebar">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>
    <div class="topbar-context">
        <strong>{{ trim($__env->yieldContent('title', 'Dashboard')) }}</strong>
        <small>{{ $topbarCompany ? $topbarCompany.' | '.$topbarRole : $topbarRole }}</small>
    </div>
    <div class="navbar-actions">
        <div class="dropdown">
            <button class="icon-btn" type="button" aria-label="Notifications" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                @if($unreadCount)
                    <span class="notification-dot">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-menu">
                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notifications</span>
                    @if($unreadCount && Route::has($topbarPrefix.'.notifications.read-all'))
                        <form method="POST" action="{{ route($topbarPrefix.'.notifications.read-all') }}">
                            @csrf
                            <button class="btn btn-sm btn-link p-0" type="submit">Mark all read</button>
                        </form>
                    @endif
                </div>
                @forelse($topbarUser->notifications()->latest()->take(5)->get() as $notification)
                    <a class="dropdown-item notification-item {{ $notification->read_at ? '' : 'is-unread' }}" href="{{ route($topbarPrefix.'.notifications.index') }}">
                        <strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong>
                        <small>{{ $notification->data['message'] ?? $notification->created_at->diffForHumans() }}</small>
                    </a>
                @empty
                    <span class="dropdown-item-text text-muted small">No notifications yet.</span>
                @endforelse
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-center" href="{{ route($topbarPrefix.'.notifications.index') }}">View all notifications</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ strtoupper(substr($topbarUser->name, 0, 1)) }}</span>
                <span class="d-none d-sm-block text-start">
                    <strong>{{ $topbarUser->name }}</strong>
                    <small>{{ $topbarRole }}</small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">Signed in as {{ $topbarUser->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
                @if(Route::has($topbarPrefix.'.profile.show'))
                    <li><a class="dropdown-item" href="{{ route($topbarPrefix.'.profile.show') }}"><i class="fa-solid fa-user-circle me-2" aria-hidden="true"></i>My Profile</a></li>
                @endif
                @if($topbarUser->role === 'company_admin' && Route::has('company-admin.company-profile.show'))
                    <li><a class="dropdown-item" href="{{ route('company-admin.company-profile.show') }}"><i class="fa-solid fa-building me-2" aria-hidden="true"></i>Company Profile</a></li>
                @endif
                @if($passwordUrl)
                    <li><a class="dropdown-item" href="{{ $passwordUrl }}"><i class="fa-solid fa-key me-2" aria-hidden="true"></i>Change Password</a></li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}" data-confirm="Sign out of Elevanix?">
                        @csrf
                        <button class="dropdown-item" type="submit">
                            <i class="fa-solid fa-arrow-right-from-bracket me-2" aria-hidden="true"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
