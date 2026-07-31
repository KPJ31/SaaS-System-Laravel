@php
    $role = auth()->user()->role;
    $items = match ($role) {
        'super_admin' => [
            ['Dashboard', 'fa-gauge-high', route('super-admin.dashboard'), request()->routeIs('super-admin.dashboard')],
            ['Company Requests', 'fa-building-circle-check', route('super-admin.company-requests.index'), request()->routeIs('super-admin.company-requests.*')],
            ['Companies', 'fa-building', route('super-admin.companies.index'), request()->routeIs('super-admin.companies.*')],
        ],
        'company_admin' => [
            ['Dashboard', 'fa-gauge-high', route('company-admin.dashboard'), request()->routeIs('company-admin.dashboard')],
        ],
        default => [
            ['Dashboard', 'fa-gauge-high', route('employee.dashboard'), request()->routeIs('employee.dashboard')],
        ],
    };
@endphp

<aside class="app-sidebar" data-sidebar>
    <div class="sidebar-brand">
        @include('partials.brand-logo', ['tone' => 'light'])
        <button class="icon-btn d-lg-none" type="button" data-sidebar-close aria-label="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="menu-label">Workspace</div>
    <nav class="sidebar-menu" aria-label="Main menu">
        @foreach($items as [$label, $icon, $url, $active])
            <a href="{{ $url }}" class="{{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
                <i class="fa-solid {{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    <div class="sidebar-footer">
        <small>{{ auth()->user()->company?->name ?? 'Platform Administration' }}</small>
    </div>
</aside>
