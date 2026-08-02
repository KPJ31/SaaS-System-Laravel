@props(['icon', 'title'])

<div class="auth-feature-item">
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <span>
        <strong>{{ $title }}</strong>
        <small>{{ $slot }}</small>
    </span>
</div>
