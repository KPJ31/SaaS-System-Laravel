@php
    $type = $type ?? 'text';
    $required = $required ?? true;
    $inputValue = $value ?? old($name);
    $icon = $icon ?? null;
    $help = $help ?? null;
    $errorId = $name.'-error';
    $helpId = $name.'-help';
    $hasError = $errors->has($name);
    $describedBy = trim(($help ? $helpId : '').' '.($hasError ? $errorId : ''));
@endphp
<label for="{{ $name }}" class="form-label">
    {{ $label }} @if($required)<span class="required-mark">*</span>@endif
</label>
<div class="{{ $icon ? 'input-icon' : '' }}">
    @if($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' || $type === 'file' ? '' : $inputValue }}"
        class="form-control @error($name) is-invalid @enderror"
        @if($required) required @endif
        @if($type === 'email') autocomplete="email" @endif
        @if($type === 'url') inputmode="url" @endif
        @if($type === 'file') accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" @endif
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    >
</div>
@if($help)
    <div id="{{ $helpId }}" class="helper-text">{{ $help }}</div>
@endif
@error($name)<div id="{{ $errorId }}" class="invalid-feedback d-block">{{ $message }}</div>@enderror
