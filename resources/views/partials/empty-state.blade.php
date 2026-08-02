@php
    $icon = $icon ?? 'fa-inbox';
    $title = $title ?? 'No records found';
    $message = $message ?? 'There are currently no records available in this section.';
@endphp

<div class="empty-state">
    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
    <strong>{{ $title }}</strong>
    <span>{{ $message }}</span>
    @isset($action)
        <div class="empty-state-action">
            {{ $action }}
        </div>
    @endisset
</div>
