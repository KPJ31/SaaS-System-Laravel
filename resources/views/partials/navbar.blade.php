<nav class="top-navbar" aria-label="Top navigation">
    <button class="icon-btn d-lg-none" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Open sidebar">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>
    <form class="navbar-search d-none d-md-flex" role="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" placeholder="Search workspace" aria-label="Search workspace">
    </form>
    <div class="navbar-actions">
        <div class="dropdown">
            <button class="icon-btn" type="button" aria-label="Notifications" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                @auth
                    @if(auth()->user()->unreadNotifications()->count())
                        <span class="notification-dot">{{ auth()->user()->unreadNotifications()->count() }}</span>
                    @endif
                @endauth
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-menu">
                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notifications</span>
                    @if(auth()->user()->unreadNotifications()->count() && Route::has(auth()->user()->role === 'super_admin' ? 'super-admin.notifications.read-all' : 'company-admin.notifications.read-all'))
                        <form method="POST" action="{{ auth()->user()->role === 'super_admin' ? route('super-admin.notifications.read-all') : route('company-admin.notifications.read-all') }}">
                            @csrf
                            <button class="btn btn-sm btn-link p-0" type="submit">Mark all read</button>
                        </form>
                    @endif
                </div>
                @forelse(auth()->user()->notifications()->latest()->take(5)->get() as $notification)
                    <a class="dropdown-item notification-item {{ $notification->read_at ? '' : 'is-unread' }}" href="{{ auth()->user()->role === 'super_admin' ? route('super-admin.notifications.index') : route('company-admin.notifications.index') }}">
                        <strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong>
                        <small>{{ $notification->data['message'] ?? $notification->created_at->diffForHumans() }}</small>
                    </a>
                @empty
                    <span class="dropdown-item-text text-muted small">No notifications yet.</span>
                @endforelse
                <div class="dropdown-divider"></div>
                @if(auth()->user()->role === 'super_admin' || auth()->user()->role === 'company_admin')
                    <a class="dropdown-item text-center" href="{{ auth()->user()->role === 'super_admin' ? route('super-admin.notifications.index') : route('company-admin.notifications.index') }}">View all notifications</a>
                @endif
            </div>
        </div>
        <div class="dropdown">
            <button class="user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="d-none d-sm-block text-start">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ str_replace('_', ' ', auth()->user()->role) }}</small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">Signed in as {{ auth()->user()->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
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
