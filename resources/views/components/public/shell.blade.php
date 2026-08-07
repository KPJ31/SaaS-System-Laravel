@props([
    'title',
    'description' => null,
])

<div class="landing-page public-simple-page">
    <header class="landing-navbar is-scrolled">
        <div class="container landing-nav-inner">
            <a href="{{ route('home') }}" class="text-decoration-none" aria-label="Elevanix home">
                <x-brand-logo />
            </a>
            <div class="landing-actions d-flex">
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Sign In</a>
                <a href="{{ route('company.register') }}" class="btn btn-primary">Register Company</a>
            </div>
        </div>
    </header>

    <main class="section-pad">
        <div class="container">
            <section class="content-card app-card public-content-card">
                <span class="form-badge">Elevanix</span>
                <h1>{{ $title }}</h1>
                @if($description)
                    <p>{{ $description }}</p>
                @endif
                <div class="public-page-body">
                    {{ $slot }}
                </div>
            </section>
        </div>
    </main>
</div>
