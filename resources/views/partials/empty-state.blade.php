@php
    $icon = $icon ?? 'fa-inbox';
    $title = $title ?? 'No records found';
    $message = $message ?? 'There are currently no records available in this section.';
@endphp

<div class="empty-state" role="status">
    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
    <strong>{{ $title }}</strong>
    <span>{{ $message }}</span>
    @if(isset($action) || isset($secondaryAction))
        <div class="empty-state-action">
            @isset($action)
                {{ $action }}
            @endisset
            @isset($secondaryAction)
                {{ $secondaryAction }}
            @endisset
        </div>
    @endif
</div>
