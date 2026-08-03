@php
    $navigation = \App\Support\DashboardNavigation::forUser(auth()->user());
    $summary = $navigation['summary'];
@endphp

<aside class="app-sidebar" data-sidebar id="app-sidebar" aria-label="Workspace navigation">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-brand text-decoration-none" aria-label="Elevanix dashboard">
            @include('partials.brand-logo', ['variant' => 'icon', 'tone' => 'light'])
            <span>
                <strong>Elevanix</strong>
                <small>{{ $navigation['roleLabel'] }} Dashboard</small>
            </span>
        </a>
        <button class="icon-btn d-lg-none" type="button" data-sidebar-close aria-label="Close sidebar">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sidebar-user">
        @if($summary['image'])
            <img class="sidebar-avatar" src="{{ asset('storage/'.$summary['image']) }}" alt="{{ $summary['title'] }}">
        @else
            <span class="sidebar-avatar">{{ strtoupper(substr($summary['fallback'], 0, 1)) }}</span>
        @endif
        <span>
            <strong>{{ $summary['title'] }}</strong>
            <small>{{ $summary['subtitle'] }}</small>
        </span>
    </div>

    @if($navigation['warning'])
        <a class="sidebar-warning" href="{{ $navigation['warning']['url'] ?? route('dashboard') }}">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <span>{{ $navigation['warning']['message'] }}</span>
        </a>
    @endif

    <nav class="sidebar-navigation" aria-label="Main menu">
        @foreach($navigation['groups'] as $group)
            <section class="sidebar-group" aria-label="{{ $group['label'] }}">
                <div class="menu-label">{{ $group['label'] }}</div>
                <div class="sidebar-menu">
                    @foreach($group['items'] as $item)
                        @php
                            $hasChildren = count($item['children']) > 0;
                            $childActive = collect($item['children'])->contains(fn ($child) => $child['active']);
                            $isOpen = $item['active'] || $childActive;
                            $collapseId = 'sidebar-menu-'.\Illuminate\Support\Str::slug($group['label'].'-'.$item['label']);
                        @endphp

                        @if($hasChildren)
                            <button class="sidebar-link sidebar-parent {{ $isOpen ? 'active' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                                @if($item['badge'])
                                    <small class="sidebar-badge">{{ $item['badge'] }}</small>
                                @endif
                                <i class="fa-solid fa-chevron-down sidebar-chevron" aria-hidden="true"></i>
                            </button>
                            <div class="collapse sidebar-submenu {{ $isOpen ? 'show' : '' }}" id="{{ $collapseId }}">
                                @foreach($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" class="sidebar-sublink {{ $child['active'] ? 'active' : '' }}" @if($child['active']) aria-current="page" @endif>
                                        <i class="fa-solid {{ $child['icon'] }}" aria-hidden="true"></i>
                                        <span>{{ $child['label'] }}</span>
                                        @if($child['badge'])
                                            <small class="sidebar-badge">{{ $child['badge'] }}</small>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <a href="{{ $item['url'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}" @if($item['active']) aria-current="page" @endif>
                                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                                @if($item['badge'])
                                    <small class="sidebar-badge">{{ $item['badge'] }}</small>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        @foreach($navigation['footer'] as $item)
            <a class="sidebar-footer-link" href="{{ $item['url'] }}">
                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <form method="POST" action="{{ route('logout') }}" data-confirm="Sign out of Elevanix?">
            @csrf
            <button class="sidebar-logout" type="submit">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
