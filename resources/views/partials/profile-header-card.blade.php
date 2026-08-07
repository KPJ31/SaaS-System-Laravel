@php
    $roleLabel = $roleLabel ?? str_replace('_', ' ', ucfirst($user->role));
    $subtitle = $subtitle ?? $user->email;
    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'U';
@endphp

<section class="content-card mb-3">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div>
            @if($user->avatar)
                <img src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }} profile image" style="width:88px;height:88px;border-radius:50%;object-fit:cover;">
            @else
                <div class="bg-primary text-white fw-semibold" aria-label="{{ $user->name }} initials" style="width:88px;height:88px;border-radius:50%;display:grid;place-items:center;font-size:1.5rem;">
                    {{ $initials }}
                </div>
            @endif
        </div>
        <div class="flex-grow-1">
            <p class="text-muted mb-1">{{ $roleLabel }}</p>
            <h2 class="mb-1">{{ $user->name }}</h2>
            <p class="mb-0">{{ $subtitle }}</p>
        </div>
        @isset($status)
            <div>@include('partials.status-badge', ['status' => $status])</div>
        @endisset
    </div>
</section>
