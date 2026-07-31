<nav class="top-navbar">
    <button class="icon-btn d-lg-none" type="button" data-sidebar-toggle aria-label="Open sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>
    <form class="navbar-search d-none d-md-flex" role="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" placeholder="Search workspace" aria-label="Search workspace">
    </form>
    <div class="navbar-actions">
        <button class="icon-btn" type="button" aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            @auth
                @if(auth()->user()->unreadNotifications()->count())
                    <span class="notification-dot">{{ auth()->user()->unreadNotifications()->count() }}</span>
                @endif
            @endauth
        </button>
        <div class="dropdown">
            <button class="user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="d-none d-sm-block text-start">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ str_replace('_', ' ', auth()->user()->role) }}</small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">Profile tools coming next</span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" type="submit">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
