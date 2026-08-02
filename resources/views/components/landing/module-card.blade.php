@props(['icon', 'title'])

<article class="module-card h-100">
    <span class="module-icon"><i class="{{ $icon }}" aria-hidden="true"></i></span>
    <h3>{{ $title }}</h3>
    <p>{{ $slot }}</p>
</article>
