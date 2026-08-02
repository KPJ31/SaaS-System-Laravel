@props(['eyebrow' => null, 'title'])

<div class="section-heading">
    @if($eyebrow)
        <span>{{ $eyebrow }}</span>
    @endif
    <h2>{{ $title }}</h2>
    @if(trim($slot) !== '')
        <p>{{ $slot }}</p>
    @endif
</div>
