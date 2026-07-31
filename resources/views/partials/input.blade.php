@php
    $type = $type ?? 'text';
    $required = $required ?? true;
@endphp
<label for="{{ $name }}" class="form-label">{{ $label }}</label>
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' || $type === 'file' ? '' : old($name) }}" class="form-control @error($name) is-invalid @enderror" @if($required) required @endif>
@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
