@php
    $navigation = \App\Support\DashboardNavigation::forUser(auth()->user());
    $summary = $navigation['summary'];
@endphp

<aside class="app-sidebar" data-sidebar id="app-sidebar" aria-label="Workspace navigation">
    <div class="sidebar-top">
        <x-dashboard.sidebar-account-card
            :image="$summary['image']"
            :initials="$summary['fallback']"
            :primary-text="$summary['title']"
            :secondary-text="$summary['subtitle']"
            :tertiary-text="$summary['meta'] ?? null"
            :link="$summary['link'] ?? null"
            :title="$summary['title']"
        />

        <button class="icon-btn d-lg-none" type="button" data-sidebar-close aria-label="Close sidebar">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    @if($navigation['warning'])
        <a class="sidebar-warning" href="{{ $navigation['warning']['url'] ?? route('dashboard') }}">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <span>{{ $navigation['warning']['message'] }}</span>
        </a>
    @endif

    <nav class="sidebar-navigation dashboard-sidebar-menu" aria-label="Main menu">
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
</aside>
